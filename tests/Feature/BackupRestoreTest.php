<?php

namespace Tests\Feature;

use App\Models\BackupRiwayat;
use App\Services\Backup;
use App\Services\StatusBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    private string $akar;

    private Backup $backup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->akar = sys_get_temp_dir().'/sipersu-bk-'.getmypid().'-'.bin2hex(random_bytes(3));
        foreach (['db', 'app/lampiran', 'keys', 'tujuan', 'internal', 'tmp', 'uji'] as $d) {
            mkdir($this->akar.'/'.$d, 0775, true);
        }
        $db = $this->akar.'/db/sipersu.sqlite';
        touch($db);
        config(['database.connections.ujibackup' => ['driver' => 'sqlite', 'database' => $db, 'foreign_key_constraints' => true, 'journal_mode' => 'WAL', 'busy_timeout' => 3000]]);
        DB::connection('ujibackup')->statement('create table users (id integer primary key, nama text)');
        DB::connection('ujibackup')->statement('create table pengajuan (id integer primary key)');
        DB::connection('ujibackup')->statement('create table surat (id integer primary key)');
        DB::connection('ujibackup')->statement('create table lampiran (id integer primary key)');
        DB::connection('ujibackup')->table('users')->insert([['nama' => 'Fauzan'], ['nama' => 'Agusman']]);

        file_put_contents($this->akar.'/app/lampiran/ktm.pdf', '%PDF-ktm-asli');
        file_put_contents($this->akar.'/keys/ed25519.secret', 'RAHASIA');
        file_put_contents($this->akar.'/.env', "APP_KEY=base64:abc\n");

        config(['sipersu.backup.password' => 'sandi-backup-uji-123']);
        $this->backup = new Backup('ujibackup', [
            'db' => $db, 'app' => $this->akar.'/app', 'keys' => $this->akar.'/keys', 'env' => $this->akar.'/.env',
            'internal' => $this->akar.'/internal', 'tmp' => $this->akar.'/tmp', 'uji' => $this->akar.'/uji',
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('ujibackup');
        File::deleteDirectory($this->akar);
        parent::tearDown();
    }

    public function test_backup_membuat_zip_terenkripsi_dan_checksum_yang_valid(): void
    {
        $zip = $this->backup->buat($this->akar.'/tujuan');

        $this->assertMatchesRegularExpression('/sipersu-\d{4}-\d{2}-\d{2}-\d{4}\.zip$/', $zip);
        $this->assertFileExists($zip.'.sha256');
        $this->assertSame(hash_file('sha256', $zip), strtok(file_get_contents($zip.'.sha256'), ' '));
        $this->assertTrue($this->backup->verifikasi($zip)['ok']);

        // terenkripsi: tanpa/ salah sandi isi tidak terbaca
        $za = new \ZipArchive;
        $za->open($zip);
        $this->assertFalse($za->getFromName('database/sipersu.sqlite'));
        $za->setPassword('sandi-salah-sekali');
        $this->assertFalse($za->getFromName('.env'));
        $za->close();
        config(['sipersu.backup.password' => 'sandi-salah-sekali']);
        $this->assertFalse($this->backup->verifikasi($zip)['ok']);
    }

    public function test_isi_zip_lengkap_dan_sqlite_adalah_snapshot_konsisten(): void
    {
        DB::connection('ujibackup')->table('users')->insert(['nama' => 'Baru-di-WAL']);   // belum tentu sudah di-checkpoint
        $zip = $this->backup->buat($this->akar.'/tujuan');

        $folder = $this->backup->ekstrakKeUji($zip);
        $this->assertFileExists($folder.'/storage/app/lampiran/ktm.pdf');
        $this->assertFileExists($folder.'/storage/keys/ed25519.secret');
        $this->assertFileExists($folder.'/.env');
        $pdo = new \PDO('sqlite:'.$folder.'/database/sipersu.sqlite');
        $this->assertSame(3, (int) $pdo->query('select count(*) from users')->fetchColumn());
    }

    public function test_checksum_mendeteksi_berkas_rusak(): void
    {
        $zip = $this->backup->buat($this->akar.'/tujuan');
        file_put_contents($zip, 'x', FILE_APPEND);
        $hasil = $this->backup->verifikasi($zip);
        $this->assertFalse($hasil['ok']);
        $this->assertStringContainsString('Checksum', $hasil['pesan']);
    }

    public function test_restore_memulihkan_data_yang_hilang(): void
    {
        $zip = $this->backup->buat($this->akar.'/tujuan');

        // "bencana": data dihapus dan berkas hilang
        DB::connection('ujibackup')->table('users')->delete();
        unlink($this->akar.'/app/lampiran/ktm.pdf');
        unlink($this->akar.'/keys/ed25519.secret');
        $this->assertSame(0, DB::connection('ujibackup')->table('users')->count());

        $folder = $this->backup->ekstrakKeUji($zip);
        $this->backup->terapkan($folder, termasukEnv: true);

        $this->assertSame(2, DB::connection('ujibackup')->table('users')->count());
        $this->assertSame('%PDF-ktm-asli', file_get_contents($this->akar.'/app/lampiran/ktm.pdf'));
        $this->assertSame('RAHASIA', file_get_contents($this->akar.'/keys/ed25519.secret'));
    }

    public function test_uji_saja_tidak_mengubah_data_aktif(): void
    {
        $zip = $this->backup->buat($this->akar.'/tujuan');
        DB::connection('ujibackup')->table('users')->insert(['nama' => 'Setelah backup']);
        $this->backup->ekstrakKeUji($zip);
        $this->assertSame(3, DB::connection('ujibackup')->table('users')->count());
    }

    public function test_retensi_menyisakan_n_backup_terakhir(): void
    {
        foreach (range(1, 35) as $i) {
            $nama = $this->akar.'/tujuan/sipersu-2026-09-'.sprintf('%02d', min($i, 28)).'-'.sprintf('%04d', $i).'.zip';
            file_put_contents($nama, 'x');
            file_put_contents($nama.'.sha256', 'x');
        }
        $this->assertSame(5, $this->backup->pangkas($this->akar.'/tujuan', 30));
        $this->assertCount(30, glob($this->akar.'/tujuan/sipersu-*.zip'));
        $this->assertCount(30, glob($this->akar.'/tujuan/sipersu-*.sha256'));
    }

    public function test_folder_tujuan_tidak_ada_gagal_dan_tanpa_password_gagal(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tidak ditemukan');
        $this->backup->buat($this->akar.'/hdd-tidak-tercolok');
    }

    public function test_password_kosong_ditolak(): void
    {
        config(['sipersu.backup.password' => '']);
        $this->expectExceptionMessage('BACKUP_PASSWORD');
        $this->backup->buat($this->akar.'/tujuan');
    }

    public function test_backup_harian_gagal_saat_hdd_tidak_ada_dicatat_dan_memicu_peringatan(): void
    {
        config(['sipersu.backup.lokal' => $this->akar.'/hdd-tidak-tercolok']);
        $kode = Artisan::call('backup:run', ['--jenis' => 'harian']);

        $this->assertSame(1, $kode);
        $this->assertDatabaseHas('backup_riwayat', ['jenis' => 'harian', 'status' => 'gagal']);
        $teks = implode(' ', StatusBackup::peringatan());
        $this->assertStringContainsString('tidak ditemukan', $teks);
        $this->assertStringContainsString('GAGAL', $teks);
    }

    public function test_peringatan_bila_backup_terakhir_lebih_dari_2_hari(): void
    {
        config(['sipersu.backup.lokal' => $this->akar.'/tujuan']);
        $b = BackupRiwayat::create(['jenis' => 'harian', 'nama_berkas' => 'a.zip', 'lokasi' => 'x', 'ukuran' => 1, 'status' => 'sukses']);
        $this->assertSame([], StatusBackup::peringatan());

        $b->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->assertStringContainsString('lebih dari 2 hari', implode(' ', StatusBackup::peringatan()));
    }

    public function test_mingguan_dilewati_tanpa_galat_bila_rclone_belum_dikonfigurasi(): void
    {
        config(['sipersu.backup.rclone_remote' => null]);
        $this->assertSame(0, Artisan::call('backup:run', ['--jenis' => 'mingguan']));
        $this->assertStringContainsString('dilewati', Artisan::output());
        $this->assertSame(0, BackupRiwayat::count());
    }
}
