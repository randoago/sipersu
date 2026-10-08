<?php

namespace App\Support;

use App\Models\Jabatan;
use App\Models\KlasifikasiSurat;

/** Isian contoh untuk dokumentasi (PDF contoh surat) dan simulasi aplikasi. */
class ContohIsian
{
    /** @return array<string, array<string, mixed>> isian pengajuan mahasiswa menurut kode jenis surat */
    public static function mahasiswa(): array
    {
        return [
            'KET-AKTIF' => ['semester' => '7', 'tahun_akademik' => '2026/2027 Ganjil', 'keterangan' => ''],
            'IZIN-PENELITIAN' => ['judul' => 'Implementasi IoT dan Sensor Kelembaban Tanah pada Budidaya Pertanian Modern di Baubau',
                'kepada' => 'Kepala Dinas Komunikasi dan Informatika Kota Baubau', 'instansi' => 'Dinas Komunikasi dan Informatika (Diskominfo) Kota Baubau',
                'alamat_instansi' => 'Jl. Palagimata No. 12, Kel. Lipu, Kec. Betoambari, Kota Baubau', 'tgl_mulai' => '2026-11-02', 'tgl_selesai' => '2027-01-29',
                'pembimbing' => 'Rando, S.Kom., M.Eng'],
            'PENGANTAR-KP' => ['kepada' => 'Pimpinan PT Telekomunikasi Indonesia Witel Baubau', 'instansi' => 'PT Telekomunikasi Indonesia, Tbk. Witel Baubau',
                'alamat_instansi' => 'Jl. Betoambari No. 20, Kota Baubau', 'tgl_mulai' => '2026-12-01', 'tgl_selesai' => '2027-01-30',
                'anggota' => "1. La Ode Rizky – 20650021\n2. Siti Rahmawati – 21650077"],
            'CUTI-AKADEMIK' => ['semester_cuti' => 'Genap 2026/2027', 'alasan' => 'Mengikuti pelatihan kerja di luar daerah'],
            'REKOM-BEASISWA' => ['nama_beasiswa' => 'Beasiswa Prestasi Muhammadiyah', 'penyelenggara' => 'Majelis Pendidikan Tinggi PP Muhammadiyah', 'semester' => '7', 'ipk' => '3,72', 'prestasi' => ''],
        ];
    }

    /** Surat keluar umum (tanpa format). */
    public static function umum(): array
    {
        return [
            'klasifikasi_id' => KlasifikasiSurat::where('kode', 'A')->value('id'), 'sifat' => 'penting',
            'tujuan' => "Ketua Program Studi Teknik Sipil\nRekayasa Sistem Komputer\nSistem dan Teknologi Informasi\nFakultas Teknik Universitas Muhammadiyah Buton\ndi Tempat",
            'perihal' => 'Undangan Rapat Koordinasi Penjaminan Mutu', 'lampiran' => '1 (satu) berkas',
            'isi' => "Dengan hormat, sehubungan dengan persiapan akreditasi program studi, kami mengundang Bapak/Ibu untuk menghadiri rapat koordinasi yang akan dilaksanakan pada:\n\nHari/Tanggal : Kamis, 15 Oktober 2026\nWaktu : 09.00 WITA – selesai\nTempat : Ruang Rapat Dekanat Fakultas Teknik\nAgenda : Penyusunan instrumen akreditasi dan kesiapan dokumen\n\nDemikian undangan ini disampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.",
            'salam' => true, 'jabatan_id' => Jabatan::where('kode', 'dekan')->value('id'), 'paraf_role' => 'wakil_dekan',
        ];
    }

    public static function undangan(): array
    {
        return [
            'kepada' => "Bapak/Ibu TIM Akreditasi\nProdi Teknik Sipil UM. Buton\nDi -\nTempat", 'lampiran' => '-', 'perihal' => 'Undangan Rapat',
            'sehubungan' => 'Pembahasan dan Persiapan Pelaksanaan Akreditasi Program Studi Teknik Sipil Fakultas Teknik Universitas Muhammadiyah Buton',
            'sebagai' => 'menghadiri rapat', 'hari_tanggal' => '2025-08-19', 'waktu' => '10.00 - Selesai', 'tempat' => 'Ruang Dosen Fakultas Teknik', 'tembusan' => 'Arsip',
        ];
    }

    public static function tugas(): array
    {
        return [
            'dasar' => 'Berdasarkan ketentuan pelaksanaan Tridarma Perguruan Tinggi, yang meliputi kegiatan pendidikan, penelitian, serta pengabdian kepada masyarakat, serta dalam rangka mendukung peningkatan kinerja, profesionalisme, dan kontribusi dalam pengembangan ilmu pengetahuan kepada masyarakat yang dilaksanakan pada T.A Semester Genap 2025/2026,',
            'ditugaskan' => [['La Sianto, S.T., M.T', 'Teknik Sipil'], ['Idwan, S.T., M.Si.', 'Teknik Sipil']],
            'kegiatan' => 'Pengabdian Kepada Masyarakat', 'tema' => 'Pemberdayaan Masyarakat/Petani dalam Percepatan Peningkatan Tata Guna Air Irigasi',
            'mitra' => 'BWS Sulawesi IV Kendari', 'waktu' => '21 April 2026 - Selesai', 'tembusan' => "Rektor Universitas Muhammadiyah Buton di Baubau\nYang bersangkutan\nArsip",
        ];
    }

