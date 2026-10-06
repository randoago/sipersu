<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\AlurSuratKeluar;
use App\Services\KunciTte;
use App\Support\ContohIsian;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Garis waktu "Tahapan Surat" menampilkan SEMUA tahap sejak surat masih draf. */
class TahapanSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-th-'.getmypid()]);
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

    private function judul(array $tahap): array
    {
        return array_column($tahap, 'judul');
    }

    public function test_draf_ber_qr_sudah_menampilkan_semua_tahap_dan_menandai_yang_berjalan(): void
    {
        $tu = $this->u('198701012010011001');
        $s = app(AlurSuratKeluar::class)->simpan($tu, ['mode_ttd' => 'qr', 'paraf_role' => 'wakil_dekan'] + ContohIsian::umum());

        $t = $s->tahapan();

        $this->assertSame(['Draf dibuat', 'Paraf Wakil Dekan', 'Tanda Tangan Elektronik (QR)', 'Surat terbit dengan nomor resmi'], $this->judul($t));
        $this->assertSame(['selesai', 'sekarang', 'menunggu', 'menunggu'], array_column($t, 'status'));
        $this->actingAs($tu)->get("/surat-keluar/{$s->id}")->assertOk()->assertSee('Tahapan Surat')->assertSee('Paraf Wakil Dekan')->assertSee('Surat terbit dengan nomor resmi');
    }

    public function test_surat_ber_qr_terbit_semua_tahap_selesai_dan_batal_menambah_tahap(): void
    {
        $alur = app(AlurSuratKeluar::class);
        [$tu, $wadek, $dekan] = [$this->u('198701012010011001'), $this->u('0912048102'), $this->u('0912038401')];
        $s = $alur->simpan($tu, ['mode_ttd' => 'qr', 'paraf_role' => 'wakil_dekan'] + ContohIsian::umum());
        $s = $alur->tandatangani($alur->paraf($alur->ajukan($s, $tu), $wadek, null), $dekan);

        $this->assertSame(['selesai', 'selesai', 'selesai', 'selesai'], array_column($s->tahapan(), 'status'));

        $s = $alur->batalkan($s, $tu, 'Salah tujuan.');
        $t = $s->tahapan();
        $this->assertSame('Dibatalkan', end($t)['judul']);
        $this->assertSame('ditolak', end($t)['status']);
    }

    public function test_surat_tanpa_qr_tanpa_tahap_persetujuan_terbit_langsung_lalu_cetak(): void
    {
        $alur = app(AlurSuratKeluar::class);
        $tu = $this->u('198701012010011001');

        $langsung = $alur->simpan($tu, ['mode_ttd' => 'basah', 'paraf_role' => 'wakil_dekan'] + ContohIsian::umum());   // paraf diabaikan untuk tanpa QR
        $this->assertSame(['Draf dibuat', 'Diterbitkan langsung (tanpa paraf dan tanda tangan elektronik)', 'Cetak, tanda tangan basah, dan cap (manual oleh TU)'], $this->judul($langsung->tahapan()));
        $langsung = $alur->ajukan($langsung, $tu);
        $this->assertSame(['selesai', 'selesai', 'sekarang'], array_column($langsung->tahapan(), 'status'));
    }

    public function test_pengajuan_tanpa_qr_menampilkan_tahap_terbit_langsung_dan_cetak(): void
    {
        JenisSurat::where('kode', 'KET-AKTIF')->update(['mode_ttd' => 'basah']);
        $alur = app(AlurPengajuan::class);
        $p = $alur->ajukan($this->u('21650012'), JenisSurat::where('kode', 'KET-AKTIF')->first(), ContohIsian::mahasiswa()['KET-AKTIF']);

        $judul = $this->judul($p->garisWaktu());
        $this->assertContains('Surat Terbit Langsung (tanpa paraf dan tanda tangan elektronik)', $judul);
        $this->assertContains('Cetak, Tanda Tangan Basah & Cap (TU)', $judul);
        $this->assertNotContains('Penandatanganan Digital', $judul);

        $this->actingAs($this->u('21650012'))->get(route('pengajuan.show', $p))->assertOk()->assertSee('Surat Terbit Langsung');
    }
}
