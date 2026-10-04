<?php

namespace Database\Seeders;

use App\Models\KlasifikasiSurat;
use App\Models\Pengaturan;
use App\Models\Prodi;
use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // Prodi contoh — sesuaikan di Master Data.
        foreach ([
            ['TS', 'Teknik Sipil'],
            ['RSK', 'Rekayasa Sistem Komputer'],
            ['STI', 'Sistem dan Teknologi Informasi'],
        ] as [$kode, $nama]) {
            Prodi::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'jenjang' => 'S1']);
        }

        // Klasifikasi contoh — kode mengikuti pola 045/II.3.AU/FT-UMB/X/2026.
        foreach ([
            ['II.3.AU', 'Administrasi Umum', 'Surat keterangan, pengantar, undangan, dan korespondensi umum'],
            ['II.1.AK', 'Akademik', 'Keterangan aktif kuliah, cuti akademik, dan layanan akademik lain'],
            ['II.2.KM', 'Kemahasiswaan', 'Rekomendasi beasiswa, kegiatan mahasiswa'],
            ['II.4.PN', 'Penelitian dan Pengabdian', 'Izin penelitian, pengabdian kepada masyarakat'],
            ['II.5.KP', 'Kerja Praktik dan Magang', 'Pengantar kerja praktik, magang, kunjungan industri'],
            ['II.6.SK', 'Surat Keputusan', 'SK Dekan dan surat tugas'],
        ] as [$kode, $nama, $ket]) {
            KlasifikasiSurat::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'keterangan' => $ket]);
        }

        Pengaturan::simpan('format_nomor', '{urut}/{klasifikasi}/FT-UMB/{bulan_romawi}/{tahun}');
        Pengaturan::simpan('panjang_urut', '3');
        Pengaturan::simpan('format_agenda', 'AGD-{tahun}/{bulan_romawi}/{urut}');
        Pengaturan::simpan('panjang_agenda', '4');
        Pengaturan::simpan('alamat_fakultas', 'Jl. Betoambari No. 36, Kota Baubau, Sulawesi Tenggara 93724');
        Pengaturan::simpan('email_fakultas', 'teknik@um-buton.ac.id');
        Pengaturan::simpan('web_fakultas', 'ft.um-buton.ac.id');
        Pengaturan::simpan('kota_surat', 'Baubau');
    }
}
