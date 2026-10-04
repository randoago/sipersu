<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use App\Services\Notifikator;
use App\Services\StatusBackup;
use Illuminate\Console\Command;

class BackupPeriksa extends Command
{
    protected $signature = 'backup:periksa';

    protected $description = 'Memeriksa kesehatan backup; kirim email ke Admin bila ada masalah (maks. sekali per hari)';

    public function handle(): int
    {
        $masalah = StatusBackup::peringatan();
        if (! $masalah) {
            $this->info('Backup sehat.');

            return self::SUCCESS;
        }
        $this->warn(implode("\n", $masalah));
        if (Pengaturan::ambil('backup_peringatan_terakhir') !== now()->toDateString()) {
            Notifikator::kirim(Notifikator::penggunaPeran(['super_admin', 'admin_tu']), 'Peringatan backup SIPERSU', implode(' • ', $masalah), '/pengaturan/backup', 'bahaya');
            Pengaturan::simpan('backup_peringatan_terakhir', now()->toDateString());
        }

        return self::FAILURE;
    }
}
