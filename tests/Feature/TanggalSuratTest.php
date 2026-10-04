<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\KlasifikasiSurat;
use App\Models\Pengaturan;
use App\Models\Surat;
use App\Models\User;
use App\Services\KunciTte;
use App\Services\PenomoranService;
use App\Services\TandaTanganService;
use App\Support\TanggalHijriah;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TanggalSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-tgl-'.getmypid()]);
        KunciTte::buat(true);
        $this->seed(DatabaseSeeder::class);
        $this->travelTo(Carbon::parse('2026-10-04 10:00:00', config('app.timezone')));
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

    private function bebas(array $o = []): array
    {
        return $o + [
            'klasifikasi_id' => KlasifikasiSurat::where('kode', 'II.3.AU')->value('id'), 'sifat' => 'biasa', 'tujuan' => 'Kepala Dinas', 'perihal' => 'Uji Tanggal',
            'lampiran' => '-', 'isi' => 'Isi surat uji.', 'salam' => '1', 'jabatan_id' => Jabatan::where('kode', 'dekan')->value('id'), 'paraf_role' => '', 'mode_ttd' => 'qr',
        ];
    }

    private function tandatangani(Surat $s): Surat
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
        $this->actingAs($this->u('0912038401'))->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password'])->assertRedirect();

        return $s->fresh();
    }

    public function test_konversi_hijriah_untuk_pemilih_tanggal(): void
    {
        $this->get('/tanggal/hijriah?tgl=2026-04-06')->assertRedirect('/login');
        $tu = $this->actingAs($this->u('198701012010011001'));

        $tu->getJson('/tanggal/hijriah?tgl=2026-04-06')->assertOk()->assertJson(['hijriah' => '18 Syawal 1447 H', 'masehi' => 'Senin, 6 April 2026']);
        $tu->getJson('/tanggal/hijriah?tgl=bukan-tanggal')->assertStatus(422);
        $tu->getJson('/tanggal/hijriah')->assertStatus(422);

        Pengaturan::simpan('hijriah_koreksi', '-1');
        $tu->getJson('/tanggal/hijriah?tgl=2026-04-06')->assertJson(['hijriah' => '17 Syawal 1447 H']);
        Pengaturan::simpan('hijriah_koreksi', '1');
        $tu->getJson('/tanggal/hijriah?tgl=2026-04-06')->assertJson(['hijriah' => '19 Syawal 1447 H']);
    }

    public function test_formulir_memuat_pemilih_tanggal_dengan_bawaan_hari_ini(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        foreach (['/surat-keluar/format/UNDANGAN-RAPAT', '/surat-keluar/buat/bebas'] as $url) {
            $tu->get($url)->assertOk()->assertSee('Tanggal surat')->assertSee('name="tanggal_surat"', false)->assertSee('2026-10-04', false)
                ->assertSee('min="2026-09-04"', false)->assertSee('max="2027-01-02"', false);
        }
    }

    public function test_tanpa_pilihan_tanggal_surat_ikut_hari_penandatanganan(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/surat-keluar', $this->bebas())->assertSessionHasNoErrors();
        $s = Surat::firstOrFail();
        $this->assertNull($s->tgl_surat, 'kosong = ikut hari penandatanganan');

        $this->travel(2)->days();                                   // Dekan menandatangani 2 hari kemudian
        $s = $this->tandatangani($s);
        $this->assertSame('2026-10-06', $s->tgl_surat->toDateString());
        $this->assertSame('001/II.3.AU/FT-UMB/X/2026', $s->nomor);
    }

    public function test_memilih_hari_ini_sama_dengan_bawaan(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/surat-keluar', $this->bebas(['tanggal_surat' => '2026-10-04']));
        $this->assertNull(Surat::firstOrFail()->tgl_surat);
        $this->actingAs($this->u('198701012010011001'))->post('/surat-keluar', $this->bebas(['perihal' => 'Dua', 'tanggal_surat' => '']));
        $this->assertNull(Surat::where('perihal', 'Dua')->first()->tgl_surat);
    }

    public function test_tanggal_terpilih_dipakai_pada_nomor_qr_dan_pdf(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/surat-keluar', $this->bebas(['tanggal_surat' => '2026-11-12']))->assertSessionHasNoErrors();
        $s = $this->tandatangani(Surat::firstOrFail());

        $this->assertSame('2026-11-12', $s->tgl_surat->toDateString());
        $this->assertSame('001/II.3.AU/FT-UMB/XI/2026', $s->nomor, 'bulan romawi mengikuti tanggal surat, bukan hari penandatanganan');
        $payload = json_decode(app(TandaTanganService::class)->payload($s), true);
        $this->assertSame('2026-11-12', $payload['t']);
        $this->assertTrue(KunciTte::verifikasi(app(TandaTanganService::class)->payload($s), $s->signature));
        $this->assertSame('2026-10-04', $s->ditandatangani_pada->toDateString(), 'waktu tanda tangan sebenarnya tetap tercatat');
        Storage::disk('local')->assertExists($s->file_pdf);
        $this->get('/v/'.$s->qr_token)->assertOk()->assertSee('12 November 2026');
    }

    public function test_tanggal_tahun_depan_memakai_counter_tahun_tersebut(): void
    {
        $this->travelTo(Carbon::parse('2026-12-20 09:00:00', config('app.timezone')));
        $this->actingAs($this->u('198701012010011001'))->post('/surat-keluar', $this->bebas(['tanggal_surat' => '2027-01-05']));
        $s = $this->tandatangani(Surat::firstOrFail());
        $this->assertSame('001/II.3.AU/FT-UMB/I/2027', $s->nomor);

        $this->actingAs($this->u('198701012010011001'))->post('/surat-keluar', $this->bebas(['perihal' => 'Biasa']));
        $b = $this->tandatangani(Surat::where('perihal', 'Biasa')->first());
        $this->assertSame('001/II.3.AU/FT-UMB/XII/2026', $b->nomor, 'counter 2026 terpisah dari 2027');
    }

    public function test_batas_tanggal_30_hari_ke_belakang_dan_90_hari_ke_depan(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/surat-keluar', $this->bebas(['tanggal_surat' => '2026-09-03']))->assertSessionHasErrors('tanggal_surat');   // -31 hari
        $tu->post('/surat-keluar', $this->bebas(['tanggal_surat' => '2027-01-03']))->assertSessionHasErrors('tanggal_surat');   // +91 hari
        $tu->post('/surat-keluar', $this->bebas(['tanggal_surat' => '04/10/2026']))->assertSessionHasErrors('tanggal_surat');
        $tu->post('/surat-keluar', $this->bebas(['tanggal_surat' => 'besok']))->assertSessionHasErrors('tanggal_surat');
        $this->assertSame(0, Surat::count());

        $tu->post('/surat-keluar', $this->bebas(['perihal' => 'Batas bawah', 'tanggal_surat' => '2026-09-04']))->assertSessionHasNoErrors();
        $tu->post('/surat-keluar', $this->bebas(['perihal' => 'Batas atas', 'tanggal_surat' => '2027-01-02']))->assertSessionHasNoErrors();
        $this->assertSame(2, Surat::count());
    }

    public function test_surat_dari_format_menyimpan_dan_mengubah_tanggal(): void
    {
        $isian = ['kepada' => 'Bapak/Ibu Tim', 'perihal' => 'Undangan Rapat', 'sehubungan' => 'persiapan', 'sebagai' => 'menghadiri rapat', 'hari_tanggal' => '2026-10-20', 'waktu' => '10.00', 'tempat' => 'Aula'];
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/surat-keluar/format/UNDANGAN-RAPAT', ['isian' => $isian, 'tanggal_surat' => '2026-10-15'])->assertSessionHasNoErrors();
        $s = Surat::firstOrFail();
        $this->assertSame('2026-10-15', $s->tgl_surat->toDateString());

        $tu->get("/surat-keluar/{$s->id}/ubah")->assertOk()->assertSee('value="2026-10-15"', false);
        $tu->get("/surat-keluar/{$s->id}")->assertOk()->assertSee('15 Oktober 2026')->assertSee(TanggalHijriah::format(Carbon::parse('2026-10-15')));

        $tu->put("/surat-keluar/{$s->id}", ['isian' => $isian, 'tanggal_surat' => '2026-10-04'])->assertSessionHasNoErrors();
        $this->assertNull($s->fresh()->tgl_surat, 'dikembalikan ke hari ini = ikut penandatanganan');
        $tu->get("/surat-keluar/{$s->id}")->assertSee('Tanggal: hari penandatanganan');
    }

    public function test_pratinjau_memakai_tanggal_terpilih_beserta_hijriahnya(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $html = $tu->postJson('/surat-keluar/pratinjau', ['format' => 'UNDANGAN-RAPAT', 'tanggal_surat' => '2026-11-12'])->assertOk()->json('html');
        $this->assertStringContainsString(TanggalHijriah::format(Carbon::parse('2026-11-12')), $html, 'Hijriah mengikuti tanggal terpilih');
        $this->assertStringContainsString('12 November 2026 M', $html);

        $biasa = $tu->postJson('/surat-keluar/pratinjau', ['format' => 'SURAT-TUGAS', 'tanggal_surat' => '2026-11-12'])->json('html');
        $this->assertStringContainsString('Pada tanggal   : 12 November 2026', html_entity_decode(str_replace('&nbsp;', ' ', $biasa)) ?: '');

        $default = $tu->postJson('/surat-keluar/pratinjau', ['format' => 'UNDANGAN-RAPAT'])->json('html');
        $this->assertStringContainsString('04 Oktober 2026 M', $default, 'tanpa pilihan = hari ini');
        $abaikan = $tu->postJson('/surat-keluar/pratinjau', ['format' => 'UNDANGAN-RAPAT', 'tanggal_surat' => 'ngawur'])->assertOk()->json('html');
        $this->assertStringContainsString('04 Oktober 2026 M', $abaikan);
    }

    public function test_format_nomor_mengikuti_tanggal_pada_layanan_penomoran(): void
    {
        $k = KlasifikasiSurat::where('kode', 'II.3.AU')->first();
        $this->assertSame('001/II.3.AU/FT-UMB/VII/2026', app(PenomoranService::class)->terbitkan($k, Carbon::parse('2026-07-15')));
    }
}
