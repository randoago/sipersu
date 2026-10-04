<?php

namespace App\Console\Commands;

use App\Models\LogAktivitas;
use App\Services\Backup;
use App\Services\Notifikator;
use Illuminate\Console\Command;

class BackupRun extends Command
{
    protected $signature = 'backup:run {--jenis=manual : manual | harian | mingguan} {--tujuan= : folder tujuan (khusus manual)}';

    protected $description = 'Membuat backup terenkripsi (SQLite via VACUUM INTO + storage + kunci + .env)';

    public function handle(Backup $backup): int
    {
        $jenis = $this->option('jenis');
        abort_unless(in_array($jenis, ['manual', 'harian', 'mingguan'], true), 1, 'Jenis tidak dikenal.');

        return $jenis === 'mingguan' ? $this->mingguan($backup) : $this->lokal($backup, $jenis);
    }

    private function lokal(Backup $backup, string $jenis): int
    {
        $tujuan = $jenis === 'harian'
            ? $backup->tujuanLokal()
            : ($this->option('tujuan') ?: $backup->jalur('internal'));
        if ($jenis !== 'harian' && ! is_dir($tujuan)) {
            @mkdir($tujuan, 0775, true);
        }

        try {
            if (! $tujuan) {
                throw new \RuntimeException('BACKUP_LOCAL_PATH belum diatur di .env.');
            }
            $zip = $backup->buat($tujuan);
            $hasil = $backup->verifikasi($zip);
            if (! $hasil['ok']) {
                throw new \RuntimeException('Verifikasi backup gagal: '.$hasil['pesan']);
            }
            $dihapus = $backup->pangkas($tujuan, (int) config('sipersu.backup.simpan_harian'));
            $backup->catat($jenis, $zip, $tujuan, 'sukses', $dihapus ? "$dihapus backup lama dihapus" : null);
            LogAktivitas::catat('backup', "Backup $jenis berhasil: ".basename($zip), null, [], null);
            $this->info('Backup berhasil: '.$zip);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $backup->catatGagal($jenis, (string) $tujuan, $e->getMessage());
            LogAktivitas::catat('backup_gagal', "Backup $jenis GAGAL: ".$e->getMessage(), null, [], null);
            $this->error('Backup gagal: '.$e->getMessage());
            if ($jenis === 'harian') {
                Notifikator::kirim(Notifikator::penggunaPeran(['super_admin', 'admin_tu']), 'Backup harian GAGAL', $e->getMessage(), '/pengaturan/backup', 'bahaya');
            }

            return self::FAILURE;
        }
    }

    private function mingguan(Backup $backup): int
    {
        $remote = $backup->rclone();
        if (! $remote) {
            $this->line('rclone belum terpasang/dikonfigurasi (BACKUP_RCLONE_REMOTE) — backup mingguan dilewati.');

            return self::SUCCESS;                    // dilewati tanpa galat
        }
        $tmp = $backup->jalur('internal').DIRECTORY_SEPARATOR.'mingguan-tmp';
        @mkdir($tmp, 0775, true);
        try {
            $zip = $backup->buat($tmp);
            $backup->unggahRclone($zip, $remote, (int) config('sipersu.backup.simpan_mingguan'));
            $backup->catat('mingguan', $zip, 'Google Drive: '.$remote, 'sukses');
            $this->info('Backup mingguan terunggah ke '.$remote);
            $kode = self::SUCCESS;
        } catch (\Throwable $e) {
            $backup->catatGagal('mingguan', $remote, $e->getMessage());
            $this->error('Backup mingguan gagal: '.$e->getMessage());
            $kode = self::FAILURE;
        } finally {
            foreach (glob($tmp.'/*') ?: [] as $f) {
                @unlink($f);
            }
        }

        return $kode;
    }
}
