<?php

namespace App\Console\Commands;

use App\Services\Backup;
use Illuminate\Console\Command;

class BackupRestore extends Command
{
    protected $signature = 'backup:restore {file : jalur ZIP backup}
        {--password= : kata sandi backup (bila BACKUP_PASSWORD belum ada di .env)}
        {--uji-saja : hanya ekstrak ke folder uji dan periksa, tanpa menimpa data aktif}
        {--termasuk-env : ikut memulihkan berkas .env (untuk laptop baru)}
        {--yes : lewati konfirmasi}';

    protected $description = 'Memulihkan backup: ke folder uji dulu, lalu (setelah konfirmasi) menimpa data aktif';

    public function handle(Backup $backup): int
    {
        $file = $this->argument('file');
        $password = $this->option('password') ?: config('sipersu.backup.password');
        if (! $password && $this->input->isInteractive()) {
            $password = $this->secret('Kata sandi backup (BACKUP_PASSWORD)');
        }
        config(['sipersu.backup.password' => $password]);

        $uji = null;
        try {
            $this->info('1/4 Memeriksa checksum dan membuka ZIP …');
            $uji = $backup->ekstrakKeUji($file, $password);
            $this->info("2/4 Diekstrak ke folder uji: $uji (integritas SQLite: ok)");
            $this->tampilkanRingkasan($uji);

            if ($this->option('uji-saja')) {
                \App\Models\Pengaturan::simpan('uji_pemulihan_terakhir', now()->toDateTimeString());
                $this->info('Uji pemulihan selesai. Data aktif TIDAK diubah.');

                return self::SUCCESS;
            }

            if (! $this->option('yes') && ! $this->confirm('Ini akan MENIMPA database, storage/app, dan kunci aktif. Lanjutkan?', false)) {
                $this->warn('Dibatalkan. Data aktif tidak diubah.');

                return self::FAILURE;
            }

            $this->info('3/4 Membuat backup kondisi saat ini sebelum menimpa …');
            @mkdir($backup->jalur('internal'), 0775, true);
            $pra = $backup->buat($backup->jalur('internal'), now()->subSecond());
            $this->line("    Cadangan pra-pemulihan: $pra");

            $backup->terapkan($uji, (bool) $this->option('termasuk-env'));
            $this->info('4/4 Pemulihan selesai. Jalankan: php artisan about  dan buka aplikasi untuk memeriksa.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Pemulihan gagal: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            // Folder uji berisi data hasil dekripsi (termasuk .env dan kunci): selalu dihapus.
            if ($uji && is_dir($uji)) {
                \Illuminate\Support\Facades\File::deleteDirectory($uji);
            }
        }
    }

    private function tampilkanRingkasan(string $folder): void
    {
        $pdo = new \PDO('sqlite:'.$folder.'/database/sipersu.sqlite');
        $this->table(['Tabel', 'Baris'], collect(['users', 'pengajuan', 'surat', 'lampiran'])->map(
            fn ($t) => [$t, (int) $pdo->query("select count(*) from $t")->fetchColumn()]
        )->all());
    }
}
