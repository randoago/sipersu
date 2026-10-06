<?php

namespace App\Console\Commands;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use App\Models\Surat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\AlurSuratKeluar;
use App\Support\ContohIsian;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DokumentasiContohSurat extends Command
{
    protected $signature = 'dokumentasi:contoh-surat {--tujuan= : folder keluaran (bawaan: documentation/contoh-surat)}';

    protected $description = 'Membuat PDF contoh SETIAP jenis surat, masing-masing ber-QR dan tanpa QR, untuk dokumentasi. Basis data tidak berubah (rollback).';

    public function handle(AlurPengajuan $alur, AlurSuratKeluar $surat): int
    {
        $tujuan = rtrim($this->option('tujuan') ?: base_path('documentation/contoh-surat'), '/\\');
        File::ensureDirectoryExists($tujuan);

        $u = fn (string $nik) => User::where('nomor_induk', $nik)->firstOrFail();
        [$tu, $wadek, $dekan, $mhs] = [$u('198701012010011001'), $u('0912048102'), $u('0912038401'), $u('21650012')];

        $isian = ContohIsian::mahasiswa();

        // Tiap jenis surat dibuat berpasangan: BER-QR (TTE) dan TANPA-QR (tanda tangan basah + cap), satu nomor urut per jenis.
        $nama = ['KET-AKTIF' => '01-surat-keterangan-aktif-kuliah', 'IZIN-PENELITIAN' => '02-surat-izin-penelitian', 'PENGANTAR-KP' => '03-surat-pengantar-kerja-praktik',
            'CUTI-AKADEMIK' => '04-surat-cuti-akademik', 'REKOM-BEASISWA' => '05-surat-rekomendasi-beasiswa'];
        $mode = ['qr' => 'BER-QR', 'basah' => 'TANPA-QR'];

        $berkas = [];
        DB::beginTransaction();
        try {
            foreach ($isian as $kode => $data) {
                $jenis = JenisSurat::where('kode', $kode)->firstOrFail();
                foreach ($mode as $m => $label) {
                    $jenis->update(['mode_ttd' => $m]);                  // dibatalkan oleh rollback di akhir
                    $p = $alur->ajukan($mhs, $jenis, $data);
                    $p = $alur->verifikasi($p, $jenis->verifikator_role === 'kaprodi' ? $u('0912078603') : $tu);
                    if ($p->status->value === 'diverifikasi') {
                        $p = $alur->paraf($p, $wadek);
                    }
                    $berkas[$nama[$kode]."-$label.pdf"] = ($p->status->value === 'ditandatangani' ? $p : $alur->tandatangani($p, $dekan))->surat;   // tanpa QR: sudah terbit tanpa persetujuan
                }
            }

            // Surat keluar umum: ber-QR dan tanpa QR
            $dasar = ContohIsian::umum();
            foreach (['06-surat-keluar-umum-BER-QR.pdf' => 'qr', '06-surat-keluar-umum-TANPA-QR.pdf' => 'basah'] as $f => $modeTtd) {
                $s = $surat->simpan($tu, $dasar + ['mode_ttd' => $modeTtd]);
                $s = $surat->ajukan($s, $tu);                          // tanpa QR: langsung terbit tanpa persetujuan
                if ($s->status === 'menunggu_paraf') {
                    $s = $surat->paraf($s, $wadek, null);
                }
                $berkas[$f] = $s->status === 'ditandatangani' ? $s : $surat->tandatangani($s, $dekan);
            }

            // Surat dari FORMAT buatan TU (menu Surat Keluar → Buat Surat)
            $dariFormat = function (string $kode, array $isian, string $mode = 'qr') use ($surat, $tu, $dekan, $wadek) {
                $f = JenisSurat::where('kode', $kode)->firstOrFail();
                $f->update(['mode_ttd' => $mode]);                       // dibatalkan oleh rollback di akhir
                $x = $surat->ajukan($surat->simpanDariFormat($tu, $f, $isian), $tu);
                if ($x->status === 'menunggu_paraf') {
                    $x = $surat->paraf($x, $wadek, 'Disetujui.');
                }

                return $x->status === 'ditandatangani' ? $x : $surat->tandatangani($x, $dekan);   // tanpa persetujuan: sudah terbit
            };
            $undangan = ContohIsian::undangan();
            $tugas = ContohIsian::tugas();
            $rekomendasi = ContohIsian::rekomendasi();
            $pemberitahuan = ContohIsian::pemberitahuan();
            // Format buatan TU, masing-masing ber-QR dan tanpa QR
            foreach ([['07-surat-undangan-dari-format', 'UNDANGAN-RAPAT', $undangan], ['08-surat-tugas-dari-format', 'SURAT-TUGAS', $tugas],
                ['09-surat-tugas-rekomendasi-dari-format', 'SURAT-TUGAS-REKOMENDASI', $rekomendasi], ['10-surat-pemberitahuan-dari-format', 'SURAT-PEMBERITAHUAN', $pemberitahuan],
                ['11-surat-keterangan-aktif-kuliah-dari-format', 'SK-AKTIF-KULIAH', ContohIsian::skAktifKuliah()], ['12-surat-keterangan-cuti-dari-format', 'SK-CUTI', ContohIsian::skCuti()],
                ['13-surat-keterangan-aktif-kembali-dari-format', 'SK-AKTIF-KEMBALI', ContohIsian::skAktifKembali()], ['14-surat-pencairan-anggaran-dari-format', 'SURAT-PENCAIRAN-ANGGARAN', ContohIsian::pencairan()],
                ['15-surat-izin-penelitian-dari-format', 'SURAT-PENELITIAN', ContohIsian::penelitian()]] as [$nm, $kode, $data]) {
                foreach ($mode as $m => $label) {
                    $berkas["$nm-$label.pdf"] = $dariFormat($kode, $data, $m);
                }
            }

            foreach ($berkas as $f => $s) {
                File::put($tujuan.DIRECTORY_SEPARATOR.$f, Storage::disk('local')->get($s->fresh()->file_pdf));
                $this->line("✓ $f   ({$s->fresh()->nomor})");
            }
        } finally {
            foreach ($berkas as $s) {
                Storage::disk('local')->delete($s->fresh()?->file_pdf ?? '');
            }
            DB::rollBack();
            Storage::disk('local')->deleteDirectory('surat');   // sisa berkas contoh
        }

        $this->info(count($berkas).' contoh surat dibuat di '.$tujuan.' (basis data tidak berubah).');

        return self::SUCCESS;
    }
}
