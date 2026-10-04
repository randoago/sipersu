<?php

namespace App\Console\Commands;

use App\Services\KunciTte;
use App\Services\TandaTanganService;
use App\Support\ContohQr;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DokumentasiPresentasiQr extends Command
{
    protected $signature = 'dokumentasi:presentasi-qr {--tujuan= : berkas keluaran (bawaan: documentation/presentasi-qr-persuratan.pdf)} {--url=http://sipersu.ft.umbuton.ac.id/verifikasi : alamat halaman verifikasi pada QR contoh}';

    protected $description = 'Membuat PDF presentasi (slide 16:9) tentang surat ber-QR di SIPERSU.';

    public function handle(TandaTanganService $tte): int
    {
        if (! KunciTte::ada()) {
            $this->error('Kunci belum dibuat. Jalankan: php artisan kunci:buat');

            return self::FAILURE;
        }

        $contoh = ContohQr::buat((string) $this->option('url'));
        $tujuan = $this->option('tujuan') ?: base_path('documentation/presentasi-qr-persuratan.pdf');
        File::ensureDirectoryExists(dirname($tujuan));

        $pdf = Pdf::loadView('pdf.presentasi-qr', $contoh + [
            'qrSvg' => $tte->svgQr($contoh['url']),
            'tanggal' => now()->translatedFormat('j F Y'),
            'total' => 15,
        ])->setPaper([0, 0, 960, 540]);
        file_put_contents($tujuan, $pdf->output());

        $this->info("Dibuat: $tujuan (15 slide, 16:9)");

        return self::SUCCESS;
    }
}
