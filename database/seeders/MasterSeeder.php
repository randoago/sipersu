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
            ['TS', 'Teknik Sipil', 'UMB-06.1'],
            ['RSK', 'Rekayasa Sistem Komputer', 'UMB-06.2'],
            ['STI', 'Sistem dan Teknologi Informasi', null],   // belum ada pada daftar kode unit kerja universitas → memakai kode fakultas
        ] as [$kode, $nama, $unit]) {
            Prodi::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'jenjang' => 'S1', 'kode_unit' => $unit]);
        }

        // Klasifikasi = Pokok Masalah menurut Pedoman Tata Naskah Dinas UM Buton (bagian "A" pada 5/KEP/II.3.AU/UMB/A/2025).
        foreach ([
            ['A', 'Umum dan Tata Usaha'], ['B', 'Organisasi'], ['C', 'Keuangan, Perlengkapan, dan Perbekalan'], ['D', 'Personalia'],
            ['E', 'Keagamaan, Dakwah/Tabligh, dan Penyiaran'], ['F', 'Pendidikan, Penelitian, dan Latihan (Darul Arqam dsb)'], ['G', 'Perekonomian'],
            ['H', 'Kesehatan, Sosial, dan Kemasyarakatan'], ['I', 'Hukum, Perundang-undangan, Hak Asasi Manusia'], ['J', 'Hubungan Luar Masyarakat'],
            ['K', 'Wakaf dan Zakat, Infaq, serta Shadaqah'], ['L', 'Pemberdayaan Masyarakat'], ['M', 'Kepustakaan dan Informasi'],
            ['N', 'Seni Budaya dan Olahraga'], ['O', 'Lain-lain'],
        ] as [$kode, $nama]) {
            KlasifikasiSurat::updateOrCreate(['kode' => $kode], ['nama' => $nama]);
        }

        Pengaturan::simpan('format_nomor', '{urut}/{kekhususan}/II.3.AU/{unit}/{klasifikasi}/{tahun}');
        Pengaturan::simpan('panjang_urut', '3');
        Pengaturan::simpan('kode_unit_fakultas', 'UMB-06');   // Fakultas Teknik
        Pengaturan::simpan('format_agenda', 'AGD-{tahun}/{bulan_romawi}/{urut}');
        Pengaturan::simpan('panjang_agenda', '4');
        // Kop surat (header: 3 baris; footer: alamat + kontak) — sesuai surat resmi fakultas
        Pengaturan::simpan('kop_alamat', 'Jl. Betoambari No. 36 Telp (0402) 2827038 Kota Baubau Sulawesi Tenggara');
        Pengaturan::simpan('alamat_fakultas', 'Jl. Betoambari No. 36, Telp. (0402) 2827038, Kota Baubau Sulawesi Tenggara');
        Pengaturan::simpan('email_fakultas', 'rektorat@umbuton.ac.id');
        Pengaturan::simpan('web_fakultas', 'umbuton.ac.id');
        Pengaturan::simpan('kota_surat', 'Baubau');
    }
}
