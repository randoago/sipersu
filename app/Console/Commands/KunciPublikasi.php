<?php

namespace App\Console\Commands;

use App\Services\KunciTte;
use Illuminate\Console\Command;

class KunciPublikasi extends Command
{
    protected $signature = 'kunci:publikasi {--tujuan= : folder keluaran (bawaan: verifikasi-statis/ di akar proyek)}';

    protected $description = 'Membuat verifikasi-statis/index.html (kunci publik tertanam; tanpa server) untuk QR / GitHub Pages';

    public function handle(): int
    {
        if (! KunciTte::ada()) {
            $this->error('Kunci belum dibuat. Jalankan: php artisan kunci:buat');

            return self::FAILURE;
        }
        $stub = file_get_contents(resource_path('stubs/verifikasi.html'));
        $nacl = file_get_contents(resource_path('stubs/nacl-fast.min.js'));
        $html = str_replace(['/*NACL*/', "'/*KUNCI_PUBLIK*/'"], [str_replace('</script', '<\/script', $nacl), "'".KunciTte::publik()."'"], $stub);

        $tujuan = rtrim($this->option('tujuan') ?: base_path('verifikasi-statis'), '/\\');
        @mkdir($tujuan, 0775, true);
        file_put_contents($tujuan.DIRECTORY_SEPARATOR.'index.html', $html);
        $this->info("Dibuat: $tujuan/index.html (".number_format(strlen($html) / 1024, 1).' KB)');
        $this->line('Tanpa internet: berkas ini dilayani aplikasi di /verifikasi. Opsional: unggah ke GitHub Pages lalu isi VERIFIKASI_URL di .env (INSTALL.md bagian 7).');

        return self::SUCCESS;
    }
}
