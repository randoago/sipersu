<?php

namespace Tests\Feature;

use App\Models\Pengajuan;
use App\Models\Surat;
use App\Models\User;
use App\Services\KunciTte;
use App\Services\SimulasiService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SimulasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-sim-'.getmypid()]);
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

    private function u(string $nik): User
    {
        return User::where('nomor_induk', $nik)->firstOrFail();
    }

    public function test_simulasi_membuat_surat_pada_setiap_tahap(): void
    {
        $manifest = app(SimulasiService::class)->siapkan();

        $status = Pengajuan::whereIn('id', collect($manifest)->where('tipe', 'pengajuan')->pluck('id'))->get()->pluck('status.value')->unique()->sort()->values()->all();
        $this->assertSame(['diajukan', 'diverifikasi', 'disetujui', 'ditandatangani', 'selesai', 'ditolak'], array_values(array_intersect(
            ['diajukan', 'diverifikasi', 'disetujui', 'ditandatangani', 'selesai', 'ditolak'], $status)), 'semua status pengajuan terwakili');

        $keluar = Surat::whereIn('id', collect($manifest)->where('kelompok', 'keluar')->pluck('id'))->get();
        foreach (['draf', 'menunggu_paraf', 'menunggu_ttd', 'ditandatangani', 'batal'] as $st) {
            $this->assertTrue($keluar->contains('status', $st), "surat keluar berstatus $st ada");
        }
        $this->assertTrue($keluar->contains(fn ($s) => $s->status === 'ditandatangani' && $s->pakaiQr()), 'ber-QR');
        $this->assertTrue($keluar->contains(fn ($s) => $s->status === 'ditandatangani' && ! $s->pakaiQr()), 'tanpa QR');
        $this->assertTrue($keluar->whereNotNull('nomor')->every(fn ($s) => $s->nomor !== ''), 'nomor terbit saat tanda tangan');

        $masuk = Surat::whereIn('id', collect($manifest)->where('kelompok', 'masuk')->pluck('id'))->get();
        $this->assertCount(3, $masuk);
        $this->assertTrue($masuk->every(fn ($s) => filled($s->no_agenda)), 'nomor agenda otomatis');
    }

    public function test_simulasi_tidak_dibuat_dua_kali(): void
    {
        app(SimulasiService::class)->siapkan();
        $this->expectException(\RuntimeException::class);

        app(SimulasiService::class)->siapkan();
    }

    public function test_format_surat_baru_tersedia_di_pilihan_buat_surat(): void
    {
        $this->actingAs($this->u('198701012010011001'))->get('/surat-keluar/buat?bentuk=qr')->assertOk()
            ->assertSee('Surat Keterangan Aktif Kuliah')->assertSee('Surat Keterangan Cuti Akademik')
            ->assertSee('Aktif Kembali Setelah Cuti')->assertSee('Surat Permohonan Pencairan Anggaran')->assertSee('Surat Izin Penelitian (dibuat TU)');
    }
}
