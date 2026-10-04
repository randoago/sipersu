<?php

namespace App\Services;

use App\Models\BackupRiwayat;
use App\Models\Pengaturan;

/** Peringatan backup untuk banner dasbor Admin dan email harian. */
class StatusBackup
{
    /** @return array<int, string> daftar peringatan (kosong = sehat) */
    public static function peringatan(): array
    {
        $p = [];
        $lokal = config('sipersu.backup.lokal');
        if (! $lokal) {
            $p[] = 'Folder backup harian (BACKUP_LOCAL_PATH) belum diatur di .env.';
        } elseif (! is_dir($lokal) || ! is_writable($lokal)) {
            $p[] = "Folder tujuan backup tidak ditemukan: $lokal. Pastikan HDD eksternal/flashdisk tercolok.";
        }

        $terakhirSukses = BackupRiwayat::where('status', 'sukses')->latest()->first();
        if (! $terakhirSukses) {
            $p[] = 'Belum pernah ada backup yang berhasil.';
        } elseif ($terakhirSukses->created_at->lt(now()->subDays(2))) {
            $p[] = 'Backup terakhir yang berhasil sudah lebih dari 2 hari lalu ('.$terakhirSukses->created_at->translatedFormat('j M Y H:i').').';
        }

        $harianTerakhir = BackupRiwayat::where('jenis', 'harian')->latest()->first();
        if ($harianTerakhir && $harianTerakhir->status === 'gagal') {
            $p[] = 'Backup harian terakhir GAGAL: '.\Illuminate\Support\Str::limit((string) $harianTerakhir->pesan, 160);
        }

        return $p;
    }

    public static function perluUjiPemulihan(): bool
    {
        $t = Pengaturan::ambil('uji_pemulihan_terakhir');

        return ! $t || \Illuminate\Support\Carbon::parse($t)->lt(now()->subDays(30));
    }
}
