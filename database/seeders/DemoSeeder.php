<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Models\JenisSurat;
use App\Models\Prodi;
use App\Models\User;
use App\Services\AlurPengajuan;
use Illuminate\Database\Seeder;

/** Data demo untuk uji tampilan: php artisan db:seed --class=DemoSeeder (JANGAN dijalankan di produksi). */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $alur = app(AlurPengajuan::class);
        $prodi = Prodi::pluck('id', 'kode');
        $tu = User::where('nomor_induk', '198701012010011001')->first();
        $wadek = User::where('nomor_induk', '0912048102')->first();
        $dekan = User::where('nomor_induk', '0912038401')->first();

        $mhs = [];
        foreach ([
            ['21650034', 'Ahmad Syahrir', 'TS'], ['22650011', 'Nurul Aini', 'STI'], ['21650077', 'Siti Rahmawati', 'TS'],
            ['21650088', 'Bambang Irawan', 'RSK'], ['20650021', 'La Ode Rizky', 'RSK'],
        ] as [$nim, $nama, $kp]) {
            $u = User::updateOrCreate(['nomor_induk' => $nim], [
                'nama' => $nama, 'password' => 'password', 'prodi_id' => $prodi[$kp], 'angkatan' => '20'.substr($nim, 0, 2),
                'tempat_lahir' => 'Baubau', 'tanggal_lahir' => '2002-03-10', 'alamat' => 'Kota Baubau, Sulawesi Tenggara',
            ]);
            $u->syncRoles([Peran::Mahasiswa->value]);
            $mhs[] = $u;
        }
        $fauzan = User::where('nomor_induk', '21650012')->first();

        $aktif = JenisSurat::where('kode', 'KET-AKTIF')->first();
        $izin = JenisSurat::where('kode', 'IZIN-PENELITIAN')->first();
        $kp = JenisSurat::where('kode', 'PENGANTAR-KP')->first();
        $beasiswa = JenisSurat::where('kode', 'REKOM-BEASISWA')->first();

        $isianAktif = ['semester' => '7', 'tahun_akademik' => '2026/2027 Ganjil', 'keterangan' => ''];
        $isianIzin = fn ($j) => ['judul' => $j, 'kepada' => 'Kepala Dinas Komunikasi dan Informatika Kota Baubau', 'instansi' => 'Dinas Komunikasi dan Informatika (Diskominfo) Kota Baubau',
            'alamat_instansi' => 'Jl. Palagimata No. 12, Kel. Lipu, Kec. Betoambari, Kota Baubau', 'tgl_mulai' => '2026-11-01', 'tgl_selesai' => '2027-01-31', 'pembimbing' => 'Dr. Eng. Ir. Sudirman, S.T., M.T.'];

        // 1) Fauzan: izin penelitian SELESAI (lengkap sampai TTD)
        $p = $alur->ajukan($fauzan, $izin, $isianIzin('Implementasi IoT dan Sensor Kelembaban Tanah pada Budidaya Pertanian Modern di Baubau'));
        $p = $alur->verifikasi($p, $tu, 'Berkas lengkap.');
        $p = $alur->paraf($p, $wadek, 'Draf disetujui.');
        $p = $alur->tandatangani($p, $dekan);
        $alur->selesaikan($p, $tu);

        // 2) Fauzan: aktif kuliah sedang diverifikasi
        $alur->ajukan($fauzan, $aktif, $isianAktif);

        // 3) Fauzan: KP DITOLAK
        $p = $alur->ajukan($fauzan, $kp, ['kepada' => 'Pimpinan PT Telkom Witel Baubau', 'instansi' => 'PT Telekomunikasi Indonesia', 'alamat_instansi' => 'Jl. Betoambari, Baubau',
            'tgl_mulai' => '2026-12-01', 'tgl_selesai' => '2027-01-15', 'anggota' => '']);
        $alur->tolak($p, User::where('nomor_induk', '0912078603')->first(), 'KRS semester berjalan belum terlampir.');

        // 4) Antrean petugas
        $alur->ajukan($mhs[0], $aktif, $isianAktif);
        $alur->ajukan($mhs[1], $izin, $isianIzin('Rancang Bangun Sistem Monitoring Kualitas Air Berbasis IoT'));
        $p = $alur->ajukan($mhs[2], $beasiswa, ['nama_beasiswa' => 'Beasiswa Prestasi Muhammadiyah', 'penyelenggara' => 'Majelis Dikti PP Muhammadiyah', 'semester' => '6', 'ipk' => '3,72', 'prestasi' => '']);
        $p = $alur->verifikasi($p, $tu);                    // menunggu paraf wakil dekan
        $p = $alur->ajukan($mhs[3], $aktif, $isianAktif);
        $alur->verifikasi($p, $tu);                         // siap TTD dekan
        $p = $alur->ajukan($mhs[4], $aktif, $isianAktif);
        $p = $alur->verifikasi($p, $tu);
        $alur->tandatangani($p, $dekan);                    // ditandatangani
    }
}
