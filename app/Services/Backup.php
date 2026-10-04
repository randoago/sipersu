<?php

namespace App\Services;

use App\Models\BackupRiwayat;
use App\Models\Pengaturan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Backup terenkripsi (ZIP AES-256) berisi: salinan SQLite (VACUUM INTO — bukan salin berkas mentah),
 * storage/app, storage/keys, dan .env. Dilengkapi berkas checksum SHA-256.
 */
class Backup
{
    public function __construct(private ?string $koneksi = null, private ?array $jalur = null)
    {
        $this->koneksi ??= config('database.default');
        $this->jalur ??= [
            'db' => database_path('sipersu.sqlite'),
            'app' => storage_path('app'),
            'keys' => config('sipersu.kunci_path'),
            'env' => base_path('.env'),
            'internal' => storage_path('backups'),         // manual & cadangan pra-pemulihan
            'tmp' => storage_path('framework/backup-tmp'),
            'uji' => storage_path('restore-uji'),
        ];
    }

    public function jalur(string $k): string
    {
        return $this->jalur[$k];
    }

    public function password(): string
    {
        $p = (string) config('sipersu.backup.password');
        if (strlen($p) < 8) {
            throw new RuntimeException('BACKUP_PASSWORD di .env belum diisi (minimal 8 karakter).');
        }

        return $p;
    }

    public static function namaBerkas(?\DateTimeInterface $waktu = null): string
    {
        return 'sipersu-'.($waktu ?? now())->format('Y-m-d-Hi').'.zip';
    }

    /** Folder tujuan harian (HDD eksternal). Null bila belum diatur. */
    public function tujuanLokal(): ?string
    {
        $t = config('sipersu.backup.lokal');

        return $t ? rtrim($t, '/\\') : null;
    }

    public function tujuanTersedia(?string $tujuan): bool
    {
        return $tujuan !== null && is_dir($tujuan) && is_writable($tujuan);
    }

    /**
     * Membuat backup di folder $tujuan. Mengembalikan jalur ZIP. Melempar RuntimeException bila gagal.
     */
    public function buat(string $tujuan, ?\DateTimeInterface $waktu = null): string
    {
        $password = $this->password();
        if (! $this->tujuanTersedia($tujuan)) {
            throw new RuntimeException("Folder tujuan backup tidak ditemukan / tidak bisa ditulisi: $tujuan (HDD eksternal belum tercolok?)");
        }
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP zip tidak aktif.');
        }

        File::ensureDirectoryExists($this->jalur['tmp']);
        $salinanDb = $this->jalur['tmp'].DIRECTORY_SEPARATOR.'sipersu-'.bin2hex(random_bytes(4)).'.sqlite';
        $zipPath = $tujuan.DIRECTORY_SEPARATOR.self::namaBerkas($waktu);
        $zipSementara = $zipPath.'.tmp';

        try {
            DB::connection($this->koneksi)->statement('VACUUM INTO '.DB::connection($this->koneksi)->getPdo()->quote($salinanDb));

            $zip = new ZipArchive;
            if ($zip->open($zipSementara, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Gagal membuat berkas ZIP.');
            }
            $zip->setPassword($password);
            $this->tambah($zip, $salinanDb, 'database/sipersu.sqlite', $password);
            $this->tambahFolder($zip, $this->jalur['app'], 'storage/app', $password);
            $this->tambahFolder($zip, $this->jalur['keys'], 'storage/keys', $password);
            if (is_file($this->jalur['env'])) {
                $this->tambah($zip, $this->jalur['env'], '.env', $password);
            }
            if (! $zip->close()) {
                throw new RuntimeException('Gagal menulis berkas ZIP (disk penuh?).');
            }
            if (! @rename($zipSementara, $zipPath)) {
                throw new RuntimeException('Gagal memindahkan backup ke folder tujuan.');
            }
            file_put_contents($zipPath.'.sha256', hash_file('sha256', $zipPath).'  '.basename($zipPath).PHP_EOL);
        } catch (\Throwable $e) {
            @unlink($zipSementara);
            @unlink($zipPath);
            throw $e instanceof RuntimeException ? $e : new RuntimeException($e->getMessage(), 0, $e);
        } finally {
            @unlink($salinanDb);
        }

        return $zipPath;
    }