    public static function rekomendasi(): array
    {
        return [
            'nama_penerima' => 'DARMAWAN, S.Kom.,M.Kom', 'nidn_penerima' => '0911048204', 'jabatan_penerima' => 'Kaprodi Rekayasa Sistem Komputer',
            'unit_kerja' => 'Fakultas Teknik Universitas Muhammadiyah Buton',
            'keperluan' => 'Untuk dapat ditugaskan sebagai tenaga pemeriksa ijazah S2, S1, D-4, dan Akreditasi BAN-PT dalam rangka penerimaan terpadu Polri T.A 2026 yang diadakan di Polres Baubau pada tanggal 9 s/d 30 Maret 2026.',
            'tembusan' => "Rektor Universitas Muhammadiyah Buton di Baubau\nYang bersangkutan\nArsip",
        ];
        $pemberitahuan = [
            'kepada' => "Seluruh Dosen dan Tenaga Kependidikan\nFakultas Teknik Universitas Muhammadiyah Buton\nDi Tempat", 'hal' => 'Pemberitahuan Jadwal Ujian Tengah Semester',
            'isi' => 'Sehubungan dengan pelaksanaan Ujian Tengah Semester Ganjil Tahun Akademik 2026/2027, kami memberitahukan bahwa ujian akan dilaksanakan pada tanggal 19 s/d 30 Oktober 2026. Mohon Bapak/Ibu menyiapkan naskah soal dan menyerahkannya kepada Tata Usaha paling lambat satu minggu sebelum pelaksanaan.',
        ];
    }

    public static function pemberitahuan(): array
    {
        return [
            'kepada' => "Seluruh Dosen dan Tenaga Kependidikan\nFakultas Teknik Universitas Muhammadiyah Buton\nDi Tempat", 'hal' => 'Pemberitahuan Jadwal Ujian Tengah Semester',
            'isi' => 'Sehubungan dengan pelaksanaan Ujian Tengah Semester Ganjil Tahun Akademik 2026/2027, kami memberitahukan bahwa ujian akan dilaksanakan pada tanggal 19 s/d 30 Oktober 2026. Mohon Bapak/Ibu menyiapkan naskah soal dan menyerahkannya kepada Tata Usaha paling lambat satu minggu sebelum pelaksanaan.',
        ];
    }

    public static function skAktifKuliah(): array
    {
        return ['nama_mhs' => 'MUHAMMAD FAUZAN', 'npm' => '21650012', 'prodi' => 'Sistem dan Teknologi Informasi (S1)', 'semester' => '7', 'tahun_akademik' => '2026/2027 Ganjil'];
    }

    public static function skCuti(): array
    {
        return ['nama_mhs' => 'AHMAD SYAHRIR', 'npm' => '21650034', 'prodi' => 'Teknik Sipil (S1)', 'semester_cuti' => 'Genap 2026/2027', 'lama' => '1 (satu) semester',
            'alasan' => 'Mengikuti pelatihan kerja di luar daerah'];
    }

    public static function skAktifKembali(): array
    {
        return ['nama_mhs' => 'AHMAD SYAHRIR', 'npm' => '21650034', 'prodi' => 'Teknik Sipil (S1)', 'semester_aktif' => 'Ganjil 2027/2028', 'masa_cuti' => 'Genap 2026/2027', 'keperluan' => 'Pendaftaran ulang dan pengisian KRS'];
    }

    public static function pencairan(): array
    {
        return [
            'kepada' => "Wakil Rektor II Universitas Muhammadiyah Buton\nDi Tempat", 'lampiran' => '1 (satu) berkas RAB',
            'kegiatan' => 'Seminar Nasional Teknik dan Teknologi Informasi', 'waktu_tempat' => '12 November 2026, Aula Fakultas Teknik', 'sumber_dana' => 'Anggaran Fakultas Teknik T.A. 2026',
            'rincian' => [['Konsumsi peserta dan panitia', '150 orang', '7.500.000'], ['Sewa sound system', '1 paket', '2.500.000'], ['Sertifikat, spanduk, dan ATK', '1 paket', '2.500.000']],
            'total' => 'Rp 12.500.000,-', 'terbilang' => 'Dua belas juta lima ratus ribu rupiah', 'penerima' => 'Ketua Panitia: Rando, S.Kom., M.Eng', 'tembusan' => "Bagian Keuangan\nArsip",
        ];
    }

    public static function penelitian(): array
    {
        return ['kepada' => 'Kepala Dinas Komunikasi dan Informatika Kota Baubau', 'instansi' => 'Dinas Komunikasi dan Informatika (Diskominfo) Kota Baubau',
            'alamat_instansi' => 'Jl. Palagimata No. 12, Kel. Lipu, Kec. Betoambari, Kota Baubau', 'nama_mhs' => 'MUHAMMAD FAUZAN', 'npm' => '21650012', 'prodi' => 'Sistem dan Teknologi Informasi (S1)',
            'judul' => 'Implementasi IoT dan Sensor Kelembaban Tanah pada Budidaya Pertanian Modern di Baubau', 'tgl_mulai' => '2026-11-02', 'tgl_selesai' => '2027-01-29', 'pembimbing' => 'Rando, S.Kom., M.Eng'];
    }
}
