<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\Jabatan;
use App\Models\User;
use App\Services\PenyusunSurat;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpesimenStempelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        Storage::disk('local')->put('spesimen/dekan.png', 'TTD-SAJA');
        Storage::disk('local')->put('spesimen/dekan-stempel.png', 'TTD-DAN-STEMPEL');
        $this->u('0912038401')->update(['spesimen_ttd' => 'spesimen/dekan.png', 'spesimen_stempel' => 'spesimen/dekan-stempel.png']);
    }

    private function u(string $nik): User
    {
        return User::where('nomor_induk', $nik)->firstOrFail();
    }

    private function dokumen(string $mode): array
    {
        $surat = new \App\Models\Surat(['isi_html' => '<p>x</p>', 'status' => 'ditandatangani', 'mode_ttd' => $mode, 'tgl_surat' => now()]);
        $surat->setRelation('jabatan', Jabatan::with('pejabat')->where('kode', 'dekan')->first());
        $surat->setRelation('jenis', null);

        return app(PenyusunSurat::class)->dataDokumen($surat, true, '<svg/>');
    }

    public function test_surat_ber_qr_memakai_spesimen_dengan_stempel(): void
    {
        $this->assertSame('data:image/png;base64,'.base64_encode('TTD-DAN-STEMPEL'), $this->dokumen('qr')['spesimen']);
    }

    public function test_bila_stempel_belum_ada_surat_ber_qr_memakai_tanda_tangan_saja(): void
    {
        $this->u('0912038401')->update(['spesimen_stempel' => null]);
        $this->assertSame('data:image/png;base64,'.base64_encode('TTD-SAJA'), $this->dokumen('qr')['spesimen']);
    }

    public function test_surat_tanpa_qr_kosong_tanpa_tanda_tangan_dan_stempel(): void
    {
        $this->assertNull($this->dokumen('basah')['spesimen']);
    }

    public function test_unggah_spesimen_stempel_terpisah_dari_tanda_tangan_saja(): void
    {
        $dekan = $this->u('0912038401');
        $this->actingAs($dekan)->post('/profil/spesimen', ['jenis' => 'stempel', 'spesimen' => UploadedFile::fake()->image('stempel.png', 400, 250)])->assertSessionHasNoErrors();
        $dekan->refresh();
        $this->assertNotSame('spesimen/dekan-stempel.png', $dekan->spesimen_stempel);
        $this->assertSame('spesimen/dekan.png', $dekan->spesimen_ttd, 'spesimen tanpa stempel tidak ikut berubah');
        Storage::disk('local')->assertExists($dekan->spesimen_stempel);
        Storage::disk('local')->assertMissing('spesimen/dekan-stempel.png');

        $this->actingAs($dekan)->post('/profil/spesimen', ['spesimen' => UploadedFile::fake()->image('ttd.png', 300, 150)])->assertSessionHasNoErrors();   // tanpa jenis = tanda tangan saja
        $this->assertNotSame('spesimen/dekan.png', $dekan->fresh()->spesimen_ttd);

        $this->actingAs($dekan)->get('/profil/spesimen?jenis=stempel')->assertOk();
        $this->actingAs($dekan)->post('/profil/spesimen', ['jenis' => 'stempel', 'spesimen' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('spesimen');
        $this->actingAs($this->u('21650012'))->post('/profil/spesimen', ['jenis' => 'stempel', 'spesimen' => UploadedFile::fake()->image('s.png', 300, 150)])->assertForbidden();
    }

    public function test_halaman_profil_menampilkan_dua_spesimen(): void
    {
        $this->actingAs($this->u('0912038401'))->get('/profil')->assertOk()->assertSee('Tanda tangan + stempel')->assertSee('Tanda tangan saja');
    }

    public function test_seeder_menyalin_kedua_berkas_dari_template_img(): void
    {
        $this->assertFileExists(base_path('template/img/ttd-dekan/ttd-dekan-stempel.png'));
        $this->assertFileExists(base_path('template/img/ttd-dekan/ttd-dekan-not-stempel.png'));
        $this->seed(\Database\Seeders\PenggunaSeeder::class);
        $d = $this->u('0912038401');
        $this->assertSame('spesimen/dekan-stempel.png', $d->spesimen_stempel);
        $this->assertSame(file_get_contents(base_path('template/img/ttd-dekan/ttd-dekan-stempel.png')), Storage::disk('local')->get($d->spesimen_stempel));
    }
}
