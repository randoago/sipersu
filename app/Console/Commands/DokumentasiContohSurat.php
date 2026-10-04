<?php

namespace App\Console\Commands;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use App\Models\Surat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\AlurSuratKeluar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DokumentasiContohSurat extends Command
{
    protected $signature = 'dokumentasi:contoh-surat {--tujuan= : folder keluaran (bawaan: documentation/contoh-surat)}';

    protected $description = 'Membuat PDF contoh surat (ber-QR dan tanpa QR) untuk dokumentasi. Basis data tidak berubah (rollback).';

    public function handle(AlurPengajuan $alur, AlurSuratKeluar $surat): int
    {
        $tujuan = rtrim($this->option('tujuan') ?: base_path('documentation/contoh-surat'), '/\\');
        File::ensureDirectoryExists($tujuan);

        $u = fn (string $nik) => User::where('nomor_induk', $nik)->firstOrFail();
        [$tu, $wadek, $dekan, $mhs] = [$u('198701012010011001'), $u('0912048102'), $u('0912038401'), $u('21650012')];

        $isian = [
            'KET-AKTIF' => ['keperluan' => 'Beasiswa / KIP Kuliah', 'semester' => '7', 'tahun_akademik' => '2026/2027 Ganjil', 'keterangan' => ''],
            'IZIN-PENELITIAN' => ['judul' => 'Implementasi IoT dan Sensor Kelembaban Tanah pada Budidaya Pertanian Modern di Baubau',
                'kepada' => 'Kepala Dinas Komunikasi dan Informatika Kota Baubau', 'instansi' => 'Dinas Komunikasi dan Informatika (Diskominfo) Kota Baubau',
                'alamat_instansi' => 'Jl. Palagimata No. 12, Kel. Lipu, Kec. Betoambari, Kota Baubau', 'tgl_mulai' => '2026-11-02', 'tgl_selesai' => '2027-01-29',
                'pembimbing' => 'Rando, S.Kom., M.Eng'],
            'PENGANTAR-KP' => ['kepada' => 'Pimpinan PT Telekomunikasi Indonesia Witel Baubau', 'instansi' => 'PT Telekomunikasi Indonesia, Tbk. Witel Baubau',
                'alamat_instansi' => 'Jl. Betoambari No. 20, Kota Baubau', 'tgl_mulai' => '2026-12-01', 'tgl_selesai' => '2027-01-30',
                'anggota' => "1. La Ode Rizky – 20650021\n2. Siti Rahmawati – 21650077"],
            'CUTI-AKADEMIK' => ['semester_cuti' => 'Genap 2026/2027', 'lama' => '1 (satu) semester', 'alasan' => 'Mengikuti pelatihan kerja di luar daerah selama satu semester.'],
            'REKOM-BEASISWA' => ['nama_beasiswa' => 'Beasiswa Prestasi Muhammadiyah', 'penyelenggara' => 'Majelis Pendidikan Tinggi PP Muhammadiyah', 'semester' => '7', 'ipk' => '3,72', 'prestasi' => ''],
        ];
        $nama = ['KET-AKTIF' => '01-surat-keterangan-aktif-kuliah', 'IZIN-PENELITIAN' => '02-surat-izin-penelitian', 'PENGANTAR-KP' => '03-surat-pengantar-kerja-praktik',
            'CUTI-AKADEMIK' => '04-surat-cuti-akademik', 'REKOM-BEASISWA' => '05-surat-rekomendasi-beasiswa'];

        $berkas = [];
        DB::beginTransaction();
        try {
            foreach ($isian as $kode => $data) {
                $jenis = JenisSurat::where('kode', $kode)->firstOrFail();
                $jenis->update(['mode_ttd' => 'qr']);
                $p = $alur->ajukan($mhs, $jenis, $data);
                $p = $alur->verifikasi($p, $jenis->verifikator_role === 'kaprodi' ? $u('0912078603') : $tu);
                if ($p->status->value === 'diverifikasi') {
                    $p = $alur->paraf($p, $wadek);
                }
                $berkas[$nama[$kode].'.pdf'] = $alur->tandatangani($p, $dekan)->surat;
            }

            // Variasi: jenis surat mahasiswa yang diatur TANPA QR
            $jenis = JenisSurat::where('kode', 'KET-AKTIF')->first();
            $jenis->update(['mode_ttd' => 'basah']);
            $p = $alur->ajukan($mhs, $jenis, $isian['KET-AKTIF']);
            $p = $alur->verifikasi($p, $tu);
            $berkas['06-surat-keterangan-aktif-kuliah-TANPA-QR.pdf'] = $alur->tandatangani($p, $dekan)->surat;

            // Surat keluar umum: ber-QR dan tanpa QR
            $dasar = [
                'klasifikasi_id' => KlasifikasiSurat::where('kode', 'II.3.AU')->value('id'), 'sifat' => 'penting',
                'tujuan' => "Ketua Program Studi Teknik Sipil\nRekayasa Sistem Komputer\nSistem dan Teknologi Informasi\nFakultas Teknik Universitas Muhammadiyah Buton\ndi Tempat",
                'perihal' => 'Undangan Rapat Koordinasi Penjaminan Mutu', 'lampiran' => '1 (satu) berkas',
                'isi' => "Dengan hormat, sehubungan dengan persiapan akreditasi program studi, kami mengundang Bapak/Ibu untuk menghadiri rapat koordinasi yang akan dilaksanakan pada:\n\nHari/Tanggal : Kamis, 15 Oktober 2026\nWaktu : 09.00 WITA – selesai\nTempat : Ruang Rapat Dekanat Fakultas Teknik\nAgenda : Penyusunan instrumen akreditasi dan kesiapan dokumen\n\nDemikian undangan ini disampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.",
                'salam' => true, 'jabatan_id' => Jabatan::where('kode', 'dekan')->value('id'), 'paraf_role' => 'wakil_dekan',
            ];
            foreach (['07-surat-keluar-undangan-ber-QR.pdf' => 'qr', '08-surat-keluar-undangan-TANPA-QR.pdf' => 'basah'] as $f => $mode) {
                $s = $surat->simpan($tu, $dasar + ['mode_ttd' => $mode]);
                $s = $surat->ajukan($s, $tu);
                $s = $surat->paraf($s, $wadek, null);
                $berkas[$f] = $surat->tandatangani($s, $dekan);
            }

            // Surat dari FORMAT buatan TU (menu Surat Keluar → Buat Surat)
            $dariFormat = function (string $kode, array $isian, string $mode = 'qr') use ($surat, $tu, $dekan) {
                $f = JenisSurat::where('kode', $kode)->firstOrFail();
                $f->update(['mode_ttd' => $mode]);                       // dibatalkan oleh rollback di akhir
                $x = $surat->simpanDariFormat($tu, $f, $isian);

                return $surat->tandatangani($surat->ajukan($x, $tu), $dekan);
            };
            $undangan = [
                'kepada' => "Bapak/Ibu TIM Akreditasi\nProdi Teknik Sipil UM. Buton\nDi -\nTempat", 'lampiran' => '-', 'perihal' => 'Undangan Rapat',
                'sehubungan' => 'Pembahasan dan Persiapan Pelaksanaan Akreditasi Program Studi Teknik Sipil Fakultas Teknik Universitas Muhammadiyah Buton',
                'sebagai' => 'menghadiri rapat', 'hari_tanggal' => '2025-08-19', 'waktu' => '10.00 - Selesai', 'tempat' => 'Ruang Dosen Fakultas Teknik', 'tembusan' => 'Arsip',
            ];
            $tugas = [
                'dasar' => 'Berdasarkan ketentuan pelaksanaan Tridarma Perguruan Tinggi, yang meliputi kegiatan pendidikan, penelitian, serta pengabdian kepada masyarakat, serta dalam rangka mendukung peningkatan kinerja, profesionalisme, dan kontribusi dalam pengembangan ilmu pengetahuan kepada masyarakat yang dilaksanakan pada T.A Semester Genap 2025/2026,',
                'ditugaskan' => [['La Sianto, S.T., M.T', 'Teknik Sipil'], ['Idwan, S.T., M.Si.', 'Teknik Sipil']],
                'kegiatan' => 'Pengabdian Kepada Masyarakat', 'tema' => 'Pemberdayaan Masyarakat/Petani dalam Percepatan Peningkatan Tata Guna Air Irigasi',
                'mitra' => 'BWS Sulawesi IV Kendari', 'waktu' => '21 April 2026 - Selesai', 'tembusan' => "Rektor Universitas Muhammadiyah Buton di Baubau\nYang bersangkutan\nArsip",
            ];
            $rekomendasi = [
                'nama_penerima' => 'DARMAWAN, S.Kom.,M.Kom', 'nidn_penerima' => '0911048204', 'jabatan_penerima' => 'Kaprodi Rekayasa Sistem Komputer',
                'unit_kerja' => 'Fakultas Teknik Universitas Muhammadiyah Buton',
                'keperluan' => 'Untuk dapat ditugaskan sebagai tenaga pemeriksa ijazah S2, S1, D-4, dan Akreditasi BAN-PT dalam rangka penerimaan terpadu Polri T.A 2026 yang diadakan di Polres Baubau pada tanggal 9 s/d 30 Maret 2026.',
                'tembusan' => "Rektor Universitas Muhammadiyah Buton di Baubau\nYang bersangkutan\nArsip",
            ];
            // Tiap format dibuat berpasangan: ber-QR (TTE) dan TANPA QR (tanda tangan basah + cap)
            foreach ([['09-surat-undangan-dari-format', 'UNDANGAN-RAPAT', $undangan], ['11-surat-tugas-dari-format', 'SURAT-TUGAS', $tugas],
                ['13-surat-tugas-rekomendasi-dari-format', 'SURAT-TUGAS-REKOMENDASI', $rekomendasi]] as [$nama, $kode, $isian]) {
                $berkas["$nama.pdf"] = $dariFormat($kode, $isian, 'qr');
                $berkas[(string) (substr($nama, 0, 2) + 1).substr($nama, 2).'-TANPA-QR.pdf'] = $dariFormat($kode, $isian, 'basah');
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
