<?php

namespace App\Console\Commands;

use App\Services\SimulasiService;
use Illuminate\Console\Command;

class SimulasiSiapkan extends Command
{
    protected $signature = 'simulasi:siapkan {--yes : lewati konfirmasi}';

    protected $description = 'Mengisi aplikasi dengan surat pada SETIAP tahap alur (pengajuan, surat keluar, surat masuk) untuk simulasi/pelatihan.';

    public function handle(SimulasiService $simulasi): int
    {
        if (SimulasiService::sudahAda()) {
            $this->warn('Simulasi sudah pernah dibuat. Lihat daftar surat di aplikasi.');

            return self::SUCCESS;
        }
        $this->warn('Simulasi menandatangani surat dan MEMAKAI NOMOR URUT SURAT SUNGGUHAN. Jalankan hanya di data uji / sebelum aplikasi dipakai resmi.');
        if (! $this->option('yes') && ! $this->confirm('Lanjutkan membuat data simulasi?', false)) {
            return self::FAILURE;
        }

        $manifest = $simulasi->siapkan();
        $this->info(count($manifest).' skenario dibuat:');
        foreach ($manifest as $m) {
            $this->line(sprintf('  [%s] %-18s %s', $m['kelompok'], $m['tahap'], $m['judul']));
        }
        $this->line('Akun mahasiswa simulasi: NPM '.SimulasiService::NPM.' / password. Tahapan tiap surat terlihat pada garis waktu "Tahapan Surat" di halaman surat.');

        return self::SUCCESS;
    }
}
