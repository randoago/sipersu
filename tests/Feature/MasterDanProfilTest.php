<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MasterDanProfilTest extends TestCase
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

    public function test_semua_halaman_admin_terbuka_untuk_super_admin(): void
    {
        $this->actingAs($this->u('0000000001'));
        foreach (['/master/pengguna', '/master/prodi', '/master/jabatan', '/master/klasifikasi', '/format-surat', '/format-surat/buat', '/format-surat/1/ubah', '/master/pengguna/buat',
            '/pengaturan/nomor', '/pengaturan/backup', '/pengaturan/log', '/profil', '/notifikasi', '/pengajuan', '/persetujuan', '/layanan', '/layanan/lacak', '/dasbor'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_mahasiswa_dan_dekan_tidak_boleh_masuk_master_atau_pengaturan(): void
    {
        foreach (['21650012', '0912038401', '0912078603'] as $nik) {
            $this->actingAs($this->u($nik));
            foreach (['/master/pengguna', '/pengaturan/nomor', '/pengaturan/backup', '/pengaturan/log'] as $url) {
                $this->get($url)->assertForbidden();
            }
        }
        $this->actingAs($this->u('21650012'))->post('/pengaturan/backup')->assertForbidden();
    }

    public function test_tambah_pengguna_dengan_peran_dan_kata_sandi_ter_hash(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/master/pengguna', [
            'nomor_induk' => '22650099', 'nama' => 'Mahasiswa Baru', 'peran' => ['mahasiswa'], 'password' => 'rahasia123', 'aktif' => '1',
        ])->assertRedirect('/master/pengguna');

        $u = $this->u('22650099');
        $this->assertTrue($u->hasRole('mahasiswa'));
        $this->assertNotSame('rahasia123', $u->password);
        $this->assertTrue(\Hash::check('rahasia123', $u->password));
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'master_buat']);
    }

    public function test_nomor_induk_harus_unik_dan_sandi_wajib_saat_buat(): void
    {
        $this->actingAs($this->u('198701012010011001'))
            ->post('/master/pengguna', ['nomor_induk' => '21650012', 'nama' => 'Dobel', 'peran' => ['mahasiswa']])
            ->assertSessionHasErrors(['nomor_induk', 'password']);
    }

    public function test_admin_tu_tidak_bisa_memberi_peran_super_admin_atau_mengubah_super_admin(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/master/pengguna', ['nomor_induk' => '5555', 'nama' => 'X', 'peran' => ['super_admin'], 'password' => 'abcdefgh1'])->assertSessionHasErrors('peran');
        $this->assertNull(User::where('nomor_induk', '5555')->first());

        $super = $this->u('0000000001');
        $this->actingAs($tu)->get("/master/pengguna/{$super->id}/ubah")->assertForbidden();
        $this->actingAs($tu)->post("/master/pengguna/{$super->id}/aktif")->assertForbidden();
    }

    public function test_tidak_bisa_menonaktifkan_akun_sendiri_dan_nonaktif_tidak_bisa_login(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post("/master/pengguna/{$tu->id}/aktif")->assertStatus(422);

        $mhs = $this->u('21650012');
        $this->actingAs($tu)->post("/master/pengguna/{$mhs->id}/aktif");
        $this->assertFalse($mhs->fresh()->aktif);
        auth()->logout();
        \Livewire\Livewire::test(\App\Livewire\Auth\Login::class)->set('nomor_induk', '21650012')->set('password', 'password')->call('masuk')->assertHasErrors('nomor_induk');
    }

    public function test_ganti_kata_sandi_butuh_sandi_lama(): void
    {
        $m = $this->u('21650012');
        $this->actingAs($m)->post('/profil/kata-sandi', ['password_lama' => 'salah', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])->assertSessionHasErrors('password_lama');
        $this->actingAs($m)->post('/profil/kata-sandi', ['password_lama' => 'password', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])->assertSessionHasNoErrors();
        $this->assertTrue(\Hash::check('baru12345', $m->fresh()->password));
        $this->actingAs($m)->post('/profil/kata-sandi', ['password_lama' => 'baru12345', 'password' => 'pendek', 'password_confirmation' => 'pendek'])->assertSessionHasErrors('password');
    }

    public function test_spesimen_hanya_pejabat_dan_hanya_gambar(): void
    {
        $dekan = $this->u('0912038401');
        $this->actingAs($this->u('21650012'))->post('/profil/spesimen', ['spesimen' => UploadedFile::fake()->image('t.png', 300, 150)])->assertForbidden();

        $this->actingAs($dekan)->post('/profil/spesimen', ['spesimen' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])->assertSessionHasErrors('spesimen');
        $this->actingAs($dekan)->post('/profil/spesimen', ['spesimen' => UploadedFile::fake()->image('ttd.png', 300, 150)])->assertSessionHasNoErrors();
        $path = $dekan->fresh()->spesimen_ttd;
        Storage::disk('local')->assertExists($path);
        $this->actingAs($dekan)->get('/profil/spesimen')->assertOk();
        $this->actingAs($this->u('0912048102'))->get('/profil/spesimen')->assertNotFound();   // tidak bisa melihat milik orang lain
    }

    public function test_notifikasi_hanya_milik_sendiri_dan_ditandai_dibaca(): void
    {
        $a = $this->u('21650012');
        $n = \App\Models\Notifikasi::create(['user_id' => $a->id, 'judul' => 'Tes', 'url' => '/layanan']);
        $this->actingAs($this->u('0912038401'))->get("/notifikasi/{$n->id}")->assertForbidden();
        $this->actingAs($a)->get("/notifikasi/{$n->id}")->assertRedirect('/layanan');
        $this->assertNotNull($n->fresh()->dibaca_pada);
    }
}
