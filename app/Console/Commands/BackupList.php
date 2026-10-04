<?php

namespace App\Console\Commands;

use App\Models\BackupRiwayat;
use Illuminate\Console\Command;

class BackupList extends Command
{
    protected $signature = 'backup:list {--n=20 : jumlah baris}';

    protected $description = 'Menampilkan riwayat backup';

    public function handle(): int
    {
        $baris = BackupRiwayat::latest()->limit((int) $this->option('n'))->get()->map(fn ($b) => [
            $b->created_at->format('Y-m-d H:i'), $b->jenis, $b->nama_berkas, number_format($b->ukuran / 1048576, 2, ',', '.').' MB', $b->lokasi, $b->status,
        ]);
        $this->table(['Waktu', 'Jenis', 'Berkas', 'Ukuran', 'Lokasi', 'Status'], $baris);

        return self::SUCCESS;
    }
}
