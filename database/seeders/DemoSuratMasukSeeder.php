<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use App\Models\User;
use App\Services\SuratMasukService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

/** Data demo surat masuk: php artisan db:seed --class=DemoSuratMasukSeeder (jangan di produksi). */
class DemoSuratMasukSeeder extends Seeder
{
    public function run(): void
    {
        $tu = User::where('nomor_induk', '198701012010011001')->firstOrFail();
        $layanan = app(SuratMasukService::class);
        $contoh = base_path('documentation/contoh-surat/01-surat-keterangan-aktif-kuliah.pdf');
        $scan = fn () => is_file($contoh) ? new UploadedFile($contoh, 'pindaian-surat.pdf', 'application/pdf', null, true) : null;
        $jenis = fn (string $k) => JenisSurat::where('kode', $k)->firstOrFail();
        $hari = fn (int $n) => now()->subDays($n)->toDateString();

        $layanan->catat($tu, $jenis('SM-UNDANGAN'), [
            'nomor_asal' => '0451/LL9/TU/2026', 'asal' => 'Lembaga Layanan Pendidikan Tinggi Wilayah IX (LLDIKTI)', 'tgl_surat' => $hari(6), 'tgl_diterima' => $hari(5),
            'perihal' => 'Undangan Rapat Koordinasi Penjaminan Mutu & Akreditasi Program Studi', 'sifat' => 'penting', 'klasifikasi_id' => null, 'lampiran' => '1 (satu) berkas',
            'isian' => ['tanggal_kegiatan' => now()->addDays(9)->toDateString(), 'waktu' => '08.30 WITA – selesai', 'tempat' => 'Aula LLDIKTI Wilayah IX, Makassar'],
        ], $scan());
        $layanan->catat($tu, $jenis('SM-PERMOHONAN'), [
            'nomor_asal' => '120/DISKOMINFO/BB/2026', 'asal' => 'Dinas Komunikasi dan Informatika Kota Baubau', 'tgl_surat' => $hari(4), 'tgl_diterima' => $hari(4),
            'perihal' => 'Permohonan Kerja Sama Pengembangan Sistem Informasi Layanan Publik', 'sifat' => 'segera', 'klasifikasi_id' => null, 'lampiran' => '',
            'isian' => ['bentuk_permohonan' => 'Kerja sama', 'batas_tanggapan' => now()->addDays(10)->toDateString()],
        ], $scan());
        $layanan->catat($tu, $jenis('SM-UMUM'), [
            'nomor_asal' => 'B-218/Rektorat/UMB/2026', 'asal' => 'Rektorat Universitas Muhammadiyah Buton', 'tgl_surat' => $hari(3), 'tgl_diterima' => $hari(3),
            'perihal' => 'Edaran Pelaksanaan Evaluasi Kinerja Dosen Semester Ganjil', 'sifat' => 'biasa', 'klasifikasi_id' => null, 'lampiran' => '2 (dua) berkas', 'isian' => [],
        ], $scan());
        $layanan->catat($tu, $jenis('SM-UMUM'), [
            'nomor_asal' => 'RHS/05/2026', 'asal' => 'Biro Kepegawaian', 'tgl_surat' => $hari(2), 'tgl_diterima' => $hari(1),
            'perihal' => 'Pemberitahuan Hasil Verifikasi Berkas Kepegawaian (Rahasia)', 'sifat' => 'rahasia', 'klasifikasi_id' => null, 'lampiran' => '', 'isian' => [],
        ], null);
    }
}
