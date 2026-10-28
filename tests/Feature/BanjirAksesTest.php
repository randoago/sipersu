<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** Perlindungan banjir akses: batas per menit per IP, blokir sementara, tidak memengaruhi IP lain, dan tidak menyentuh basis data. */
class BanjirAksesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sipersu.batas_laju.per_menit' => 5, 'sipersu.batas_laju.blokir_detik' => 120, 'sipersu.batas_laju.aktif' => true]);
        Cache::store('array')->flush();
    }

    private function dari(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    public function test_permintaan_wajar_lolos_dan_yang_berlebihan_dijawab_429_dengan_retry_after(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->dari('192.168.1.50')->get('/login')->assertOk();
        }
        $r = $this->dari('192.168.1.50')->get('/login')->assertStatus(429);
        $this->assertGreaterThanOrEqual(1, (int) $r->headers->get('Retry-After'));
        $r->assertSee('Terlalu Banyak Permintaan');
    }

    public function test_alamat_lain_tidak_terpengaruh(): void
    {
        for ($i = 0; $i < 7; $i++) {
            $this->dari('192.168.1.50')->get('/login');
        }
        $this->dari('192.168.1.50')->get('/login')->assertStatus(429);
        $this->dari('192.168.1.51')->get('/login')->assertOk();
    }

    public function test_banjir_dua_kali_batas_memblokir_ip_sementara(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->dari('10.0.0.9')->get('/login');   // melewati 2× batas (5) → diblokir
        }
        $this->assertTrue(Cache::store('array')->has('banjir:blokir:10.0.0.9'));
        $r = $this->dari('10.0.0.9')->get('/login')->assertStatus(429);
        $this->assertSame('120', $r->headers->get('Retry-After'));
        $this->dari('10.0.0.10')->get('/login')->assertOk();
    }

    public function test_api_dijawab_json_429(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->dari('10.0.0.20')->postJson('/api/v1/auth/token', ['nomor_induk' => 'x', 'password' => 'y']);
        }
        $this->dari('10.0.0.20')->postJson('/api/v1/auth/token', ['nomor_induk' => 'x', 'password' => 'y'])
            ->assertStatus(429)->assertJsonStructure(['message'])->assertHeader('Retry-After');
    }

    public function test_pembatas_berjalan_sebelum_sesi_dan_basis_data_disentuh(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $this->dari('10.0.0.30')->get('/login');
        }
        $queries = 0;
        \DB::listen(function () use (&$queries) {
            $queries++;
        });
        $this->dari('10.0.0.30')->get('/login')->assertStatus(429);
        $this->assertSame(0, $queries, 'IP terblokir tidak boleh memicu kueri basis data.');
    }

    public function test_cek_kesehatan_tidak_dibatasi_dan_pembatas_dapat_dimatikan(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->dari('10.0.0.40')->get('/up')->assertOk();
        }
        config(['sipersu.batas_laju.aktif' => false]);
        for ($i = 0; $i < 20; $i++) {
            $this->dari('10.0.0.41')->get('/login')->assertOk();
        }
    }

    public function test_akses_dari_luar_jaringan_tetap_403_bukan_429(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->dari('8.8.8.8')->get('/login')->assertForbidden();
        }
    }
}
