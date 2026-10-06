<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginUsernameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function masuk(string $identitas, string $sandi = 'password')
    {
        return Livewire::test(Login::class)->set('nomor_induk', $identitas)->set('password', $sandi)->call('masuk');
    }

    public function test_tu_dan_superadmin_masuk_dengan_username(): void
    {
        $this->masuk('TU')->assertRedirect(route('dasbor'));
        $this->assertAuthenticatedAs(User::where('nomor_induk', '198701012010011001')->first());
        auth()->logout();

        $this->masuk('superadmin')->assertRedirect(route('dasbor'));
        $this->assertAuthenticatedAs(User::where('nomor_induk', '0000000001')->first());
    }

    public function test_username_tidak_membedakan_huruf_besar_kecil_dan_nomor_induk_tetap_berlaku(): void
    {
        $this->masuk('tu')->assertRedirect(route('dasbor'));
        auth()->logout();
        $this->masuk('SuperAdmin')->assertRedirect(route('dasbor'));
        auth()->logout();
        $this->masuk('198701012010011001')->assertRedirect(route('dasbor'));
        auth()->logout();
        $this->masuk('21650012')->assertRedirect(route('dasbor'));
    }

    public function test_username_dengan_sandi_salah_atau_akun_nonaktif_ditolak(): void
    {
        $this->masuk('TU', 'salah')->assertHasErrors('nomor_induk');
        $this->assertGuest();

        User::where('username', 'TU')->update(['aktif' => false]);
        $this->masuk('TU')->assertHasErrors('nomor_induk');
        $this->assertGuest();

        $this->masuk('tidak-ada')->assertHasErrors('nomor_induk');
    }

    public function test_username_unik_dan_hanya_karakter_aman_di_master_data(): void
    {
        $tu = User::where('username', 'TU')->first();
        $dosen = User::where('nomor_induk', '0912088704')->first();
        $data = ['nomor_induk' => $dosen->nomor_induk, 'nama' => 'Dosen Contoh', 'peran' => ['dosen_tendik'], 'aktif' => '1'];

        $this->actingAs($tu)->put("/master/pengguna/{$dosen->id}", $data + ['username' => 'tu'])->assertSessionHasErrors('username'); // sudah dipakai (huruf besar/kecil sama)
        $this->actingAs($tu)->put("/master/pengguna/{$dosen->id}", $data + ['username' => 'a b<script>'])->assertSessionHasErrors('username');
        $this->actingAs($tu)->put("/master/pengguna/{$dosen->id}", $data + ['username' => 'dosen.contoh'])->assertRedirect();
        $this->assertSame('dosen.contoh', $dosen->fresh()->username);
    }
}
