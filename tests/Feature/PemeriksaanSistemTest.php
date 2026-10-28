<?php

namespace Tests\Feature;

use App\Models\Notifikasi;
use App\Services\KunciTte;
use App\Services\PemeriksaanSistem as P;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PemeriksaanSistemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-periksa-'.getmypid()]);
        KunciTte::buat(true);
        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        @unlink(KunciTte::jalur('ed25519.secret'));
        @unlink(KunciTte::jalur('ed25519.public'));
        @rmdir(config('sipersu.kunci_path'));
        parent::tearDown();
    }

    private function hasilPemeriksaan(string $nama): string
    {
        return collect((new P)->jalankan())->firstWhere('nama', $nama)['status'];
    }

    public function test_sistem_sehat_tidak_kritis_dan_perintah_berhasil(): void
    {
        foreach (['Basis data', 'Migrasi', 'Kunci tanda tangan', 'Folder tulis'] as $n) {
            $this->assertSame(P::OK, $this->hasilPemeriksaan($n), $n);
        }
        $this->assertNotSame(P::KRITIS, P::terburuk((new P)->jalankan()));
        $this->artisan('sipersu:periksa')->assertSuccessful();
    }

    public function test_kunci_tanda_tangan_hilang_dilaporkan_kritis(): void
    {
        unlink(KunciTte::jalur('ed25519.secret'));
        $this->assertSame(P::KRITIS, $this->hasilPemeriksaan('Kunci tanda tangan'));
        $this->artisan('sipersu:periksa')->assertFailed();
    }

    public function test_migrasi_belum_dijalankan_dilaporkan_kritis(): void
    {
        DB::table('migrations')->orderByDesc('id')->limit(1)->delete();
        $this->assertSame(P::KRITIS, $this->hasilPemeriksaan('Migrasi'));
    }

    public function test_debug_menyala_di_produksi_kritis_dan_pembatas_mati_peringatan(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true]);
        $this->assertSame(P::KRITIS, $this->hasilPemeriksaan('Pengaturan produksi'));
        config(['app.debug' => false, 'sipersu.batas_laju.aktif' => false]);
        $this->assertSame(P::PERINGATAN, $this->hasilPemeriksaan('Pengaturan produksi'));
    }

    public function test_backup_belum_pernah_berhasil_menjadi_peringatan_bukan_kritis(): void
    {
        $this->assertSame(P::PERINGATAN, $this->hasilPemeriksaan('Backup'));
    }

    public function test_masalah_kritis_dikirim_ke_admin_hanya_sekali_per_hari(): void
    {
        unlink(KunciTte::jalur('ed25519.secret'));
        $this->artisan('sipersu:periksa --kirim')->assertFailed();
        $jumlah = Notifikasi::where('judul', 'Masalah kritis pada SIPERSU')->count();
        $this->assertGreaterThan(0, $jumlah);

        $this->artisan('sipersu:periksa --kirim')->assertFailed();
        $this->assertSame($jumlah, Notifikasi::where('judul', 'Masalah kritis pada SIPERSU')->count());
    }

    public function test_ruang_disk_dan_folder_dapat_dibaca(): void
    {
        $this->assertContains($this->hasilPemeriksaan('Ruang disk'), [P::OK, P::PERINGATAN, P::KRITIS]);
        $this->assertSame(P::OK, $this->hasilPemeriksaan('Antrean email'));
    }

    public function test_perintah_buka_blokir_membuka_satu_ip_atau_semua(): void
    {
        $c = Cache::store(config('sipersu.batas_laju.store'));
        $c->put('banjir:blokir:10.1.1.1', 1, 300);
        $c->put('banjir:blokir:10.1.1.2', 1, 300);

        $this->artisan('banjir:buka 10.1.1.1')->assertSuccessful();
        $this->assertFalse($c->has('banjir:blokir:10.1.1.1'));
        $this->assertTrue($c->has('banjir:blokir:10.1.1.2'));

        $this->artisan('banjir:buka ngawur')->assertFailed();
        $this->artisan('banjir:buka --semua')->assertSuccessful();
        $this->assertFalse($c->has('banjir:blokir:10.1.1.2'));
    }
}
