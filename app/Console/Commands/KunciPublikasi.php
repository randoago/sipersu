<?php

namespace App\Console\Commands;

use App\Services\KunciTte;
use Illuminate\Console\Command;

class KunciPublikasi extends Command
{
    protected $signature = 'kunci:publikasi {--tujuan= : folder keluaran (bawaan: verifikasi-offline/ di akar proyek)}';

    protected $description = 'Membuat verifikasi.html statis (kunci publik tertanam) untuk GitHub Pages';

    public function handle(): int
    {
        if (! KunciTte::ada()) {
            $this->error('Kunci belum dibuat. Jalankan: php artisan kunci:buat');

            return self::FAILURE;
        }
        $stub = file_get_contents(resource_path('stubs/verifikasi.html'));
        $nacl = file_get_contents(resource_path('stubs/nacl-fast.min.js'));
        $html = str_replace(['/*NACL*/', "'/*KUNCI_PUBLIK*/'"], [str_replace('</script', '<\/script', $nacl), "'".KunciTte::publik()."'"], $stub);

        $tujuan = rtrim($this->option('tujuan') ?: base_path('verifikasi-offline'), '/\\');
        @mkdir($tujuan, 0775, true);
        file_put_contents($tujuan.DIRECTORY_SEPARATOR.'verifikasi.html', $html);
        $this->info("Dibuat: $tujuan/verifikasi.html (".number_format(strlen($html) / 1024, 1).' KB)');
        $this->line('Unggah berkas ini ke GitHub Pages (lihat INSTALL.md), lalu isi VERIFIKASI_OFFLINE_URL di .env.');

        return self::SUCCESS;
    }
}
