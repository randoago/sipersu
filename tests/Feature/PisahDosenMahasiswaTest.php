<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PisahDosenMahasiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): User
    {
        return User::where('nomor_induk', '198701012010011001')->firstOrFail();
    }

    public function test_daftar_pengguna_dipisah_dosen_dan_mahasiswa(): void
    {
        $this->actingAs($this->admin())->get('/master/pengguna')->assertOk()
            ->assertSee('Agusman')->assertSee('NIDN 0912038401')->assertDontSee('Muhammad Fauzan');

        $this->actingAs($this->admin())->get('/master/pengguna?kelompok=mahasiswa')->assertOk()
            ->assertSee('Muhammad Fauzan')->assertSee('NPM 21650012')->assertDontSee('Agusman');
    }

    public function test_pilihan_pejabat_jabatan_tidak_memuat_mahasiswa(): void
    {
        $j = Jabatan::firstOrFail();

        $this->actingAs($this->admin())->get("/master/jabatan/{$j->id}/ubah")->assertOk()
            ->assertSee('Agusman')->assertDontSee('Muhammad Fauzan');
    }

    public function test_mahasiswa_ditolak_sebagai_pejabat(): void
    {
        $j = Jabatan::firstOrFail();
        $mhs = User::where('nomor_induk', '21650012')->firstOrFail();

        $this->actingAs($this->admin())
            ->put("/master/jabatan/{$j->id}", ['kode' => $j->kode, 'nama' => $j->nama, 'user_id' => $mhs->id])
            ->assertSessionHasErrors('user_id');
    }
}