    private function tambah(ZipArchive $zip, string $sumber, string $nama, string $password): void
    {
        $zip->addFile($sumber, $nama);
        $zip->setEncryptionName($nama, ZipArchive::EM_AES_256, $password);
    }

    private function tambahFolder(ZipArchive $zip, string $folder, string $awalan, string $password): void
    {
        if (! is_dir($folder)) {
            return;
        }
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS));
        foreach ($iter as $berkas) {
            if ($berkas->isFile() && $berkas->getFilename() !== '.gitignore') {
                $rel = str_replace('\\', '/', substr($berkas->getPathname(), strlen(rtrim($folder, '/\\')) + 1));
                $this->tambah($zip, $berkas->getPathname(), $awalan.'/'.$rel, $password);
            }
        }
    }

    /** @return array{ok: bool, pesan: string} */
    public function verifikasi(string $zipPath, ?string $password = null): array
    {
        if (! is_file($zipPath)) {
            return ['ok' => false, 'pesan' => 'Berkas tidak ditemukan.'];
        }
        $sha = $zipPath.'.sha256';
        if (is_file($sha)) {
            $dicatat = strtok(trim(file_get_contents($sha)), ' ');
            if (! hash_equals($dicatat, hash_file('sha256', $zipPath))) {
                return ['ok' => false, 'pesan' => 'Checksum SHA-256 TIDAK cocok — berkas rusak atau diubah.'];
            }
        } else {
            return ['ok' => false, 'pesan' => 'Berkas checksum (.sha256) tidak ditemukan.'];
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'pesan' => 'ZIP tidak dapat dibuka.'];
        }
        $zip->setPassword($password ?? $this->password());
        $db = $zip->getFromName('database/sipersu.sqlite');
        $jumlah = $zip->numFiles;
        $zip->close();
        if ($db === false || strncmp($db, 'SQLite format 3', 15) !== 0) {
            return ['ok' => false, 'pesan' => 'Isi tidak dapat dibaca (kata sandi backup salah?) atau berkas SQLite hilang.'];
        }

        return ['ok' => true, 'pesan' => "Checksum cocok, ZIP terbuka, SQLite valid ($jumlah berkas)."];
    }

    /** Ekstrak ke folder uji dan periksa integritas SQLite. Mengembalikan folder hasil. */
    public function ekstrakKeUji(string $zipPath, ?string $password = null): string
    {
        $hasil = $this->verifikasi($zipPath, $password);
        if (! $hasil['ok']) {
            throw new RuntimeException($hasil['pesan']);
        }
        $tujuan = $this->jalur['uji'].DIRECTORY_SEPARATOR.now()->format('Ymd-His');
        File::ensureDirectoryExists($tujuan);
        $zip = new ZipArchive;
        $zip->open($zipPath);
        $zip->setPassword($password ?? $this->password());
        if (! $zip->extractTo($tujuan)) {
            $zip->close();
            throw new RuntimeException('Gagal mengekstrak backup ke folder uji.');
        }
        $zip->close();

        $pdo = new \PDO('sqlite:'.$tujuan.'/database/sipersu.sqlite');
        $cek = $pdo->query('PRAGMA integrity_check')->fetchColumn();
        $pdo = null;
        if ($cek !== 'ok') {
            throw new RuntimeException('Pemeriksaan integritas SQLite gagal: '.$cek);
        }

        return $tujuan;
    }

    /** Menimpa data aktif dari folder hasil ekstrak. Panggil SETELAH backup pra-pemulihan. */
    public function terapkan(string $folderUji, bool $termasukEnv = false): void
    {
        DB::disconnect($this->koneksi);
        foreach (['', '-wal', '-shm'] as $akhiran) {
            @unlink($this->jalur['db'].$akhiran);
        }
        copy($folderUji.'/database/sipersu.sqlite', $this->jalur['db']);

        $this->gantiFolder($folderUji.'/storage/app', $this->jalur['app']);
        $this->gantiFolder($folderUji.'/storage/keys', $this->jalur['keys']);
        @chmod(rtrim($this->jalur['keys'], '/\\').'/ed25519.secret', 0600);

        if ($termasukEnv && is_file($folderUji.'/.env')) {
            if (is_file($this->jalur['env'])) {
                copy($this->jalur['env'], $this->jalur['env'].'.sebelum-restore');
            }
            copy($folderUji.'/.env', $this->jalur['env']);
        }
        Pengaturan::simpan('uji_pemulihan_terakhir', now()->toDateTimeString());
    }

    private function gantiFolder(string $dari, string $ke): void
    {
        if (! is_dir($dari)) {
            return;
        }
        File::ensureDirectoryExists($ke);
        File::cleanDirectory($ke);
        File::copyDirectory($dari, $ke);
    }

    /** Hapus backup lama di $folder, sisakan $simpan terbaru. */
    public function pangkas(string $folder, int $simpan): int
    {
        $daftar = glob(rtrim($folder, '/\\').DIRECTORY_SEPARATOR.'sipersu-*.zip') ?: [];
        rsort($daftar);              // nama berformat tanggal → urut terbaru dulu
        $hapus = 0;
        foreach (array_slice($daftar, $simpan) as $lama) {
            @unlink($lama);
            @unlink($lama.'.sha256');
            $hapus++;
        }

        return $hapus;
    }

    // ---- rclone (Google Drive) --------------------------------------------------------------------------

    public function rclone(): ?string
    {
        $remote = config('sipersu.backup.rclone_remote');
        if (! $remote) {
            return null;
        }
        try {
            $p = new Process(['rclone', 'listremotes']);
            $p->setTimeout(20)->run();
        } catch (\Throwable) {
            return null;                       // rclone tidak terpasang
        }
        $nama = explode(':', $remote)[0].':';

        return $p->isSuccessful() && str_contains($p->getOutput(), $nama) ? $remote : null;
    }

    public function unggahRclone(string $zipPath, string $remote, int $simpan): void
    {
        $remote = rtrim($remote, '/');
        foreach ([$zipPath, $zipPath.'.sha256'] as $b) {
            $p = new Process(['rclone', 'copy', $b, $remote]);
            $p->setTimeout(1800)->run();
            if (! $p->isSuccessful()) {
                throw new RuntimeException('rclone gagal: '.trim($p->getErrorOutput()));
            }
        }
        $ls = new Process(['rclone', 'lsf', $remote, '--include', 'sipersu-*.zip']);
        $ls->setTimeout(60)->run();
        $berkas = array_filter(explode("\n", trim($ls->getOutput())));
        rsort($berkas);
        foreach (array_slice($berkas, $simpan) as $lama) {
            (new Process(['rclone', 'deletefile', $remote.'/'.$lama]))->run();
            (new Process(['rclone', 'deletefile', $remote.'/'.$lama.'.sha256']))->run();
        }
    }

    // ---- riwayat & status -------------------------------------------------------------------------------

    public function catat(string $jenis, string $zipPath, string $lokasi, string $status, ?string $pesan = null, ?int $userId = null): BackupRiwayat
    {
        return BackupRiwayat::create([
            'jenis' => $jenis, 'nama_berkas' => basename($zipPath), 'lokasi' => $lokasi,
            'ukuran' => is_file($zipPath) ? filesize($zipPath) : 0,
            'checksum' => is_file($zipPath) ? hash_file('sha256', $zipPath) : null,
            'status' => $status, 'pesan' => $pesan, 'dibuat_oleh' => $userId,
        ]);
    }

    public function catatGagal(string $jenis, string $lokasi, string $pesan, ?int $userId = null): BackupRiwayat
    {
        return BackupRiwayat::create(['jenis' => $jenis, 'nama_berkas' => '-', 'lokasi' => $lokasi, 'ukuran' => 0, 'status' => 'gagal', 'pesan' => $pesan, 'dibuat_oleh' => $userId]);
    }
}
