<?php

namespace App\Services;

use RuntimeException;

/**
 * Kunci Ed25519 milik fakultas (sodium bawaan PHP). Kunci privat disimpan di
 * storage/keys (di luar public) dan ikut dicadangkan oleh modul backup.
 */
class KunciTte
{
    public static function jalur(string $nama): string
    {
        return rtrim(config('sipersu.kunci_path'), '/\\').DIRECTORY_SEPARATOR.$nama;
    }

    public static function ada(): bool
    {
        return is_file(self::jalur('ed25519.secret')) && is_file(self::jalur('ed25519.public'));
    }

    /** @return array{public: string, secret: string} base64 standar */
    public static function buat(bool $timpa = false): array
    {
        if (self::ada() && ! $timpa) {
            throw new RuntimeException('Kunci sudah ada. Gunakan --force bila benar-benar ingin menimpa (dokumen lama tidak akan lagi bisa diverifikasi!).');
        }
        $pasangan = sodium_crypto_sign_keypair();
        $secret = base64_encode(sodium_crypto_sign_secretkey($pasangan));
        $public = base64_encode(sodium_crypto_sign_publickey($pasangan));

        if (! is_dir(config('sipersu.kunci_path'))) {
            mkdir(config('sipersu.kunci_path'), 0700, true);
        }
        file_put_contents(self::jalur('ed25519.secret'), $secret);
        @chmod(self::jalur('ed25519.secret'), 0600);
        file_put_contents(self::jalur('ed25519.public'), $public);

        return ['public' => $public, 'secret' => $secret];
    }

    public static function publik(): string
    {
        return trim((string) @file_get_contents(self::jalur('ed25519.public'))) ?: throw new RuntimeException('Kunci publik belum dibuat. Jalankan: php artisan kunci:buat');
    }

    private static function rahasia(): string
    {
        $b64 = trim((string) @file_get_contents(self::jalur('ed25519.secret')));
        if ($b64 === '') {
            throw new RuntimeException('Kunci privat belum dibuat. Jalankan: php artisan kunci:buat');
        }

        return base64_decode($b64, true);
    }

    /** Tanda tangan terpisah (64 byte), dikembalikan sebagai base64url. */
    public static function tandatangani(string $pesan): string
    {
        return self::b64url(sodium_crypto_sign_detached($pesan, self::rahasia()));
    }

    public static function verifikasi(string $pesan, string $signatureB64url, ?string $publikB64 = null): bool
    {
        $sig = self::dariB64url($signatureB64url);
        $pub = base64_decode($publikB64 ?? self::publik(), true);
        if ($sig === false || strlen($sig) !== SODIUM_CRYPTO_SIGN_BYTES || $pub === false || strlen($pub) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($sig, $pesan, $pub);
    }

    public static function b64url(string $biner): string
    {
        return rtrim(strtr(base64_encode($biner), '+/', '-_'), '=');
    }

    public static function dariB64url(string $teks): string|false
    {
        return base64_decode(strtr($teks, '-_', '+/').str_repeat('=', (4 - strlen($teks) % 4) % 4), true);
    }
}
