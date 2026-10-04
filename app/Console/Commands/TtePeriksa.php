<?php

namespace App\Console\Commands;

use App\Services\KunciTte;
use Illuminate\Console\Command;

class TtePeriksa extends Command
{
    protected $signature = 'tte:periksa {isi : tautan QR lengkap, atau "payload.signature" (bagian setelah #)}';

    protected $description = 'Memeriksa tanda tangan Ed25519 pada isi QR memakai kunci publik fakultas (untuk belajar/uji)';

    public function handle(): int
    {
        $teks = trim($this->argument('isi'));
        if (($i = strpos($teks, '#')) !== false) {
            $teks = substr($teks, $i + 1);
        }
        $bagian = explode('.', $teks);
        if (count($bagian) !== 2) {
            $this->error('Format tidak dikenali. Harus "payload.signature" (dua bagian dipisah titik).');

            return self::FAILURE;
        }
        $pesan = KunciTte::dariB64url($bagian[0]);
        if ($pesan === false) {
            $this->error('Payload bukan base64url yang valid.');

            return self::FAILURE;
        }
        $this->line('Payload (isi yang ditandatangani):');
        $this->line('  '.$pesan);
        $this->newLine();
        $this->line('Signature: '.strlen((string) KunciTte::dariB64url($bagian[1])).' byte ('.strlen($bagian[1]).' karakter base64url)');
        $this->line('Kunci publik: '.KunciTte::publik());

        $sah = KunciTte::verifikasi($pesan, $bagian[1]);
        $sah ? $this->info('✔ DOKUMEN ASLI — tanda tangan cocok dengan kunci publik fakultas.') : $this->error('✘ TIDAK VALID — tanda tangan tidak cocok (isi diubah atau bukan dari fakultas).');

        return $sah ? self::SUCCESS : self::FAILURE;
    }
}
