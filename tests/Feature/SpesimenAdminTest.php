<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PenyusunSurat;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpesimenAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
    }

    private function u(string $nik): User
    {
        return User::where('nomor_induk', $nik)->firstOrFail();
    }

    private function gambar(string $nama = 'ttd.png', int $w = 400, int $h = 200): UploadedFile
    {
        return UploadedFile::fake()->image($nama, $w, $h);
    }

    public function test_halaman_hanya_untuk_admin_tu_dan_super_admin_dan_memuat_semua_pejabat(): void
    {
        foreach (['198701012010011001', '0000000001'] as $nik) {
            $this->actingAs($this->u($nik))->get('/master/spesimen')->assertOk()
                ->assertSee('Agusman')->assertSee('Idwan')->assertSee('Rando')->assertSee('Darmawan')->assertSee('Wakil Dekan (Contoh)')
                ->assertSee('Tanda tangan + stempel')->assertDontSee('Muhammad Fauzan');
        }
        foreach (['0912038401', '0912048102', '0912078603', '0912088704', '21650012'] as $nik) {
            $this->actingAs($this->u($nik))->get('/master/spesimen')->assertForbidden();
        }
    }

    public function test_tu_mengunggah_spesimen_untuk_dekan_dan_kaprodi(): void
    {
        $tu = $this->u('198701012010011001');
        $dekan = $this->u('0912038401');
        $kaprodi = $this->u('0912058301');

        $this->actingAs($tu)->post("/master/spesimen/{$dekan->id}", ['jenis' => 'stempel', 'spesimen' => $this->gambar('stempel.png', 445, 276)])->assertSessionHasNoErrors();
        $this->actingAs($tu)->post("/master/spesimen/{$dekan->id}", ['jenis' => 'ttd', 'spesimen' => $this->gambar()])->assertSessionHasNoErrors();
        $this->actingAs($tu)->post("/master/spesimen/{$kaprodi->id}", ['spesimen' => $this->gambar('kaprodi.jpg')])->assertSessionHasNoErrors();   // tanpa jenis = tanda tangan saja

        $dekan->refresh();
        $kaprodi->refresh();
        Storage::disk('local')->assertExists($dekan->spesimen_stempel);
        Storage::disk('local')->assertExists($dekan->spesimen_ttd);
        $this->assertNotSame($dekan->spesimen_stempel, $dekan->spesimen_ttd);
        Storage::disk('local')->assertExists($kaprodi->spesimen_ttd);
        $this->assertNull($kaprodi->spesimen_stempel);
        $this->assertStringContainsString('user-'.$kaprodi->id.'-', $kaprodi->spesimen_ttd);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'spesimen_ttd', 'user_id' => $tu->id]);
        $this->assertStringContainsString('untuk Agusman', \App\Models\LogAktivitas::where('aksi', 'spesimen_ttd')->latest('id')->skip(1)->first()->deskripsi);
    }

    public function test_mengganti_spesimen_menghapus_berkas_lama(): void
    {
        $tu = $this->u('198701012010011001');
        $d = $this->u('0912038401');
        $this->actingAs($tu)->post("/master/spesimen/{$d->id}", ['jenis' => 'stempel', 'spesimen' => $this->gambar()]);
        $lama = $d->fresh()->spesimen_stempel;
        $this->actingAs($tu)->post("/master/spesimen/{$d->id}", ['jenis' => 'stempel', 'spesimen' => $this->gambar('baru.png')]);
        Storage::disk('local')->assertMissing($lama);
        Storage::disk('local')->assertExists($d->fresh()->spesimen_stempel);
    }

    public function test_hapus_spesimen(): void
    {
        $tu = $this->u('198701012010011001');
        $d = $this->u('0912038401');
        $this->actingAs($tu)->post("/master/spesimen/{$d->id}", ['jenis' => 'stempel', 'spesimen' => $this->gambar()]);
        $path = $d->fresh()->spesimen_stempel;

        $this->actingAs($tu)->delete("/master/spesimen/{$d->id}/stempel")->assertRedirect();
        $this->assertNull($d->fresh()->spesimen_stempel);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'spesimen_hapus']);
        $this->actingAs($tu)->delete("/master/spesimen/{$d->id}/lain")->assertNotFound();
    }

    public function test_validasi_berkas_dan_hanya_untuk_pejabat(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $d = $this->u('0912038401');
        $sebelum = [$d->spesimen_ttd, $d->spesimen_stempel];
        $tu->post("/master/spesimen/{$d->id}", ['spesimen' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('spesimen');
        $tu->post("/master/spesimen/{$d->id}", ['spesimen' => UploadedFile::fake()->image('besar.png', 800, 600)->size(3000)])->assertSessionHasErrors('spesimen');
        $tu->post("/master/spesimen/{$d->id}", ['spesimen' => $this->gambar('kecil.png', 50, 20)])->assertSessionHasErrors('spesimen');
        $tu->post("/master/spesimen/{$d->id}", [])->assertSessionHasErrors('spesimen');
        $tu->post("/master/spesimen/{$d->id}", ['jenis' => 'aneh', 'spesimen' => $this->gambar()])->assertSessionHasErrors('jenis');
        $this->assertSame($sebelum, [$d->fresh()->spesimen_ttd, $d->fresh()->spesimen_stempel], 'unggahan tidak sah tidak mengubah spesimen');

        // mahasiswa dan dosen biasa bukan pejabat → tidak ada spesimen untuk mereka
        $mhs = $this->u('21650012');
        $tu->post("/master/spesimen/{$mhs->id}", ['spesimen' => $this->gambar()])->assertNotFound();
        $tu->get("/master/spesimen/{$mhs->id}/lihat")->assertNotFound();
    }

    public function test_non_admin_tidak_bisa_mengunggah_atau_menghapus_untuk_orang_lain(): void
    {
        $d = $this->u('0912038401');
        $k = $this->u('0912058301');
        $this->actingAs($k)->post("/master/spesimen/{$d->id}", ['spesimen' => $this->gambar()])->assertForbidden();
        $this->actingAs($this->u('21650012'))->post("/master/spesimen/{$d->id}", ['spesimen' => $this->gambar()])->assertForbidden();
        $this->actingAs($this->u('0912088704'))->delete("/master/spesimen/{$d->id}/ttd")->assertForbidden();
        $this->actingAs($k)->get("/master/spesimen/{$d->id}/lihat")->assertForbidden();
    }

    public function test_pratinjau_gambar_spesimen_dapat_dilihat_admin(): void
    {
        $tu = $this->u('198701012010011001');
        $d = $this->u('0912038401');
        $this->actingAs($tu)->post("/master/spesimen/{$d->id}", ['jenis' => 'stempel', 'spesimen' => $this->gambar()]);
        $this->actingAs($tu)->get("/master/spesimen/{$d->id}/lihat?jenis=stempel")->assertOk();
        $this->actingAs($tu)->get("/master/spesimen/{$d->id}/lihat?jenis=ttd")->assertOk();      // dari seeder? (storage fake) → tidak ada
    }

    public function test_surat_ber_qr_langsung_memakai_stempel_yang_diunggah_tu(): void
    {
        $tu = $this->u('198701012010011001');
        $d = $this->u('0912038401');
        $this->actingAs($tu)->post("/master/spesimen/{$d->id}", ['jenis' => 'stempel', 'spesimen' => $this->gambar('s.png', 445, 276)]);

        $surat = new \App\Models\Surat(['isi_html' => '<p>x</p>', 'status' => 'ditandatangani', 'mode_ttd' => 'qr', 'tgl_surat' => now()]);
        $surat->setRelation('jabatan', \App\Models\Jabatan::with('pejabat')->where('kode', 'dekan')->first());
        $surat->setRelation('jenis', null);
        $doc = app(PenyusunSurat::class)->dataDokumen($surat, true, '<svg/>');
        $this->assertSame('data:image/png;base64,'.base64_encode(Storage::disk('local')->get($d->fresh()->spesimen_stempel)), $doc['spesimen']);
    }

    public function test_dekan_tetap_dapat_mengunggah_miliknya_sendiri_di_profil(): void
    {
        $d = $this->u('0912038401');
        $this->actingAs($d)->post('/profil/spesimen', ['jenis' => 'stempel', 'spesimen' => $this->gambar()])->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists($d->fresh()->spesimen_stempel);
    }
}
