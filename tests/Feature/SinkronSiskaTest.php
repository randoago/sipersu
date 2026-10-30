<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\Prodi;
use App\Models\User;
use App\Services\SiskaClient;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SinkronSiskaTest extends TestCase
{
    use RefreshDatabase;

    private const ID_TS = 'ts-uuid-0001';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Pengaturan::simpan('siska_url', 'http://siska.test');
        SiskaClient::simpanKunci('sk_kunci_uji_1234567890abcd');
        Pengaturan::simpan('siska_prodi', json_encode([self::ID_TS]));
    }

    private function u(string $nik): User
    {
        return User::where('nomor_induk', $nik)->orWhere('username', $nik)->firstOrFail();
    }

    private function mhs(string $nim, array $lebih = []): array
    {
        return $lebih + ['nim' => $nim, 'nama' => "MHS $nim", 'jenis_kelamin' => 'L', 'id_prodi' => self::ID_TS, 'nama_program_studi' => 'S1 Teknik Sipil', 'angkatan' => '2021',
            'status' => 'AKTIF', 'email' => "$nim@mail.test", 'no_hp' => '0812345678', 'tempat_lahir' => 'Baubau', 'tanggal_lahir' => '2002-05-03', 'alamat' => 'Jl. Satu'];
    }

    private function fakeSiska(array $mahasiswa, ?array $prodi = null): void
    {
        $prodi ??= [['id_prodi' => self::ID_TS, 'kode_program_studi' => '22201', 'nama_program_studi' => 'Teknik Sipil', 'jenjang' => 'S1', 'aktif' => true]];
        Http::fake([
            'siska.test/api/integrasi/v1/prodi*' => Http::response(['data' => $prodi]),
            'siska.test/api/integrasi/v1/mahasiswa*' => Http::response(['data' => $mahasiswa, 'meta' => ['halaman' => 1, 'halaman_terakhir' => 1, 'per_halaman' => 200, 'total' => count($mahasiswa)]]),
        ]);
    }

    public function test_sinkron_prodi_mencocokkan_nama_lokal_tanpa_menimpa_kode(): void
    {
        $this->fakeSiska([]);
        $this->actingAs($this->u('TU'))->post('/master/sinkron-siska/prodi')->assertRedirect('/master/prodi')->assertSessionHas('sukses');

        $ts = Prodi::where('kode', 'TS')->firstOrFail();
        $this->assertSame(self::ID_TS, $ts->siska_id);
        $this->assertSame('UMB-06.1', $ts->kode_unit);
        $this->assertSame(0, Prodi::where('kode', '22201')->count());                 // tidak membuat duplikat
    }

    public function test_sinkron_prodi_membuat_prodi_baru_bila_tidak_ada(): void
    {
        $this->fakeSiska([], [['id_prodi' => self::ID_TS, 'kode_program_studi' => '59999', 'nama_program_studi' => 'Teknik Baru', 'jenjang' => 'S1', 'aktif' => true]]);
        $this->actingAs($this->u('TU'))->post('/master/sinkron-siska/prodi');

        $this->assertDatabaseHas('prodi', ['kode' => '59999', 'nama' => 'Teknik Baru', 'siska_id' => self::ID_TS]);
    }

    public function test_sinkron_pengguna_membuat_mahasiswa_dan_tidak_menyentuh_data_lokal(): void
    {
        Prodi::where('kode', 'TS')->update(['siska_id' => self::ID_TS]);
        User::where('nomor_induk', '21650012')->update(['username' => 'fauzan', 'email' => null]);
        $this->fakeSiska([
            $this->mhs('30000001'),
            $this->mhs('21650012', ['nama' => 'MUHAMMAD FAUZAN BARU', 'email' => 'fauzan@mail.test']),
            $this->mhs('30000002', ['email' => '30000001@mail.test']),                   // email bentrok → dikosongkan
            $this->mhs('30000001'),                                                      // NIM ganda → dilewati
            $this->mhs('bad nim!'),                                                      // NIM tidak valid → dilewati
        ]);

        $this->actingAs($this->u('TU'))->post('/master/sinkron-siska/pengguna')
            ->assertSessionHas('sukses', 'Sinkron SISKA selesai: 2 ditambah, 1 diperbarui, 0 tidak berubah, 2 dilewati.');

        $baru = $this->u('30000001');
        $this->assertTrue($baru->hasRole('mahasiswa'));
        $this->assertSame(self::ID_TS, Prodi::find($baru->prodi_id)->siska_id);
        $this->assertSame('2002-05-03', $baru->tanggal_lahir->toDateString());
        $this->assertNull($this->u('30000002')->email);

        $lama = $this->u('21650012');
        $this->assertSame('MUHAMMAD FAUZAN BARU', $lama->nama);
        $this->assertSame('fauzan', $lama->username);                                    // username lokal utuh
        $this->assertTrue($lama->hasRole('mahasiswa'));
        $this->assertSame(1, User::where('nomor_induk', '30000001')->count());
    }

    public function test_sinkron_pengguna_tidak_menimpa_akun_non_mahasiswa(): void
    {
        Prodi::where('kode', 'TS')->update(['siska_id' => self::ID_TS]);
        $this->fakeSiska([$this->mhs('0912038401', ['nama' => 'PALSU'])]);                // NIDN dekan

        $this->actingAs($this->u('TU'))->post('/master/sinkron-siska/pengguna');

        $this->assertNotSame('PALSU', $this->u('0912038401')->nama);
        $this->assertFalse($this->u('0912038401')->hasRole('mahasiswa'));
    }

    public function test_kunci_ditolak_siska_menampilkan_galat_dan_tidak_mengubah_data(): void
    {
        Http::fake(['siska.test/*' => Http::response(['message' => 'x'], 401)]);
        $sebelum = User::count();

        $this->actingAs($this->u('TU'))->post('/master/sinkron-siska/prodi')->assertSessionHas('galat');

        $this->assertSame($sebelum, User::count());
    }

    public function test_redirect_tidak_diikuti_agar_kunci_tidak_bocor_ke_host_lain(): void
    {
        Http::fake([
            'siska.test/*' => Http::response('', 302, ['Location' => 'http://penyerang.test/curi']),
            'penyerang.test/*' => Http::response(['data' => []]),
        ]);

        $this->actingAs($this->u('TU'))->post('/master/sinkron-siska/prodi')->assertSessionHas('galat');

        Http::assertNotSent(fn ($req) => str_contains($req->url(), 'penyerang.test'));
    }

    public function test_kunci_api_tersimpan_terenkripsi_dan_halaman_koneksi_hanya_super_admin(): void
    {
        $this->assertStringNotContainsString('sk_kunci_uji', (string) Pengaturan::ambil('siska_kunci'));
        $this->assertSame('sk_kunci_uji_1234567890abcd', app(SiskaClient::class)->kunci());

        $this->fakeSiska([]);
        $this->actingAs($this->u('TU'))->get('/master/siska')->assertForbidden();
        $this->actingAs($this->u('superadmin'))->get('/master/siska')->assertOk()->assertDontSee('sk_kunci_uji');
        $this->actingAs($this->u('21650012'))->post('/master/sinkron-siska/prodi')->assertForbidden();
    }

    public function test_simpan_pengaturan_tanpa_mengganti_kunci_mempertahankan_kunci_lama(): void
    {
        $this->actingAs($this->u('superadmin'))->put('/master/siska', ['url' => 'http://siska.test/', 'kunci' => '', 'prodi' => [self::ID_TS], 'status' => ['AKTIF', 'Lulus']])
            ->assertRedirect('/master/siska')->assertSessionHasNoErrors();

        $this->assertSame('http://siska.test', app(SiskaClient::class)->url());
        $this->assertSame('sk_kunci_uji_1234567890abcd', app(SiskaClient::class)->kunci());
    }
}
