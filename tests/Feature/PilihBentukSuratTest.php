<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\Surat;
use App\Models\User;
use App\Services\KunciTte;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Buat Surat: pilih bentuk (ber-QR / tanpa QR) dulu, lalu SEMUA jenis surat tampil untuk bentuk itu. */
class PilihBentukSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-pb-'.getmypid()]);
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

    public function test_langkah_pertama_hanya_dua_kartu_bentuk_surat(): void
    {
        $this->actingAs($this->u('198701012010011001'))->get('/surat-keluar/buat')->assertOk()
            ->assertSee('Surat Ber-QR')->assertSee('Surat Tanpa QR')
            ->assertSee('/surat-keluar/buat?bentuk=qr', false)->assertSee('/surat-keluar/buat?bentuk=basah', false)
            ->assertDontSee('Surat Tugas Rekomendasi');
    }

    public function test_memilih_salah_satu_bentuk_menampilkan_semua_jenis_surat(): void
    {
        foreach (['qr', 'basah'] as $bentuk) {
            $r = $this->actingAs($this->u('198701012010011001'))->get("/surat-keluar/buat?bentuk=$bentuk")->assertOk();
            foreach (JenisSurat::untukStaf()->where('aktif', true)->pluck('nama') as $nama) {
                $r->assertSee($nama);
            }
            $r->assertSee('Surat Bebas')->assertSee("bentuk=$bentuk", false);
        }
    }

    public function test_jenis_bawaan_tanpa_qr_dapat_dibuat_ber_qr_dan_melalui_persetujuan(): void
    {
        $tu = $this->u('198701012010011001');
        $isian = ['kepada' => 'Semua Dosen', 'hal' => 'Libur', 'isi' => 'Kampus libur.'];     // Surat Pemberitahuan: bawaan tanpa QR

        $this->actingAs($tu)->get('/surat-keluar/format/SURAT-PEMBERITAHUAN?bentuk=qr')->assertOk()->assertSee('Ber-QR');
        $this->actingAs($tu)->post('/surat-keluar/format/SURAT-PEMBERITAHUAN', ['isian' => $isian, 'mode_ttd' => 'qr'])->assertRedirect();

        $s = Surat::firstOrFail();
        $this->assertSame('qr', $s->mode_ttd);
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
        $this->assertSame('menunggu_ttd', $s->fresh()->status, 'ber-QR melalui persetujuan');
        $this->actingAs($this->u('0912038401'))->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password'])->assertRedirect();
        $this->assertNotNull($s->fresh()->qr_token);
    }

    public function test_jenis_bawaan_ber_qr_dapat_dibuat_tanpa_qr_dan_langsung_terbit(): void
    {
        $tu = $this->u('198701012010011001');
        $isian = ['kepada' => "Ketua Prodi\ndi Tempat", 'perihal' => 'Rapat', 'sehubungan' => 'persiapan', 'sebagai' => 'menghadiri rapat', 'hari_tanggal' => '2026-10-20', 'waktu' => '09.00', 'tempat' => 'Aula'];

        $this->actingAs($tu)->post('/surat-keluar/format/UNDANGAN-RAPAT', ['isian' => $isian, 'mode_ttd' => 'basah'])->assertRedirect();
        $s = Surat::firstOrFail();
        $this->assertSame('basah', $s->mode_ttd);

        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
        $s->refresh();
        $this->assertSame('ditandatangani', $s->status, 'tanpa QR terbit langsung');
        $this->assertNull($s->qr_token);
        $this->assertSame(0, $s->persetujuan()->count());
    }

    public function test_ubah_draf_mempertahankan_bentuk_yang_dipilih(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/surat-keluar/format/SURAT-PEMBERITAHUAN', ['isian' => ['kepada' => 'A', 'hal' => 'Lama', 'isi' => 'x'], 'mode_ttd' => 'qr']);
        $s = Surat::firstOrFail();

        $this->actingAs($tu)->put("/surat-keluar/{$s->id}", ['isian' => ['kepada' => 'A', 'hal' => 'Baru', 'isi' => 'y']])->assertRedirect();
        $this->assertSame('qr', $s->fresh()->mode_ttd);
        $this->assertSame('Baru', $s->fresh()->perihal);
    }

    public function test_surat_bebas_mengikuti_bentuk_yang_dipilih(): void
    {
        $this->actingAs($this->u('198701012010011001'))->get('/surat-keluar/buat/bebas?bentuk=basah')->assertOk()->assertSee('name="mode_ttd"', false);
    }
}
