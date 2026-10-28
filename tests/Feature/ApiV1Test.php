<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\Surat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\KunciTte;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\DemoSuratMasukSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private Surat $terbit;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-api-'.getmypid()]);
        KunciTte::buat(true);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSuratMasukSeeder::class);

        $alur = app(AlurPengajuan::class);
        $p = $alur->ajukan($this->u('21650012'), JenisSurat::where('kode', 'KET-AKTIF')->first(), ['semester' => '7', 'tahun_akademik' => '2026/2027', 'keterangan' => '']);
        $p = $alur->verifikasi($p, $this->u('198701012010011001'));
        $this->terbit = $alur->tandatangani($p, $this->u('0912038401'))->surat;
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
        return User::where('nomor_induk', $nik)->orWhere('username', $nik)->firstOrFail();
    }

    private function sebagai(string $nik): static
    {
        Sanctum::actingAs($this->u($nik), ['baca']);

        return $this;
    }

    // ---- token ---------------------------------------------------------------------------------------------

    public function test_token_dibuat_dengan_npm_atau_username_dan_dapat_dipakai(): void
    {
        foreach (['21650012', 'TU'] as $login) {
            $r = $this->postJson('/api/v1/auth/token', ['nomor_induk' => $login, 'password' => 'password', 'nama_perangkat' => 'uji'])
                ->assertCreated()->assertJsonStructure(['token', 'token_type', 'kedaluwarsa_pada', 'pengguna' => ['id', 'nomor_induk', 'nama', 'peran', 'prodi']]);
            $this->app['auth']->forgetGuards();
            $this->withToken($r->json('token'))->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.nama', $this->u($r->json('pengguna.nomor_induk'))->nama);
            $this->app['auth']->forgetGuards();
        }
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'api_token_dibuat']);
    }

    public function test_token_ditolak_untuk_sandi_salah_dan_akun_nonaktif(): void
    {
        $this->postJson('/api/v1/auth/token', ['nomor_induk' => 'TU', 'password' => 'salah'])->assertUnauthorized();
        $this->u('21650012')->update(['aktif' => false]);
        $this->postJson('/api/v1/auth/token', ['nomor_induk' => '21650012', 'password' => 'password'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/token', ['nomor_induk' => 'TU'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'api_login_gagal']);
    }

    public function test_lima_kali_salah_memblokir_sementara(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/token', ['nomor_induk' => 'TU', 'password' => 'salah'])->assertUnauthorized();
        }
        $this->postJson('/api/v1/auth/token', ['nomor_induk' => 'TU', 'password' => 'password'])->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_tanpa_token_dijawab_json_401_dan_token_dapat_dicabut(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->get('/api/v1/pengajuan')->assertUnauthorized();   // tanpa header Accept: tetap JSON, bukan pengalihan ke halaman login

        $token = $this->postJson('/api/v1/auth/token', ['nomor_induk' => 'TU', 'password' => 'password'])->json('token');
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->deleteJson('/api/v1/auth/token')->assertNoContent();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_token_tidak_berlaku_lagi_setelah_akun_dinonaktifkan(): void
    {
        $token = $this->postJson('/api/v1/auth/token', ['nomor_induk' => 'TU', 'password' => 'password'])->json('token');
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertOk();

        $this->u('TU')->update(['aktif' => false]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized()->assertJson(['message' => 'Akun tidak aktif.']);
        $this->assertSame(0, $this->u('TU')->tokens()->count());
    }

    public function test_sesi_web_tidak_dianggap_login_di_api(): void
    {
        $this->actingAs($this->u('TU'))->getJson('/api/v1/me')->assertUnauthorized();
    }

    // ---- katalog -------------------------------------------------------------------------------------------

    public function test_mahasiswa_hanya_melihat_format_mahasiswa_dan_rincian_memuat_isian(): void
    {
        $r = $this->sebagai('21650012')->getJson('/api/v1/jenis-surat')->assertOk();
        $this->assertCount(5, $r->json('data'));
        $this->assertSame(['mahasiswa'], array_values(array_unique(array_column($r->json('data'), 'sasaran'))));

        $this->getJson('/api/v1/jenis-surat/KET-AKTIF')->assertOk()->assertJsonPath('data.isian.0.nama', 'semester')->assertJsonStructure(['data' => ['syarat_berkas']]);
        $this->getJson('/api/v1/jenis-surat/SURAT-TUGAS')->assertNotFound();
        $this->assertGreaterThan(5, count($this->sebagai('TU')->getJson('/api/v1/jenis-surat')->json('data')));
    }

    // ---- pengajuan -----------------------------------------------------------------------------------------

    public function test_mahasiswa_hanya_melihat_pengajuan_sendiri(): void
    {
        $milik = Pengajuan::where('user_id', $this->u('21650012')->id)->count();
        $r = $this->sebagai('21650012')->getJson('/api/v1/pengajuan')->assertOk();
        $this->assertCount($milik, $r->json('data'));

        $orang = Pengajuan::where('user_id', $this->u('21650034')->id)->firstOrFail();
        $this->getJson('/api/v1/pengajuan/'.$orang->kode)->assertForbidden();
        $this->getJson('/api/v1/pengajuan/'.Pengajuan::where('user_id', $this->u('21650012')->id)->value('kode'))->assertOk()
            ->assertJsonStructure(['data' => ['kode', 'status' => ['kode', 'label'], 'persetujuan', 'lampiran', 'isian', 'pemohon']]);
    }

    public function test_kaprodi_melihat_prodinya_tu_dan_dekan_melihat_semua(): void
    {
        $semua = Pengajuan::count();
        $this->assertCount($semua, $this->sebagai('TU')->getJson('/api/v1/pengajuan?per_page=50')->json('data'));
        $this->assertCount($semua, $this->sebagai('0912038401')->getJson('/api/v1/pengajuan?per_page=50')->json('data'));

        $kaprodi = $this->u('0912068502');   // RSK
        $r = $this->sebagai('0912068502')->getJson('/api/v1/pengajuan?per_page=50')->assertOk();
        $this->assertNotEmpty($r->json('data'));
        $this->assertLessThan($semua, count($r->json('data')));
        foreach ($r->json('data') as $p) {
            $this->assertSame($kaprodi->prodi->nama, $p['pemohon']['prodi']);
        }
    }

    public function test_pengajuan_dapat_disaring_menurut_status(): void
    {
        $r = $this->sebagai('TU')->getJson('/api/v1/pengajuan?status=ditolak')->assertOk();
        $this->assertNotEmpty($r->json('data'));
        foreach ($r->json('data') as $p) {
            $this->assertSame('ditolak', $p['status']['kode']);
        }
        $this->getJson('/api/v1/pengajuan?status=ngawur')->assertUnprocessable();
    }

    // ---- surat keluar & masuk ------------------------------------------------------------------------------

    public function test_surat_keluar_mahasiswa_ditolak_dan_surat_pengajuan_tidak_masuk_daftar_staf(): void
    {
        $this->sebagai('21650012')->getJson('/api/v1/surat-keluar')->assertForbidden();
        $this->sebagai('TU')->getJson('/api/v1/surat-keluar')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/surat-keluar/'.$this->terbit->id)->assertNotFound();
    }

    public function test_surat_keluar_staf_terlihat_oleh_pembuat_dan_admin_tetapi_tidak_oleh_dosen_lain(): void
    {
        $surat = Surat::create(['arah' => 'keluar', 'perihal' => 'Undangan Rapat', 'sifat' => 'biasa', 'asal_tujuan' => 'Dosen', 'status' => 'draf',
            'dibuat_oleh' => $this->u('0912088704')->id, 'mode_ttd' => 'qr', 'klasifikasi_id' => $this->terbit->klasifikasi_id]);

        $this->sebagai('0912088704')->getJson('/api/v1/surat-keluar')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.perihal', 'Undangan Rapat');
        $this->getJson('/api/v1/surat-keluar/'.$surat->id)->assertOk()->assertJsonStructure(['data' => ['tahapan', 'lampiran']]);
        $this->sebagai('TU')->getJson('/api/v1/surat-keluar/'.$surat->id)->assertOk();
        $this->sebagai('0912058301')->getJson('/api/v1/surat-keluar/'.$surat->id)->assertForbidden();   // Kaprodi lain, bukan pembuat/penandatangan
    }

    public function test_surat_masuk_rahasia_hanya_untuk_admin_dekan_dan_wakil_dekan(): void
    {
        $rahasia = Surat::where('arah', 'masuk')->where('sifat', 'rahasia')->firstOrFail();

        $this->sebagai('21650012')->getJson('/api/v1/surat-masuk')->assertForbidden();
        $this->sebagai('0912088704')->getJson('/api/v1/surat-masuk')->assertForbidden();   // dosen

        $kaprodi = $this->sebagai('0912058301')->getJson('/api/v1/surat-masuk?per_page=50')->assertOk();
        $this->assertNotContains($rahasia->id, array_column($kaprodi->json('data'), 'id'));
        $this->getJson('/api/v1/surat-masuk/'.$rahasia->id)->assertForbidden();

        $tu = $this->sebagai('TU')->getJson('/api/v1/surat-masuk?per_page=50')->assertOk();
        $this->assertContains($rahasia->id, array_column($tu->json('data'), 'id'));
        $this->getJson('/api/v1/surat-masuk/'.$rahasia->id)->assertOk()->assertJsonPath('data.arah', 'masuk')->assertJsonStructure(['data' => ['nomor', 'asal', 'tgl_diterima', 'no_agenda', 'lampiran']]);
        $this->sebagai('0912038401')->getJson('/api/v1/surat-masuk/'.$rahasia->id)->assertOk();
    }

    // ---- pembukuan -----------------------------------------------------------------------------------------

    public function test_pembukuan_hanya_admin_dekan_wakil_dekan_dan_menyamarkan_rahasia_bagi_pimpinan(): void
    {
        $this->sebagai('21650012')->getJson('/api/v1/pembukuan')->assertForbidden();
        $this->sebagai('0912058301')->getJson('/api/v1/pembukuan')->assertForbidden();

        $perihal = Surat::where('arah', 'masuk')->where('sifat', 'rahasia')->value('perihal');
        $admin = $this->sebagai('TU')->getJson('/api/v1/pembukuan?arah=masuk&per_page=100')->assertOk()->assertJsonStructure(['data', 'meta' => ['total']]);
        $this->assertContains($perihal, array_column($admin->json('data'), 'perihal'));

        foreach ($this->sebagai('TU')->getJson('/api/v1/pembukuan?per_page=100')->json('data') as $b) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $b['tgl_surat']);   // tanggal tanpa jam
        }

        $dekan = $this->sebagai('0912038401')->getJson('/api/v1/pembukuan?arah=masuk&per_page=100')->assertOk();
        $this->assertNotContains($perihal, array_column($dekan->json('data'), 'perihal'));
        $this->assertContains('(rahasia)', array_column($dekan->json('data'), 'perihal'));
    }

    // ---- verifikasi ----------------------------------------------------------------------------------------

    public function test_verifikasi_memeriksa_tanda_tangan_dan_status_batal(): void
    {
        $this->sebagai('21650012')->getJson('/api/v1/verifikasi/'.$this->terbit->qr_token)->assertOk()
            ->assertJsonPath('data.tanda_tangan_sah', true)->assertJsonPath('data.berlaku', true)
            ->assertJsonPath('data.nomor', $this->terbit->nomor)->assertJsonPath('data.pdf_sha256', $this->terbit->pdf_hash);

        $this->terbit->update(['status' => 'batal', 'dibatalkan_pada' => now(), 'alasan_batal' => 'Salah ketik']);
        $this->getJson('/api/v1/verifikasi/'.$this->terbit->qr_token)->assertOk()
            ->assertJsonPath('data.tanda_tangan_sah', true)->assertJsonPath('data.berlaku', false)->assertJsonPath('data.status', 'batal');

        $this->terbit->update(['perihal' => 'Diubah diam-diam']);   // data di basis data tidak lagi cocok dengan tanda tangan
        $this->getJson('/api/v1/verifikasi/'.$this->terbit->qr_token)->assertOk()->assertJsonPath('data.tanda_tangan_sah', false);

        $this->getJson('/api/v1/verifikasi/'.str_repeat('x', 43))->assertNotFound();
        $this->getJson('/api/v1/verifikasi/pendek')->assertNotFound();
    }

    public function test_surat_pengajuan_terbit_muncul_di_rincian_pengajuan(): void
    {
        $this->sebagai('21650012')->getJson('/api/v1/pengajuan/'.$this->terbit->pengajuan->kode)->assertOk()
            ->assertJsonPath('data.surat.nomor', $this->terbit->nomor)->assertJsonPath('data.status.kode', 'ditandatangani');
    }

    public function test_akses_dari_luar_jaringan_lokal_ditolak_dengan_json(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->postJson('/api/v1/auth/token', ['nomor_induk' => 'TU', 'password' => 'password'])->assertForbidden()->assertJsonStructure(['message']);
    }
}
