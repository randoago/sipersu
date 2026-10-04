<?php

namespace Database\Seeders;

use App\Enums\Peran;
use App\Models\Jabatan;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PenggunaSeeder extends Seeder
{
    /** Semua akun contoh: kata sandi "password". Nomor induk contoh (bukan data asli). */
    public function run(): void
    {
        $prodi = Prodi::pluck('id', 'kode');

        $buat = function (array $a, Peran $peran): User {
            $u = User::updateOrCreate(['nomor_induk' => $a['nomor_induk']], $a + ['password' => 'password']);
            $u->syncRoles([$peran->value]);

            return $u;
        };

        $buat(['nomor_induk' => '0000000001', 'nama' => 'Super Admin', 'email' => 'superadmin@umbuton.ac.id'], Peran::SuperAdmin);
        $buat(['nomor_induk' => '198701012010011001', 'nama' => 'Admin Tata Usaha', 'email' => 'tu.teknik@umbuton.ac.id'], Peran::AdminTu);

        $dekan = $buat(['nomor_induk' => '0912038401', 'nama' => 'Agusman', 'gelar_belakang' => 'S.T., MM.', 'email' => 'dekan.teknik@umbuton.ac.id'], Peran::Dekan);
        $wadek = $buat(['nomor_induk' => '0912048102', 'nama' => 'Wakil Dekan (Contoh)', 'email' => 'wadek.teknik@umbuton.ac.id'], Peran::WakilDekan);

        $kts = $buat(['nomor_induk' => '0912058301', 'nama' => 'Idwan', 'gelar_belakang' => 'S.T., M.Si.', 'prodi_id' => $prodi['TS']], Peran::Kaprodi);
        $krsk = $buat(['nomor_induk' => '0912068502', 'nama' => 'Rando', 'gelar_belakang' => 'S.Kom., M.Eng', 'prodi_id' => $prodi['RSK']], Peran::Kaprodi);
        $ksti = $buat(['nomor_induk' => '0912078603', 'nama' => 'Darmawan', 'gelar_belakang' => 'S.Kom., M.Kom.', 'prodi_id' => $prodi['STI']], Peran::Kaprodi);

        $buat(['nomor_induk' => '0912088704', 'nama' => 'Dosen Contoh', 'gelar_belakang' => 'S.T., M.T.', 'prodi_id' => $prodi['TS']], Peran::DosenTendik);

        $buat([
            'nomor_induk' => '21650012', 'nama' => 'Muhammad Fauzan', 'email' => 'fauzan@mhs.umbuton.ac.id',
            'prodi_id' => $prodi['STI'], 'angkatan' => '2021', 'tempat_lahir' => 'Baubau',
            'tanggal_lahir' => '2002-05-14', 'alamat' => 'Jl. Pahlawan No. 42, Kel. Batupoaro, Kota Baubau, Sulawesi Tenggara',
        ], Peran::Mahasiswa);

        // Spesimen Dekan (template/img/ttd-dekan): tanpa stempel + dengan stempel (dipakai surat ber-QR) di storage privat.
        $spesimen = [];
        foreach (['spesimen_ttd' => ['ttd-dekan-not-stempel.png', 'spesimen/dekan.png'], 'spesimen_stempel' => ['ttd-dekan-stempel.png', 'spesimen/dekan-stempel.png']] as $kolom => [$berkas, $tujuan]) {
            $sumber = base_path('template/img/ttd-dekan/'.$berkas);
            if (File::exists($sumber)) {
                Storage::disk('local')->put($tujuan, File::get($sumber));
                $spesimen[$kolom] = $tujuan;
            }
        }
        if ($spesimen) {
            $dekan->update($spesimen);
        }

        $jabatan = [
            ['dekan', 'Dekan Fakultas Teknik', null, $dekan],
            ['wadek1', 'Wakil Dekan I Fakultas Teknik', null, $wadek],
            ['kaprodi-ts', 'Ketua Program Studi Teknik Sipil', $prodi['TS'], $kts],
            ['kaprodi-rsk', 'Ketua Program Studi Rekayasa Sistem Komputer', $prodi['RSK'], $krsk],
            ['kaprodi-sti', 'Ketua Program Studi Sistem dan Teknologi Informasi', $prodi['STI'], $ksti],
        ];
        foreach ($jabatan as [$kode, $nama, $prodiId, $pejabat]) {
            Jabatan::updateOrCreate(['kode' => $kode], [
                'nama' => $nama, 'prodi_id' => $prodiId, 'user_id' => $pejabat->id,
                'periode_mulai' => '2024-01-01', 'aktif' => true,
            ]);
        }
    }
}
