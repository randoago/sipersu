<?php

namespace App\Console\Commands;

use App\Support\ContohQr;
use App\Services\KunciTte;
use App\Services\TandaTanganService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DokumentasiQrCode extends Command
{
    protected $signature = 'dokumentasi:qrcode {--tujuan= : berkas keluaran (bawaan: documentation/penjelasan-qrcode.pdf)} {--url=http://sipersu.ft.umbuton.ac.id/verifikasi : alamat halaman verifikasi pada QR contoh}';

    protected $description = 'Membuat PDF penjelasan QR code SIPERSU (anatomi, isi, alur verifikasi, keamanan, referensi).';

    public function handle(TandaTanganService $tte): int
    {
        if (! KunciTte::ada()) {
            $this->error('Kunci belum dibuat. Jalankan: php artisan kunci:buat');

            return self::FAILURE;
        }

        $contoh = ContohQr::buat((string) $this->option('url'));
        $level = $contoh['level'];

        $tujuan = $this->option('tujuan') ?: base_path('documentation/penjelasan-qrcode.pdf');
        File::ensureDirectoryExists(dirname($tujuan));

        $pdf = Pdf::loadView('pdf.penjelasan-qr', $contoh + [
            'qrSvg' => $tte->svgQr($contoh['url']),
            'tanggal' => now()->translatedFormat('j F Y'),
        ])->setPaper('a4', 'portrait');
        file_put_contents($tujuan, $pdf->output());

        $this->info("Dibuat: $tujuan (QR contoh: versi {$level['L']['versi']}, {$level['L']['modul']}×{$level['L']['modul']} modul, ".$contoh['panjangUrl'].' karakter)');

        return self::SUCCESS;
    }
}
