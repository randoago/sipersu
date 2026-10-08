<?php

namespace App\Support;

use App\Services\KunciTte;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/** Contoh QR bertanda tangan sungguhan (kunci fakultas) untuk dokumentasi: PDF penjelasan dan presentasi. */
class ContohQr
{
    /** @return array{url: string, urlDasar: string, payload: string, payloadB64: string, signature: string, panjangUrl: int, level: array<string, array{versi: int, modul: int}>} */
    public static function buat(string $urlDasar): array
    {
        $payload = json_encode([
            'v' => 1,
            'n' => '001/KET/II.3.AU/UMB-06/F/2026',
            'p' => 'Surat Keterangan Aktif Kuliah',
            's' => 'Agusman, S.T., MM.',
            'j' => 'Dekan Fakultas Teknik',
            't' => '2026-10-04',
            'h' => hash('sha256', 'contoh-isi-surat'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = KunciTte::b64url(KunciTte::tandatangani($payload));
        $dasar = rtrim($urlDasar, '/');
        $url = $dasar.'#'.KunciTte::b64url($payload).'.'.$signature;

        $level = [];
        foreach (['L', 'M', 'Q', 'H'] as $k) {
            $e = Encoder::encode($url, ErrorCorrectionLevel::{$k}(), 'UTF-8');
            $level[$k] = ['versi' => $e->getVersion()->getVersionNumber(), 'modul' => $e->getMatrix()->getWidth()];
        }

        return [
            'url' => $url, 'urlDasar' => $dasar, 'payload' => $payload, 'payloadB64' => KunciTte::b64url($payload),
            'signature' => $signature, 'panjangUrl' => strlen($url), 'level' => $level,
        ];
    }
}
