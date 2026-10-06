<?php

namespace Tests\Feature;

use App\Enums\StatusPengajuan as S;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\Penomoran;
use App\Models\Surat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\KunciTte;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlurPengajuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-'.getmypid()]);
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

    private function pengguna(string $nik): User
    {
        return User::where('nomor_induk', $nik)->firstOrFail();
    }

    private function ajukan(string $kode = 'KET-AKTIF'): Pengajuan
    {
        $jenis = JenisSurat::where('kode', $kode)->firstOrFail();
        $isian = ['semester' => '7', 'tahun_akademik' => '2026/2027 Ganjil', 'keterangan' => ''];

        return app(AlurPengajuan::class)->ajukan($this->pengguna('21650012'), $jenis, $isian);
    }

    public function test_alur_lengkap_sampai_nomor_terbit(): void
    {
        $alur = app(AlurPengajuan::class);
        $tu = $this->pengguna('198701012010011001');
        $dekan = $this->pengguna('0912038401');

        $p = $this->ajukan();
        $this->assertSame(S::Diajukan, $p->status);
        $this->assertNull($p->surat);

        $p = $alur->verifikasi($p, $tu);
        $this->assertSame(S::Disetujui, $p->status, 'tanpa paraf, verifikasi langsung siap TTD');
        $this->assertNull($p->surat->nomor, 'nomor TIDAK boleh terbit sebelum tanda tangan');

        $p = $alur->tandatangani($p, $dekan);
        $surat = $p->surat->fresh();

        $this->assertSame(S::Ditandatangani, $p->status);
        $this->assertSame('ditandatangani', $surat->status);
        $this->assertMatchesRegularExpression('#^001/II\.1\.AK/FT-UMB/[IVX]+/'.now()->year.'$#', $surat->nomor);
        $this->assertGreaterThanOrEqual(32, strlen($surat->qr_token));
        Storage::disk('local')->assertExists($surat->file_pdf);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($surat->file_pdf)), $surat->pdf_hash);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($surat->file_pdf));

        $p = $alur->selesaikan($p, $tu);
        $this->assertSame(S::Selesai, $p->status);
    }

    public function test_surat_dengan_paraf_butuh_wakil_dekan_dulu(): void
    {
        $alur = app(AlurPengajuan::class);
        $jenis = JenisSurat::where('kode', 'IZIN-PENELITIAN')->first();
        $p = app(AlurPengajuan::class)->ajukan($this->pengguna('21650012'), $jenis, [
            'judul' => 'Judul', 'kepada' => 'Kadis', 'instansi' => 'Diskominfo', 'alamat_instansi' => 'Baubau',
            'tgl_mulai' => '2026-11-01', 'tgl_selesai' => '2026-12-01', 'pembimbing' => 'Dr. A',
        ]);

        $p = $alur->verifikasi($p, $this->pengguna('198701012010011001'));
        $this->assertSame(S::Diverifikasi, $p->status);

        $this->expectException(AuthorizationException::class);   // dekan belum boleh TTD sebelum paraf
        $alur->tandatangani($p, $this->pengguna('0912038401'));
    }

    public function test_paraf_lalu_ttd(): void
    {
        $alur = app(AlurPengajuan::class);
        $jenis = JenisSurat::where('kode', 'IZIN-PENELITIAN')->first();
        $p = $alur->ajukan($this->pengguna('21650012'), $jenis, [
            'judul' => 'Judul', 'kepada' => 'Kadis', 'instansi' => 'Diskominfo', 'alamat_instansi' => 'Baubau',
            'tgl_mulai' => '2026-11-01', 'tgl_selesai' => '2026-12-01', 'pembimbing' => 'Dr. A',
        ]);
        $p = $alur->verifikasi($p, $this->pengguna('198701012010011001'));
        $p = $alur->paraf($p, $this->pengguna('0912048102'));
        $this->assertSame(S::Disetujui, $p->status);
        $p = $alur->tandatangani($p, $this->pengguna('0912038401'));
        $this->assertSame(S::Ditandatangani, $p->status);
        $this->assertStringContainsString('II.4.PN', $p->surat->nomor);
    }

    public function test_hanya_pejabat_yang_boleh_ttd_dan_mahasiswa_tidak_boleh_verifikasi(): void
    {
        $alur = app(AlurPengajuan::class);
        $p = $alur->verifikasi($this->ajukan(), $this->pengguna('198701012010011001'));

        foreach (['0000000001' /* super admin */, '198701012010011001' /* TU */, '21650012' /* mhs */] as $nik) {
            try {
                $alur->tandatangani($p, $this->pengguna($nik));
                $this->fail("$nik tidak boleh menandatangani");
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }

        $baru = $this->ajukan();
        $this->expectException(AuthorizationException::class);
        $alur->verifikasi($baru, $this->pengguna('21650012'));
    }

    public function test_kaprodi_hanya_memverifikasi_prodi_sendiri(): void
    {
        $alur = app(AlurPengajuan::class);
        $p = $this->ajukan('PENGANTAR-KP');            // verifikator: kaprodi; pemohon prodi STI
        $this->assertFalse($alur->bolehVerifikasi($p, $this->pengguna('0912058301')), 'kaprodi Sipil tidak berwenang');
        $this->assertTrue($alur->bolehVerifikasi($p, $this->pengguna('0912078603')), 'kaprodi STI berwenang');
    }

    public function test_penolakan_wajib_alasan_dan_surat_batal_tidak_memakai_nomor(): void
    {
        $alur = app(AlurPengajuan::class);
        $p = $this->ajukan();
        $tu = $this->pengguna('198701012010011001');

        $p = $alur->tolak($p, $tu, 'Berkas SPP tidak terbaca');
        $this->assertSame(S::Ditolak, $p->status);
        $this->assertSame('Berkas SPP tidak terbaca', $p->alasan_tolak);
        $this->assertSame(0, Penomoran::count());
    }
}
