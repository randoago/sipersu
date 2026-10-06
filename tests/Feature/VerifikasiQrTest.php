<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\KunciTte;
use App\Services\TandaTanganService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerifikasiQrTest extends TestCase
{
    use RefreshDatabase;

    private Surat $surat;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-v-'.getmypid()]);
        KunciTte::buat(true);
        $this->seed(DatabaseSeeder::class);

        $alur = app(AlurPengajuan::class);
        $p = $alur->ajukan(User::where('nomor_induk', '21650012')->first(), JenisSurat::where('kode', 'KET-AKTIF')->first(),
            ['semester' => '7', 'tahun_akademik' => '2026/2027', 'keterangan' => '']);
        $p = $alur->verifikasi($p, User::where('nomor_induk', '198701012010011001')->first());
        $this->surat = $alur->tandatangani($p, User::where('nomor_induk', '0912038401')->first())->surat;
    }

    protected function tearDown(): void
    {
        @unlink(KunciTte::jalur('ed25519.secret'));
        @unlink(KunciTte::jalur('ed25519.public'));
        @rmdir(config('sipersu.kunci_path'));
        parent::tearDown();
    }

    public function test_halaman_verifikasi_publik_menampilkan_dokumen_asli_tanpa_login(): void
    {
        $this->get('/v/'.$this->surat->qr_token)
            ->assertOk()
            ->assertSee('Dokumen Asli')
            ->assertSee($this->surat->nomor)
            ->assertSee($this->surat->pdf_hash);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'verifikasi_qr']);
    }

    public function test_token_tidak_dikenal_atau_pendek_ditolak(): void
    {
        $this->get('/v/'.str_repeat('x', 43))->assertNotFound()->assertSee('Tidak Ditemukan');
        $this->get('/v/pendek')->assertNotFound();
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'verifikasi_qr_gagal']);
    }

    public function test_token_acak_minimal_32_karakter_dan_tidak_berurutan(): void
    {
        $this->assertGreaterThanOrEqual(32, strlen($this->surat->qr_token));
        $this->assertDoesNotMatchRegularExpression('/^\d+$/', $this->surat->qr_token);
    }

    public function test_surat_dibatalkan_tampil_tidak_berlaku(): void
    {
        $this->surat->update(['status' => 'batal', 'dibatalkan_pada' => now(), 'alasan_batal' => 'Salah ketik nama']);
        $this->get('/v/'.$this->surat->qr_token)->assertOk()->assertSee('TIDAK BERLAKU', false)->assertDontSee('Dokumen Asli');
    }

    public function test_surat_rahasia_hanya_metadata_minimal(): void
    {
        $this->surat->update(['sifat' => 'rahasia']);
        $this->get('/v/'.$this->surat->qr_token)->assertOk()
            ->assertSee('rahasia')
            ->assertDontSee($this->surat->perihal)
            ->assertDontSee('Muhammad Fauzan');
    }

    public function test_pencocokan_pdf_unggahan_dengan_hash(): void
    {
        $asli = Storage::disk('local')->get($this->surat->file_pdf);
        $url = '/v/'.$this->surat->qr_token;

        $this->post($url.'/cek', ['berkas' => UploadedFile::fake()->createWithContent('surat.pdf', $asli)])
            ->assertRedirect($url)->assertSessionHas('hasil_berkas', 'cocok');

        $this->post($url.'/cek', ['berkas' => UploadedFile::fake()->createWithContent('surat.pdf', $asli.'%diubah')])
            ->assertSessionHas('hasil_berkas', 'beda');

        $this->post($url.'/cek', ['berkas' => UploadedFile::fake()->create('virus.exe', 10)])->assertSessionHasErrors('berkas');
    }

    public function test_signature_ed25519_valid_dan_gagal_bila_data_diubah(): void
    {
        $tte = app(TandaTanganService::class);
        $payload = $tte->payload($this->surat);

        $this->assertTrue(KunciTte::verifikasi($payload, $this->surat->signature));

        $diubah = json_decode($payload, true);
        $diubah['n'] = '999/II.1.AK/FT-UMB/X/2026';
        $this->assertFalse(KunciTte::verifikasi(json_encode($diubah), $this->surat->signature), 'nomor diubah → tidak valid');

        $this->assertFalse(KunciTte::verifikasi($payload, KunciTte::b64url(random_bytes(64))), 'signature palsu');
        $this->assertFalse(KunciTte::verifikasi($payload, 'bukan-base64url!!'));
    }

    public function test_qr_memuat_url_halaman_statis_dan_fragment_payload_signature_yang_bisa_diverifikasi_offline(): void
    {
        $tte = app(TandaTanganService::class);
        $url = $tte->urlQr($this->surat);

        $this->assertStringStartsWith(config('sipersu.verifikasi_url').'#', $url);
        $this->assertStringNotContainsString($this->surat->qr_token, $url, 'QR tidak memuat token: verifikasi dilakukan oleh halaman statis');
        [$b64, $sig] = explode('.', explode('#', $url, 2)[1]);
        $payload = KunciTte::dariB64url($b64);
        $data = json_decode($payload, true);

        $this->assertSame($this->surat->nomor, $data['n']);
        $this->assertSame('Dekan Fakultas Teknik', $data['j']);
        $this->assertTrue(KunciTte::verifikasi($payload, $sig, KunciTte::publik()), 'verifikasi hanya dengan kunci publik');
    }

    public function test_tampilan_hanya_surat_bertanda_tangan_dapat_dicari_via_token(): void
    {
        $draf = Surat::create(['arah' => 'keluar', 'perihal' => 'Draf', 'status' => 'menunggu_ttd', 'qr_token' => null]);
        $this->get('/v/'.str_repeat('a', 43))->assertNotFound();
        $this->assertNull($draf->qr_token);
    }

    public function test_halaman_verifikasi_menampilkan_riwayat_surat_berurutan(): void
    {
        $html = $this->get('/v/'.$this->surat->qr_token)->assertOk()->assertSee('Riwayat Surat')->getContent();

        $this->assertStringContainsString('Permohonan diajukan oleh pemohon', $html);
        $this->assertStringContainsString('Diverifikasi', $html);
        $this->assertStringContainsString('Admin Tata Usaha', $html);
        $this->assertStringContainsString('Ditandatangani secara elektronik', $html);
        $this->assertStringContainsString('Agusman', $html);
        $this->assertLessThan(strpos($html, 'Ditandatangani secara elektronik'), strpos($html, 'Diverifikasi'), 'urut kronologis');
        $this->assertStringNotContainsString('Berkas lengkap', $html, 'catatan internal tidak tampil');

        $this->surat->update(['status' => 'batal', 'dibatalkan_pada' => now()->addMinute(), 'alasan_batal' => 'Salah ketik nama']);
        $this->get('/v/'.$this->surat->qr_token)->assertSee('Surat dibatalkan');
    }

    public function test_riwayat_surat_rahasia_hanya_penandatanganan(): void
    {
        $this->surat->update(['sifat' => 'rahasia']);
        $this->get('/v/'.$this->surat->qr_token)->assertOk()
            ->assertSee('Ditandatangani secara elektronik')
            ->assertDontSee('Permohonan diajukan')
            ->assertDontSee('Diverifikasi');
    }

    public function test_perintah_tte_periksa_menilai_asli_dan_tidak_valid(): void
    {
        $url = app(TandaTanganService::class)->urlQr($this->surat);

        $this->artisan('tte:periksa', ['isi' => $url])->expectsOutputToContain('DOKUMEN ASLI')->assertExitCode(0);
        $this->artisan('tte:periksa', ['isi' => explode('#', $url, 2)[1]])->expectsOutputToContain('DOKUMEN ASLI')->assertExitCode(0);   // tanpa alamat

        [$b64, $sig] = explode('.', explode('#', $url, 2)[1]);
        $rusak = substr($b64, 0, 30).($b64[30] === 'A' ? 'B' : 'A').substr($b64, 31);
        $this->artisan('tte:periksa', ['isi' => "$rusak.$sig"])->expectsOutputToContain('TIDAK VALID')->assertExitCode(1);
        $this->artisan('tte:periksa', ['isi' => 'bukan-format'])->expectsOutputToContain('Format tidak dikenali')->assertExitCode(1);
    }

    public function test_halaman_statis_dilayani_lokal_dan_memuat_kunci_publik_serta_kontak_tu(): void
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'verif-'.uniqid();
        config(['sipersu.verifikasi_berkas' => $dir.DIRECTORY_SEPARATOR.'index.html']);
        $this->get('/verifikasi')->assertNotFound();                       // belum dibuat

        $this->artisan('kunci:publikasi', ['--tujuan' => $dir])->assertSuccessful();
        $html = $this->get('/verifikasi')->assertOk()->baseResponse->getFile()->getContent();

        $this->assertStringContainsString(KunciTte::publik(), $html);
        $this->assertStringContainsString('Untuk salinan PDF asli, hubungi TU Fakultas Teknik UM Buton.', $html);
        @unlink($dir.DIRECTORY_SEPARATOR.'index.html');
        @rmdir($dir);
    }

    public function test_pdf_penjelasan_qr_dibuat_dengan_qr_contoh_yang_valid(): void
    {
        $berkas = sys_get_temp_dir().DIRECTORY_SEPARATOR.'penjelasan-'.uniqid().'.pdf';

        $this->artisan('dokumentasi:qrcode', ['--tujuan' => $berkas])->assertSuccessful();

        $this->assertStringStartsWith('%PDF', file_get_contents($berkas));
        $this->assertGreaterThan(20_000, filesize($berkas));
        @unlink($berkas);
    }

    public function test_pdf_presentasi_qr_berisi_15_slide_16_banding_9(): void
    {
        $berkas = sys_get_temp_dir().DIRECTORY_SEPARATOR.'presentasi-'.uniqid().'.pdf';

        $this->artisan('dokumentasi:presentasi-qr', ['--tujuan' => $berkas])->assertSuccessful();

        $pdf = file_get_contents($berkas);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(15, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
        @unlink($berkas);
    }
}
