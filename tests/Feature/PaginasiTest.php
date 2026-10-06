<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Navigasi halaman memakai ikon panah dan teks Indonesia, bukan "Previous/Next/Showing". */
class PaginasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigasi_halaman_memakai_ikon_dan_bahasa_indonesia(): void
    {
        $this->seed(DatabaseSeeder::class);
        foreach (range(1, 20) as $i) {
            User::create(['nomor_induk' => '2299'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'nama' => "Mhs Uji $i", 'password' => 'password'])->assignRole('mahasiswa');
        }

        $r = $this->actingAs(User::where('username', 'TU')->first())->get('/master/pengguna?kelompok=mahasiswa')->assertOk();

        $r->assertSee('Halaman berikutnya')->assertSee('Menampilkan')->assertSee('data')->assertSee('<svg', false);
        $r->assertDontSee('Previous')->assertDontSee('Next')->assertDontSee('Showing')->assertDontSee('results');

        $this->actingAs(User::where('username', 'TU')->first())->get('/master/pengguna?kelompok=mahasiswa&page=2')->assertOk()->assertSee('Halaman sebelumnya');
    }
}
