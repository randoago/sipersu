<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\KunciTte;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AksesDanOtorisasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-a-'.getmypid()]);
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

    public function test_tamu_diarahkan_ke_login_dan_login_dengan_nim(): void
    {
        $this->get('/dasbor')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Selamat Datang Kembali');

        Livewire::test(\App\Livewire\Auth\Login::class)
            ->set('nomor_induk', '21650012')->set('password', 'password')->call('masuk')->assertRedirect(route('dasbor'));
        $this->assertAuthenticatedAs($this->u('21650012'));
    }

    public function test_login_salah_dicatat_dan_dibatasi_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Livewire::test(\App\Livewire\Auth\Login::class)->set('nomor_induk', '21650012')->set('password', 'salah')->call('masuk')->assertHasErrors('nomor_induk');
        }
        // percobaan ke-6 diblokir meski sandi benar
        Livewire::test(\App\Livewire\Auth\Login::class)->set('nomor_induk', '21650012')->set('password', 'password')->call('masuk')->assertHasErrors('nomor_induk');
        $this->assertGuest();
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'login_gagal']);
    }

    public function test_semua_peran_dapat_membuka_dasbor(): void
    {
        foreach (['0000000001', '198701012010011001', '0912038401', '0912048102', '0912078603', '0912088704', '21650012'] as $nik) {
            $this->actingAs($this->u($nik))->get('/dasbor')->assertOk();
        }
    }

    public function test_mahasiswa_tidak_bisa_membuka_antrean_petugas_dan_pengajuan_orang_lain(): void
    {
        $alur = app(AlurPengajuan::class);
        $lain = User::factory()->create(['nomor_induk' => '99999999', 'prodi_id' => $this->u('21650012')->prodi_id]);
        $lain->assignRole('mahasiswa');
        $p = $alur->ajukan($this->u('21650012'), JenisSurat::first(), ['keperluan' => 'BPJS', 'semester' => '7', 'tahun_akademik' => 'x', 'keterangan' => '']);

        $this->actingAs($lain)->get('/pengajuan')->assertForbidden();
        $this->actingAs($lain)->get("/pengajuan/{$p->id}")->assertForbidden();
        $this->actingAs($this->u('21650012'))->get("/pengajuan/{$p->id}")->assertOk()->assertSee($p->kode);
        $this->actingAs($this->u('21650012'))->post("/pengajuan/{$p->id}/verifikasi")->assertForbidden();
    }

    public function test_persetujuan_ttd_butuh_pejabat_dan_kata_sandi(): void
    {
        $alur = app(AlurPengajuan::class);
        $p = $alur->ajukan($this->u('21650012'), JenisSurat::where('kode', 'KET-AKTIF')->first(), ['keperluan' => 'BPJS', 'semester' => '7', 'tahun_akademik' => 'x', 'keterangan' => '']);
        $alur->verifikasi($p, $this->u('198701012010011001'));

        $this->actingAs($this->u('198701012010011001'))->post("/persetujuan/{$p->id}/tandatangani", ['password' => 'password'])->assertForbidden();
        $this->actingAs($this->u('0912038401'))->post("/persetujuan/{$p->id}/tandatangani", ['password' => 'salah'])->assertSessionHasErrors('password');
        $this->assertNull($p->fresh()->surat->nomor);
        $this->actingAs($this->u('0912038401'))->post("/persetujuan/{$p->id}/tandatangani", ['password' => 'password'])->assertRedirect();
        $this->assertNotNull($p->fresh()->surat->nomor);
    }

    public function test_pengajuan_lewat_formulir_livewire_dengan_unggah_berkas(): void
    {
        $mhs = $this->u('21650012');
        $jenis = JenisSurat::where('kode', 'KET-AKTIF')->first();

        Livewire::actingAs($mhs)->test(\App\Livewire\AjukanSurat::class, ['jenis' => $jenis])
            ->set('isian.keperluan', 'Pengurusan BPJS')->set('isian.semester', '7')->set('isian.tahun_akademik', '2026/2027 Ganjil')
            ->call('lanjut')->assertSet('langkah', 2)
            ->set('berkas.0', UploadedFile::fake()->create('ktm.pdf', 300, 'application/pdf'))
            ->set('berkas.1', UploadedFile::fake()->image('spp.jpg')->size(500))
            ->call('lanjut')->assertSet('langkah', 3)
            ->call('kirim')->assertHasErrors('setuju')
            ->set('setuju', true)->call('kirim')->assertHasNoErrors();

        $p = Pengajuan::firstOrFail();
        $this->assertSame('diajukan', $p->status->value);
        $this->assertSame(2, $p->lampiran()->count());
        Storage::disk('local')->assertExists($p->lampiran()->first()->path);
    }

    public function test_unggah_hanya_pdf_jpg_png_maks_2mb(): void
    {
        $mhs = $this->u('21650012');
        $jenis = JenisSurat::where('kode', 'KET-AKTIF')->first();
        $t = Livewire::actingAs($mhs)->test(\App\Livewire\AjukanSurat::class, ['jenis' => $jenis])
            ->set('isian.keperluan', 'Lainnya')->set('isian.semester', '3')->set('isian.tahun_akademik', 'x')->call('lanjut');

        $t->set('berkas.0', UploadedFile::fake()->create('besar.pdf', 3000, 'application/pdf'))->assertHasErrors('berkas.0');
        $t->set('berkas.0', UploadedFile::fake()->create('virus.exe', 10))->assertHasErrors('berkas.0');
        $t->set('berkas.0', UploadedFile::fake()->create('doc.docx', 10))->assertHasErrors('berkas.0');
    }

    public function test_lampiran_hanya_bisa_diunduh_pihak_berwenang(): void
    {
        $mhs = $this->u('21650012');
        $p = app(AlurPengajuan::class)->ajukan($mhs, JenisSurat::first(), ['keperluan' => 'BPJS', 'semester' => '7', 'tahun_akademik' => 'x', 'keterangan' => '']);
        Storage::disk('local')->put('lampiran/pengajuan/1/a.pdf', '%PDF-1');
        $l = $p->lampiran()->create(['label' => 'KTM', 'nama_asli' => 'ktm.pdf', 'path' => 'lampiran/pengajuan/1/a.pdf', 'mime' => 'application/pdf', 'ukuran' => 7]);
        $asing = User::factory()->create(['nomor_induk' => '88888888']);
        $asing->assignRole('mahasiswa');

        $this->actingAs($asing)->get("/lampiran/{$l->id}")->assertForbidden();
        $this->actingAs($mhs)->get("/lampiran/{$l->id}")->assertOk();
        $this->actingAs($this->u('198701012010011001'))->get("/lampiran/{$l->id}")->assertOk();
    }

    public function test_fase_1_hanya_jaringan_lokal_ip_lain_ditolak_di_semua_rute(): void
    {
        foreach (['10.1.2.3', '172.20.5.5', '192.168.1.20', '127.0.0.1', '100.100.1.1'] as $ip) {   // privat + loopback + Tailscale
            $this->withServerVariables(['REMOTE_ADDR' => $ip])->get('/login')->assertOk();
        }
        foreach (['203.0.113.9', '8.8.8.8', '100.63.255.255', '172.32.0.1'] as $ip) {
            foreach (['/login', '/verifikasi', '/v/'.str_repeat('z', 43), '/dasbor'] as $path) {
                $this->withServerVariables(['REMOTE_ADDR' => $ip])->get($path)->assertForbidden();
            }
        }
    }

    public function test_header_proksi_tidak_dapat_memalsukan_alamat_lokal(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->withHeaders(['X-Forwarded-For' => '192.168.1.5', 'CF-Connecting-IP' => '192.168.1.5'])
            ->get('/login')->assertForbidden();
    }
}
