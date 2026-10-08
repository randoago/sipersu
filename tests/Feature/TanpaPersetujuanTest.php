<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\AlurSuratKeluar;
use App\Services\KunciTte;
use App\Support\ContohIsian;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Surat TANPA QR tidak melalui persetujuan (langsung terbit); hanya surat ber-QR yang diparaf dan ditandatangani. */
class TanpaPersetujuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-tp-'.getmypid()]);
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

    public function test_surat_keluar_tanpa_qr_langsung_terbit_tanpa_persetujuan(): void
    {
        $tu = $this->u('198701012010011001');
        $alur = app(AlurSuratKeluar::class);
        $s = $alur->simpan($tu, ['paraf_role' => null, 'mode_ttd' => 'basah'] + ContohIsian::umum());

        $this->assertTrue($s->langsungTerbit());
        $s = $alur->ajukan($s, $tu);

        $this->assertSame('ditandatangani', $s->status);
        $this->assertNotEmpty($s->nomor);
        $this->assertNull($s->qr_token);
        $this->assertNull($s->signature);
        $this->assertNull($s->penandatangan_id, 'tidak ada tanda tangan elektronik oleh pejabat');
        $this->assertSame('Agusman, S.T., MM.', $s->penandatangan_nama, 'nama pejabat tetap tercetak pada surat');
        $this->assertSame(0, $s->persetujuan()->count(), 'tanpa tahap paraf/ttd');
        Storage::disk('local')->assertExists($s->file_pdf);
        $this->assertTrue(LogAktivitas::where('aksi', 'terbit_langsung')->where('user_id', $tu->id)->exists());
    }

    public function test_surat_ber_qr_tetap_melalui_persetujuan(): void
    {
        $tu = $this->u('198701012010011001');
        $alur = app(AlurSuratKeluar::class);
        $s = $alur->simpan($tu, ['mode_ttd' => 'qr', 'paraf_role' => 'wakil_dekan'] + ContohIsian::umum());

        $this->assertFalse($s->langsungTerbit());
        $s = $alur->ajukan($s, $tu);
        $this->assertSame('menunggu_paraf', $s->status, 'ber-QR tetap melalui persetujuan');
        $this->assertNull($s->nomor);
    }

    public function test_tombol_dan_pesan_menyebut_terbitkan_langsung(): void
    {
        $tu = $this->u('198701012010011001');
        $s = app(AlurSuratKeluar::class)->simpan($tu, ['paraf_role' => null, 'mode_ttd' => 'basah'] + ContohIsian::umum());

        $this->actingAs($tu)->get("/surat-keluar/{$s->id}")->assertOk()->assertSee('Terbitkan Surat (tanpa persetujuan)');
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan")->assertRedirect()->assertSessionHas('sukses', fn ($m) => str_contains($m, 'tanpa persetujuan'));
        $this->assertSame('ditandatangani', $s->fresh()->status);
    }

    public function test_pengajuan_mahasiswa_langsung_terbit_setelah_diverifikasi_tu(): void
    {
        JenisSurat::where('kode', 'KET-AKTIF')->update(['mode_ttd' => 'basah']);
        $jenis = JenisSurat::where('kode', 'KET-AKTIF')->first();
        $alur = app(AlurPengajuan::class);
        $mhs = $this->u('21650012');

        $p = $alur->ajukan($mhs, $jenis, ContohIsian::mahasiswa()['KET-AKTIF']);
        $this->assertSame(['verifikasi'], $p->persetujuan()->pluck('tahap')->all(), 'hanya verifikasi TU');

        $p = $alur->verifikasi($p, $this->u('198701012010011001'));

        $this->assertSame('ditandatangani', $p->status->value);
        $this->assertNotEmpty($p->surat->nomor);
        $this->assertNull($p->surat->qr_token);
        $this->assertNull($p->surat->penandatangan_id);
        Storage::disk('local')->assertExists($p->surat->file_pdf);

        $p = $alur->selesaikan($p, $this->u('198701012010011001'));
        $this->assertSame('selesai', $p->status->value);
    }

    public function test_pengajuan_ber_qr_tetap_perlu_persetujuan(): void
    {
        JenisSurat::where('kode', 'KET-AKTIF')->update(['mode_ttd' => 'qr']);
        $alur = app(AlurPengajuan::class);
        $p = $alur->ajukan($this->u('21650012'), JenisSurat::where('kode', 'KET-AKTIF')->first(), ContohIsian::mahasiswa()['KET-AKTIF']);
        $p = $alur->verifikasi($p, $this->u('198701012010011001'));

        $this->assertSame('disetujui', $p->status->value, 'menunggu tanda tangan Dekan');
        $this->assertNull($p->surat->nomor);
    }

    public function test_format_surat_tanpa_qr_otomatis_tanpa_paraf_dan_ber_qr_tetap_boleh_paraf(): void
    {
        $dasar = [
            'nama' => 'Surat Edaran Internal', 'deskripsi' => 'x', 'ikon' => 'mail', 'sasaran' => 'staf', 'judul_surat' => 'EDARAN', 'perihal_template' => 'Edaran {{ isian.hal }}',
            'klasifikasi_id' => KlasifikasiSurat::where('kode', 'A')->value('id'), 'penandatangan_jabatan_id' => Jabatan::where('kode', 'dekan')->value('id'),
            'sla_hari' => 1, 'urutan' => 5, 'aktif' => '1', 'perlu_paraf' => '1', 'paraf_role' => 'wakil_dekan',
            'fields' => [['label' => 'Hal', 'tipe' => 'teks', 'wajib' => '1', 'nama' => '']], 'template_html' => '<p>{{ isian.hal }}</p>',
        ];
        $tu = $this->actingAs($this->u('198701012010011001'));

        $tu->post('/format-surat', $dasar + ['mode_ttd' => 'basah'])->assertRedirect('/format-surat');
        $a = JenisSurat::where('nama', 'Surat Edaran Internal')->latest('id')->first();
        $this->assertFalse($a->perlu_paraf, 'tanpa QR: paraf dinonaktifkan');
        $this->assertTrue($a->langsungTerbit());

        $tu->post('/format-surat', ['nama' => 'Edaran Ber QR'] + $dasar + ['mode_ttd' => 'qr'])->assertRedirect('/format-surat');
        $b = JenisSurat::where('nama', 'Edaran Ber QR')->first();
        $this->assertTrue($b->perlu_paraf);
        $this->assertFalse($b->langsungTerbit());
    }

    public function test_pilihan_bentuk_tanpa_qr_menjelaskan_tidak_ada_persetujuan(): void
    {
        $this->actingAs($this->u('198701012010011001'))->get('/surat-keluar/buat')->assertOk()->assertSee('Tidak ada persetujuan');
        $this->assertTrue(Surat::query()->doesntExist());
    }
}
