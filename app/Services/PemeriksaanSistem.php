<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Pemeriksaan kesehatan sistem untuk deteksi dini sebelum aplikasi "mati": basis data, migrasi, kunci tanda tangan,
 * folder tulis, ruang disk, antrean gagal, pengaturan produksi, dan backup. Dipakai `php artisan sipersu:periksa`
 * (dijadwalkan tiap jam; masalah KRITIS dikirim ke Admin sekali per hari).
 *
 * @phpstan-type Hasil array{nama: string, status: string, pesan: string}
 */
class PemeriksaanSistem
{
    public const OK = 'ok';

    public const PERINGATAN = 'peringatan';

    public const KRITIS = 'kritis';

    /** Sisa ruang disk minimum (byte) sebelum dianggap peringatan / kritis. */
    private const DISK_PERINGATAN = 2 * 1024 ** 3;

    private const DISK_KRITIS = 512 * 1024 ** 2;

    /** @return array<int, array{nama: string, status: string, pesan: string}> */
    public function jalankan(): array
    {
        return [
            $this->basisData(), $this->migrasi(), $this->kunciTte(), $this->folderTulis(), $this->ruangDisk(),
            $this->antreanGagal(), $this->pengaturanProduksi(), $this->backup(),
        ];
    }

    public static function terburuk(array $hasil): string
    {
        $s = array_column($hasil, 'status');

        return in_array(self::KRITIS, $s, true) ? self::KRITIS : (in_array(self::PERINGATAN, $s, true) ? self::PERINGATAN : self::OK);
    }

    private function hasil(string $nama, string $status, string $pesan): array
    {
        return compact('nama', 'status', 'pesan');
    }

    private function basisData(): array
    {
        try {
            DB::select('select 1');
            if (DB::getDriverName() === 'sqlite') {
                $cek = DB::select('PRAGMA quick_check')[0]->quick_check ?? 'tidak diketahui';
                if ($cek !== 'ok') {
                    return $this->hasil('Basis data', self::KRITIS, "SQLite melaporkan kerusakan: {$cek}. Pulihkan dari backup (documentation/pemulihan.md).");
                }
            }

            return $this->hasil('Basis data', self::OK, 'Terhubung dan utuh.');
        } catch (\Throwable $e) {
            return $this->hasil('Basis data', self::KRITIS, 'Tidak dapat dibuka: '.\Illuminate\Support\Str::limit($e->getMessage(), 120));
        }
    }

    private function migrasi(): array
    {
        try {
            $belum = collect(app('migrator')->getMigrationFiles(database_path('migrations')))->keys()
                ->diff(DB::table('migrations')->pluck('migration'))->count();

            return $belum === 0 ? $this->hasil('Migrasi', self::OK, 'Semua migrasi sudah dijalankan.')
                : $this->hasil('Migrasi', self::KRITIS, "{$belum} migrasi belum dijalankan. Jalankan: php artisan migrate --force");
        } catch (\Throwable $e) {
            return $this->hasil('Migrasi', self::KRITIS, 'Tabel migrasi tidak terbaca: '.\Illuminate\Support\Str::limit($e->getMessage(), 100));
        }
    }

    private function kunciTte(): array
    {
        try {
            $uji = 'periksa-'.bin2hex(random_bytes(4));

            return KunciTte::verifikasi($uji, KunciTte::tandatangani($uji))
                ? $this->hasil('Kunci tanda tangan', self::OK, 'Kunci privat dan publik berpasangan.')
                : $this->hasil('Kunci tanda tangan', self::KRITIS, 'Kunci privat dan publik tidak berpasangan.');
        } catch (\Throwable $e) {
            return $this->hasil('Kunci tanda tangan', self::KRITIS, 'Kunci tidak dapat dipakai (hilang atau rusak): pulihkan dari salinan kunci atau backup.');
        }
    }

    private function folderTulis(): array
    {
        $buruk = [];
        foreach ([storage_path('app'), storage_path('logs'), storage_path('framework/cache'), storage_path('framework/views'), base_path('bootstrap/cache')] as $f) {
            if (! is_dir($f) || ! is_writable($f)) {
                $buruk[] = basename(dirname($f)).'/'.basename($f);
            }
        }

        return $buruk ? $this->hasil('Folder tulis', self::KRITIS, 'Tidak dapat ditulis: '.implode(', ', $buruk))
            : $this->hasil('Folder tulis', self::OK, 'Folder storage dan cache dapat ditulis.');
    }

    private function ruangDisk(): array
    {
        $sisa = @disk_free_space(base_path());
        if ($sisa === false) {
            return $this->hasil('Ruang disk', self::PERINGATAN, 'Sisa ruang disk tidak dapat dibaca.');
        }
        $gb = number_format($sisa / 1024 ** 3, 1, ',', '.').' GB';

        return match (true) {
            $sisa < self::DISK_KRITIS => $this->hasil('Ruang disk', self::KRITIS, "Sisa hanya {$gb}. Bebaskan ruang sekarang atau aplikasi dan backup akan gagal."),
            $sisa < self::DISK_PERINGATAN => $this->hasil('Ruang disk', self::PERINGATAN, "Sisa {$gb}; segera bebaskan ruang."),
            default => $this->hasil('Ruang disk', self::OK, "Sisa {$gb}."),
        };
    }

    private function antreanGagal(): array
    {
        try {
            $n = DB::table('failed_jobs')->count();

            return $n > 20 ? $this->hasil('Antrean email', self::PERINGATAN, "{$n} tugas gagal (cek konfigurasi MAIL_* atau koneksi).")
                : $this->hasil('Antrean email', self::OK, "{$n} tugas gagal.");
        } catch (\Throwable) {
            return $this->hasil('Antrean email', self::OK, 'Tabel antrean tidak tersedia.');
        }
    }

    private function pengaturanProduksi(): array
    {
        if (app()->environment('production') && config('app.debug')) {
            return $this->hasil('Pengaturan produksi', self::KRITIS, 'APP_DEBUG=true di produksi membocorkan rincian galat. Ubah ke false.');
        }
        if (app()->environment('production') && ! config('sipersu.batas_laju.aktif')) {
            return $this->hasil('Pengaturan produksi', self::PERINGATAN, 'Pembatas banjir akses dimatikan (LAJU_AKTIF=false).');
        }

        return $this->hasil('Pengaturan produksi', self::OK, 'Sesuai.');
    }

    private function backup(): array
    {
        try {
            $masalah = StatusBackup::peringatan();
            if ($masalah) {
                return $this->hasil('Backup', self::PERINGATAN, implode(' ', $masalah));
            }
            if (StatusBackup::perluUjiPemulihan()) {
                return $this->hasil('Backup', self::PERINGATAN, 'Sudah lebih dari 30 hari sejak uji pemulihan terakhir (scripts\\uji-pemulihan.bat).');
            }

            return $this->hasil('Backup', self::OK, 'Backup terakhir berhasil dan uji pemulihan masih baru.');
        } catch (\Throwable $e) {
            return $this->hasil('Backup', self::PERINGATAN, 'Status backup tidak terbaca: '.\Illuminate\Support\Str::limit($e->getMessage(), 100));
        }
    }
}
