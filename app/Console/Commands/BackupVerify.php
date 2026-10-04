<?php

namespace App\Console\Commands;

use App\Services\Backup;
use Illuminate\Console\Command;

class BackupVerify extends Command
{
    protected $signature = 'backup:verify {file? : jalur ZIP (bawaan: backup terbaru)} {--password= : kata sandi backup}';

    protected $description = 'Memeriksa checksum SHA-256 dan mencoba membuka ZIP backup';

    public function handle(Backup $backup): int
    {
        $file = $this->argument('file') ?: $this->terbaru($backup);
        if (! $file) {
            $this->error('Tidak ada backup ditemukan.');

            return self::FAILURE;
        }
        if ($p = $this->option('password')) {
            config(['sipersu.backup.password' => $p]);
        }
        $hasil = $backup->verifikasi($file);
        $this->line($file);
        $hasil['ok'] ? $this->info($hasil['pesan']) : $this->error($hasil['pesan']);

        return $hasil['ok'] ? self::SUCCESS : self::FAILURE;
    }

    private function terbaru(Backup $backup): ?string
    {
        foreach (array_filter([$backup->tujuanLokal(), $backup->jalur('internal')]) as $folder) {
            $daftar = glob($folder.DIRECTORY_SEPARATOR.'sipersu-*.zip') ?: [];
            rsort($daftar);
            if ($daftar) {
                return $daftar[0];
            }
        }

        return null;
    }
}
