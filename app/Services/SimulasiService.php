<?php

namespace App\Services;

use App\Enums\Peran;
use App\Models\JenisSurat;
use App\Models\Pengaturan;
use App\Models\Prodi;
use App\Models\Surat;
use App\Models\User;
use App\Support\ContohIsian;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Simulasi: mengisi aplikasi dengan surat pada SETIAP tahap alur, melalui layanan yang sama dengan pemakaian nyata
 * (nomor surat, QR, notifikasi, dan log ikut tercipta). PERINGATAN: menandatangani surat memakai nomor urut sungguhan,
 * jadi jalankan di data uji, bukan di server yang sudah dipakai.
 */
class SimulasiService
{
    public const KUNCI = 'simulasi_manifest';

    public const NPM = '99650001';

    public function __construct(private AlurPengajuan $alur, private AlurSuratKeluar $keluar, private SuratMasukService $masuk)
    {
    }

    public static function sudahAda(): bool
    {
        return (bool) Pengaturan::ambil(self::KUNCI);
    }

    /** @return array<int, array<string, mixed>> */
    public static function manifest(): array
    {
        return json_decode((string) Pengaturan::ambil(self::KUNCI, '[]'), true) ?: [];
    }

    /** @return array<int, array<string, mixed>> daftar skenario yang dibuat */
    public function siapkan(): array
    {
        if (self::sudahAda()) {
            throw new RuntimeException('Simulasi sudah pernah dibuat.');
        }
        $u = fn (string $nik) => User::where('nomor_induk', $nik)->firstOrFail();
        [$tu, $wadek, $dekan, $kaprodi, $dosen] = [$u('198701012010011001'), $u('0912048102'), $u('0912038401'), $u('0912078603'), $u('0912088704')];

        $mhs = User::updateOrCreate(['nomor_induk' => self::NPM], [
            'nama' => 'Mahasiswa Simulasi', 'password' => 'password', 'prodi_id' => Prodi::where('kode', 'STI')->value('id'), 'angkatan' => '2022',
            'tempat_lahir' => 'Baubau', 'tanggal_lahir' => '2003-04-12', 'alamat' => 'Kota Baubau, Sulawesi Tenggara',
        ]);
        $mhs->syncRoles([Peran::Mahasiswa->value]);

        $manifest = [];
        $catat = function (string $kelompok, string $tahap, string $judul, string $tipe, int $id, string $mode = 'qr') use (&$manifest) {
            $manifest[] = compact('kelompok', 'tahap', 'judul', 'tipe', 'id', 'mode');
        };

        DB::transaction(function () use ($mhs, $tu, $wadek, $dekan, $kaprodi, $dosen, $catat) {
            $isian = ContohIsian::mahasiswa();
            $jenis = fn (string $k) => JenisSurat::where('kode', $k)->firstOrFail();

            // ---- A. Pengajuan mahasiswa (e-Layanan) -----------------------------------------------------
            $buat = function (string $kode, ?string $mode = null) use ($mhs, $isian, $jenis) {
                $j = $jenis($kode);
                $asal = $j->mode_ttd;
                if ($mode) {
                    $j->update(['mode_ttd' => $mode]);
                }

                return [$this->alur->ajukan($mhs, $j, $isian[$kode]), $j, $asal];
            };
            $verif = fn ($p, $j) => $this->alur->verifikasi($p, $j->verifikator_role === 'kaprodi' ? $kaprodi : $tu, 'Berkas lengkap.');

            [$p] = $buat('KET-AKTIF');
            $catat('pengajuan', '1. Diajukan', 'Surat Keterangan Aktif Kuliah — menunggu verifikasi Admin TU', 'pengajuan', $p->id);

            [$p, $j] = $buat('IZIN-PENELITIAN');
            $p = $verif($p, $j);
            $catat('pengajuan', '2. Diverifikasi', 'Surat Izin Penelitian — menunggu paraf Wakil Dekan', 'pengajuan', $p->id);

            [$p, $j] = $buat('REKOM-BEASISWA');
            $p = $verif($p, $j);
            if ($p->status->value === 'diverifikasi') {
                $p = $this->alur->paraf($p, $wadek, 'Disetujui.');
            }
            $catat('pengajuan', '3. Disetujui', 'Surat Rekomendasi Beasiswa — menunggu tanda tangan Dekan', 'pengajuan', $p->id);

            [$p, $j] = $buat('CUTI-AKADEMIK');
            $p = $verif($p, $j);
            if ($p->status->value === 'diverifikasi') {
                $p = $this->alur->paraf($p, $wadek);
            }
            $p = $this->alur->tandatangani($p, $dekan);
            $catat('pengajuan', '4. Ditandatangani', 'Surat Cuti Akademik (ber-QR) — TU menyelesaikan', 'pengajuan', $p->id);

            // Surat TANPA QR tidak melalui persetujuan: setelah diverifikasi TU, surat langsung terbit (tanpa Wakil Dekan/Dekan).
            [$p, $j, $asal] = $buat('KET-AKTIF', 'basah');
            $p = $verif($p, $j);
            $j->update(['mode_ttd' => $asal]);
            $catat('pengajuan', '4. Ditandatangani', 'Surat Keterangan Aktif Kuliah (TANPA QR) — terbit langsung setelah verifikasi TU, tanpa paraf/tanda tangan elektronik', 'pengajuan', $p->id, 'basah');

            [$p, $j] = $buat('PENGANTAR-KP');
            $p = $verif($p, $j);
            if ($p->status->value === 'diverifikasi') {
                $p = $this->alur->paraf($p, $wadek);
            }
            $p = $this->alur->tandatangani($p, $dekan);
            $p = $this->alur->selesaikan($p, $tu);
            $catat('pengajuan', '5. Selesai', 'Surat Pengantar Kerja Praktik — sudah diselesaikan, mahasiswa mengunduh', 'pengajuan', $p->id);

            [$p, $j] = $buat('PENGANTAR-KP');
            $p = $this->alur->tolak($p, $kaprodi, 'KRS semester berjalan belum terlampir.');
            $catat('pengajuan', 'Ditolak', 'Surat Pengantar Kerja Praktik — ditolak dengan alasan', 'pengajuan', $p->id);

            [$p, $j] = $buat('IZIN-PENELITIAN');
            $p = $verif($p, $j);
            $p = $this->alur->kembalikan($p, $wadek, 'Judul penelitian perlu diperjelas dan lampirkan proposal.');
            $catat('pengajuan', 'Dikembalikan', 'Surat Izin Penelitian — dikembalikan untuk revisi', 'pengajuan', $p->id);

            // ---- B. Surat keluar --------------------------------------------------------------------------
            $umum = ContohIsian::umum();
            $dariFormat = function (string $kode, array $data, ?string $mode = null, ?string $tanggal = null, ?User $pembuat = null) use ($jenis, $tu) {
                $j = $jenis($kode);
                $asal = $j->mode_ttd;
                if ($mode) {
                    $j->update(['mode_ttd' => $mode]);
                }
                $s = $this->keluar->simpanDariFormat($pembuat ?? $tu, $j, $data, null, $tanggal);
                $j->update(['mode_ttd' => $asal]);

                return $s;
            };
            $sampaiTtd = function (Surat $s, User $oleh) use ($wadek, $dekan) {
                $s = $this->keluar->ajukan($s, $oleh);
                if ($s->status === 'menunggu_paraf') {
                    $s = $this->keluar->paraf($s, $wadek, 'Disetujui.');
                }

                return $s;
            };

            $s = $this->keluar->simpan($dosen, ['perihal' => 'Undangan Rapat Koordinasi (draf)'] + $umum + ['mode_ttd' => 'qr']);
            $catat('keluar', '1. Draf', 'Surat keluar umum — baru disimpan, belum diajukan (dibuat Dosen)', 'surat', $s->id);

            $s = $this->keluar->simpan($tu, $umum + ['mode_ttd' => 'qr', 'paraf_role' => 'wakil_dekan']);
            $s = $this->keluar->ajukan($s, $tu);
            $catat('keluar', '2. Menunggu paraf', 'Surat keluar umum — menunggu paraf Wakil Dekan', 'surat', $s->id);

            $s = $sampaiTtd($dariFormat('UNDANGAN-RAPAT', ContohIsian::undangan()), $tu);
            $catat('keluar', '3. Menunggu tanda tangan', 'Surat Undangan (format TU) — menunggu tanda tangan Dekan', 'surat', $s->id);

            $s = $this->keluar->ajukan($dariFormat('SURAT-PENCAIRAN-ANGGARAN', ContohIsian::pencairan()), $tu);
            $catat('keluar', '2. Menunggu paraf', 'Surat Permohonan Pencairan Anggaran (format TU) — menunggu paraf Wakil Dekan', 'surat', $s->id);

            $s = $this->keluar->simpan($tu, $umum + ['mode_ttd' => 'qr', 'paraf_role' => 'wakil_dekan']);
            $s = $this->keluar->kembalikan($this->keluar->ajukan($s, $tu), $wadek, 'Mohon lengkapi agenda dan waktu rapat.');
            $catat('keluar', 'Dikembalikan', 'Surat keluar umum — dikembalikan Wakil Dekan untuk revisi', 'surat', $s->id);

            $s = $this->keluar->tandatangani($sampaiTtd($dariFormat('SURAT-TUGAS', ContohIsian::tugas()), $tu), $dekan);
            $catat('keluar', '4. Ditandatangani', 'Surat Tugas — ber-QR', 'surat', $s->id);

            $s = $sampaiTtd($dariFormat('SURAT-TUGAS', ContohIsian::tugas(), 'basah'), $tu);
            $catat('keluar', '4. Ditandatangani', 'Surat Tugas — TANPA QR: langsung terbit tanpa persetujuan (cetak, tanda tangan basah + cap)', 'surat', $s->id, 'basah');

            $s = $this->keluar->tandatangani($sampaiTtd($dariFormat('SURAT-TUGAS-REKOMENDASI', ContohIsian::rekomendasi(), null, now()->addDays(5)->toDateString()), $tu), $dekan);
            $catat('keluar', '4. Ditandatangani', 'Surat Tugas Rekomendasi — tanggal surat dipilih (Hijriah otomatis)', 'surat', $s->id);

            $s = $sampaiTtd($dariFormat('SURAT-PEMBERITAHUAN', ContohIsian::pemberitahuan()), $tu);
            $catat('keluar', '4. Ditandatangani', 'Surat Pemberitahuan — TANPA QR: langsung terbit saat diajukan', 'surat', $s->id, 'basah');

            $s = $this->keluar->tandatangani($sampaiTtd($dariFormat('UNDANGAN-RAPAT', ContohIsian::undangan()), $tu), $dekan);
            $s = $this->keluar->batalkan($s, $tu, 'Rapat ditunda; surat diganti dengan undangan baru.');
            $catat('keluar', '5. Dibatalkan', 'Surat Undangan — dibatalkan (QR menampilkan TIDAK BERLAKU, nomor tidak dipakai ulang)', 'surat', $s->id);

            // ---- C. Surat masuk ---------------------------------------------------------------------------
            $contoh = base_path('documentation/contoh-surat/01-surat-keterangan-aktif-kuliah-BER-QR.pdf');
            $scan = fn () => is_file($contoh) ? new UploadedFile($contoh, 'pindaian-surat.pdf', 'application/pdf', null, true) : null;
            $hari = fn (int $n) => now()->subDays($n)->toDateString();
            foreach ([
                ['SM-UNDANGAN', 'Undangan dicatat, dengan pindaian', ['nomor_asal' => 'SIM/001/LL9/2026', 'asal' => 'LLDIKTI Wilayah IX', 'tgl_surat' => $hari(3), 'tgl_diterima' => $hari(2),
                    'perihal' => 'Undangan Rapat Koordinasi Akreditasi (simulasi)', 'sifat' => 'penting', 'klasifikasi_id' => null, 'lampiran' => '1 (satu) berkas',
                    'isian' => ['tanggal_kegiatan' => now()->addDays(9)->toDateString(), 'waktu' => '08.30 WITA – selesai', 'tempat' => 'Aula LLDIKTI Wilayah IX, Makassar']], true],
                ['SM-PERMOHONAN', 'Permohonan/Audiensi dicatat', ['nomor_asal' => 'SIM/002/DISKOMINFO/2026', 'asal' => 'Dinas Kominfo Kota Baubau', 'tgl_surat' => $hari(2), 'tgl_diterima' => $hari(2),
                    'perihal' => 'Permohonan Kerja Sama Sistem Informasi (simulasi)', 'sifat' => 'segera', 'klasifikasi_id' => null, 'lampiran' => '',
                    'isian' => ['bentuk_permohonan' => 'Kerja sama', 'batas_tanggapan' => now()->addDays(10)->toDateString()]], true],
                ['SM-UMUM', 'Surat masuk rahasia (tanpa pindaian)', ['nomor_asal' => 'SIM/003/BIRO/2026', 'asal' => 'Biro Kepegawaian', 'tgl_surat' => $hari(1), 'tgl_diterima' => $hari(1),
                    'perihal' => 'Pemberitahuan Hasil Verifikasi Berkas (simulasi, rahasia)', 'sifat' => 'rahasia', 'klasifikasi_id' => null, 'lampiran' => '', 'isian' => []], false],
            ] as [$kode, $judul, $data, $denganScan]) {
                $m = $this->masuk->catat($tu, $jenis($kode), $data, $denganScan ? $scan() : null);
                $catat('masuk', 'Tercatat', $judul.' — nomor agenda otomatis', 'surat', $m->id);
            }
        });

        Pengaturan::simpan(self::KUNCI, json_encode($manifest));

        return $manifest;
    }
}
