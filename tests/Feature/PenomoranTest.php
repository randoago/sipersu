<?php

namespace Tests\Feature;

use App\Models\KlasifikasiSurat;
use App\Models\Penomoran;
use App\Services\PenomoranService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenomoranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_format_nomor_sesuai_aturan(): void
    {
        $k = KlasifikasiSurat::where('kode', 'II.3.AU')->first();
        $this->travelTo(now()->setDate(2026, 10, 15));
        $svc = app(PenomoranService::class);

        $this->assertSame('001/II.3.AU/FT-UMB/X/2026', $svc->terbitkan($k, now()));
        $this->assertSame('002/II.3.AU/FT-UMB/X/2026', $svc->terbitkan($k, now()));
    }

    public function test_urutan_per_klasifikasi_dan_reset_tiap_tahun(): void
    {
        $au = KlasifikasiSurat::where('kode', 'II.3.AU')->first();
        $ak = KlasifikasiSurat::where('kode', 'II.1.AK')->first();
        $svc = app(PenomoranService::class);

        $svc->terbitkan($au, now()->setDate(2026, 12, 30));
        $svc->terbitkan($au, now()->setDate(2026, 12, 31));
        $this->assertStringStartsWith('001/II.1.AK', $svc->terbitkan($ak, now()->setDate(2026, 12, 31)), 'klasifikasi lain mulai dari 1');
        $this->assertSame('001/II.3.AU/FT-UMB/I/2027', $svc->terbitkan($au, now()->setDate(2027, 1, 2)), 'reset tahun baru');
        $this->assertSame(3, Penomoran::count());
    }

    public function test_nomor_tidak_pernah_ganda_dan_tidak_dipakai_ulang(): void
    {
        $k = KlasifikasiSurat::where('kode', 'II.3.AU')->first();
        $svc = app(PenomoranService::class);
        $semua = [];
        for ($i = 0; $i < 25; $i++) {
            $semua[] = $svc->terbitkan($k, now());
        }
        $this->assertCount(25, array_unique($semua));

        // transaksi yang dibatalkan (rollback) tidak menyisakan nomor yang "terpakai"
        try {
            \DB::transaction(function () use ($svc, $k) {
                $svc->terbitkan($k, now());
                throw new \RuntimeException('gagal setelah nomor dialokasikan');
            });
        } catch (\RuntimeException) {
        }
        $berikut = $svc->terbitkan($k, now());
        $this->assertStringStartsWith('026/', $berikut, 'rollback mengembalikan counter; nomor 026 belum pernah terbit');
    }

    public function test_dua_proses_nyata_mendapat_nomor_berbeda(): void
    {
        // Mensimulasikan dua koneksi SQLite terpisah pada berkas yang sama dengan BEGIN IMMEDIATE.
        $berkas = sys_get_temp_dir().'/sipersu-imm-'.getmypid().'.sqlite';
        @unlink($berkas);
        $a = new \PDO('sqlite:'.$berkas);
        $a->exec('create table penomoran (id integer primary key, nomor integer)');
        $a->exec('insert into penomoran (id, nomor) values (1, 0)');
        $b = new \PDO('sqlite:'.$berkas);
        $b->setAttribute(\PDO::ATTR_TIMEOUT, 0);

        $a->exec('BEGIN IMMEDIATE');
        $gagal = false;
        try {
            $b->exec('BEGIN IMMEDIATE');         // proses kedua HARUS tertahan selama A memegang kunci tulis
        } catch (\PDOException) {
            $gagal = true;
        }
        $this->assertTrue($gagal, 'BEGIN IMMEDIATE kedua tidak boleh lolos saat transaksi pertama aktif');
        $a->exec('COMMIT');
        @unlink($berkas);
    }
}
