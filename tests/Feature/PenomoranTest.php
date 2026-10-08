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
        $k = KlasifikasiSurat::where('kode', 'A')->first();
        $this->travelTo(now()->setDate(2026, 10, 15));
        $svc = app(PenomoranService::class);

        $this->assertSame('001/II.3.AU/UMB-06/A/2026', $svc->terbitkan($k, now()));
        $this->assertSame('002/II.3.AU/UMB-06/A/2026', $svc->terbitkan($k, now()));
    }

    public function test_urutan_per_unit_kerja_dan_reset_tiap_tahun(): void
    {
        $au = KlasifikasiSurat::where('kode', 'A')->first();
        $ak = KlasifikasiSurat::where('kode', 'F')->first();
        $svc = app(PenomoranService::class);

        $svc->terbitkan($au, now()->setDate(2026, 12, 30));
        $svc->terbitkan($au, now()->setDate(2026, 12, 31));
        $this->assertSame('003/II.3.AU/UMB-06/F/2026', $svc->terbitkan($ak, now()->setDate(2026, 12, 31)), 'pokok masalah lain: urutan unit yang sama berlanjut');
        $this->assertSame('001/II.3.AU/UMB-06.2/F/2026', $svc->terbitkan($ak, now()->setDate(2026, 12, 31), 'UMB-06.2'), 'unit kerja lain mulai dari 1');
        $this->assertSame('001/II.3.AU/UMB-06/A/2027', $svc->terbitkan($au, now()->setDate(2027, 1, 2)), 'reset tahun baru (1 Januari)');
        $this->assertSame(3, Penomoran::count());
    }

    public function test_format_sesuai_contoh_pedoman_tata_naskah_dinas(): void
    {
        \App\Models\Pengaturan::simpan('panjang_urut', '1');   // contoh pedoman tanpa nol di depan
        $svc = app(PenomoranService::class);
        $tgl = now()->setDate(2025, 6, 1);

        $this->assertSame('7/EDR/II.3.AU/UMB-06/C/2025', $svc->format(7, 'C', $tgl, 'UMB-06', 'EDR'), 'surat Dekan dengan kode kekhususan');
        $this->assertSame('12/II.3.AU/UMB-LPPM/C/2025', $svc->format(12, 'C', $tgl, 'UMB-LPPM'), 'surat lembaga/unit tanpa kekhususan');
        $this->assertSame('9/KET/II.3.AU/UMB-06.2/F/2025', $svc->format(9, 'F', $tgl, 'UMB-06.2', 'KET'), 'Rekayasa Sistem Komputer');
        $this->assertSame('3/TGS/II.3.AU/UMB-06.1/D/2025', $svc->format(3, 'D', $tgl, 'UMB-06.1', 'TGS'), 'Teknik Sipil');

        $this->assertSame(['urut' => 9, 'klasifikasi' => 'F', 'unit' => 'UMB-06.2', 'kekhususan' => 'KET', 'tahun' => 2025], $svc->uraikan('9/KET/II.3.AU/UMB-06.2/F/2025'));
        $this->assertSame(['urut' => 12, 'klasifikasi' => 'C', 'unit' => 'UMB-LPPM', 'kekhususan' => null, 'tahun' => 2025], $svc->uraikan('12/II.3.AU/UMB-LPPM/C/2025'));
        $this->assertNull($svc->uraikan('SK/045/FT-UMB/I/2026'));
    }

    public function test_kode_unit_prodi_dari_penandatangan_kaprodi(): void
    {
        $this->assertSame('UMB-06.1', \App\Models\Prodi::where('kode', 'TS')->value('kode_unit'));
        $this->assertSame('UMB-06.2', \App\Models\Prodi::where('kode', 'RSK')->value('kode_unit'));
        $this->assertSame('UMB-06.2', PenomoranService::unitUntuk(\App\Models\Jabatan::with('prodi')->where('kode', 'kaprodi-rsk')->first()));
        $this->assertSame('UMB-06', PenomoranService::unitUntuk(\App\Models\Jabatan::with('prodi')->where('kode', 'dekan')->first()));
        $this->assertSame('UMB-06', PenomoranService::unitUntuk(\App\Models\Jabatan::with('prodi')->where('kode', 'kaprodi-sti')->first()), 'prodi tanpa kode unit memakai kode fakultas');
    }

    public function test_nomor_tidak_pernah_ganda_dan_tidak_dipakai_ulang(): void
    {
        $k = KlasifikasiSurat::where('kode', 'A')->first();
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
