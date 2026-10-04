<?php

namespace App\Console\Commands;

use App\Services\KunciTte;
use Illuminate\Console\Command;

class KunciBuat extends Command
{
    protected $signature = 'kunci:buat {--force : Timpa kunci yang sudah ada (BERBAHAYA)}';

    protected $description = 'Membuat pasangan kunci Ed25519 fakultas untuk tanda tangan elektronik';

    public function handle(): int
    {
        if (KunciTte::ada() && $this->option('force') && ! $this->confirm('Menimpa kunci membuat SEMUA QR lama tidak valid secara offline. Lanjutkan?')) {
            return self::FAILURE;
        }
        try {
            $k = KunciTte::buat((bool) $this->option('force'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info('Kunci dibuat di '.config('sipersu.kunci_path'));
        $this->line('Kunci publik (base64): '.$k['public']);
        $this->warn('Segera jalankan: php artisan kunci:cadangkan <folder-tujuan>  — dan simpan salinannya di tempat aman.');
        $this->line('Lalu: php artisan kunci:publikasi  untuk membuat verifikasi-offline/verifikasi.html');

        return self::SUCCESS;
    }
}
