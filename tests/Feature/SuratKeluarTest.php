<?php

namespace Tests\Feature;

use App\Models\KlasifikasiSurat;
use App\Models\Jabatan;
use App\Models\Surat;
use App\Models\User;
use App\Services\KunciTte;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratKeluarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-sk-'.getmypid()]);
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

    private function data(array $o = []): array
    {
        return $o + [
            'klasifikasi_id' => KlasifikasiSurat::where('kode', 'II.3.AU')->value('id'), 'sifat' => 'biasa',
            'tujuan' => "Kepala Dinas Pendidikan\ndi Tempat", 'perihal' => 'Undangan Rapat Koordinasi', 'lampiran' => '-',
            'isi' => "Dengan hormat, kami mengundang Bapak/Ibu.\n\nDemikian disampaikan.", 'salam' => '1',
            'jabatan_id' => Jabatan::where('kode', 'dekan')->value('id'), 'paraf_role' => '', 'mode_ttd' => 'qr',
        ];
    }

    public function test_alur_draf_ajukan_ttd_nomor_terbit_dan_unduh_pdf(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/surat-keluar', $this->data())->assertRedirect();
        $s = Surat::firstOrFail();
        $this->assertSame('draf', $s->status);
        $this->assertNull($s->nomor);

        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan")->assertRedirect();
        $this->assertSame('menunggu_ttd', $s->fresh()->status);
        $this->assertNull($s->fresh()->nomor, 'nomor belum terbit sebelum TTD');

        $dekan = $this->u('0912038401');
        $this->actingAs($dekan)->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'salah'])->assertSessionHasErrors('password');
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password'])->assertForbidden();
        $this->actingAs($dekan)->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password'])->assertRedirect();

        $s->refresh();
        $this->assertSame('ditandatangani', $s->status);
        $this->assertMatchesRegularExpression('#^001/II\.3\.AU/FT-UMB/[IVX]+/\d{4}$#', $s->nomor);
        $this->actingAs($tu)->get("/surat/{$s->id}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get('/v/'.$s->qr_token)->assertOk()->assertSee('Dokumen Asli');
    }

    public function test_paraf_wajib_sebelum_ttd_bila_dipilih(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/surat-keluar', $this->data(['paraf_role' => 'wakil_dekan']));
        $s = Surat::firstOrFail();
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
        $this->assertSame('menunggu_paraf', $s->fresh()->status);

        $this->actingAs($this->u('0912038401'))->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password'])->assertForbidden();
        $this->actingAs($this->u('0912048102'))->post("/surat-keluar/{$s->id}/paraf")->assertRedirect();
        $this->assertSame('menunggu_ttd', $s->fresh()->status);
    }

    public function test_nomor_berurutan_dan_surat_batal_tidak_memakai_ulang_nomor(): void
    {
        $tu = $this->u('198701012010011001');
        $dekan = $this->u('0912038401');
        $nomor = [];
        foreach (['A', 'B'] as $p) {
            $this->actingAs($tu)->post('/surat-keluar', $this->data(['perihal' => "Surat $p"]));
            $s = Surat::where('perihal', "Surat $p")->first();
            $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
            $this->actingAs($dekan)->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password']);
            $nomor[$p] = $s->fresh()->nomor;
        }
        $this->assertStringStartsWith('001/', $nomor['A']);
        $this->assertStringStartsWith('002/', $nomor['B']);

        $a = Surat::where('perihal', 'Surat A')->first();
        $this->actingAs($tu)->post("/surat-keluar/{$a->id}/batalkan", ['alasan' => 'Salah tujuan'])->assertRedirect();
        $this->assertSame('batal', $a->fresh()->status);
        $this->assertSame($nomor['A'], $a->fresh()->nomor, 'nomor tetap tercatat');
        $this->get('/v/'.$a->fresh()->qr_token)->assertSee('TIDAK BERLAKU', false)->assertDontSee('Dokumen Asli');

        $this->actingAs($tu)->post('/surat-keluar', $this->data(['perihal' => 'Surat C']));
        $c = Surat::where('perihal', 'Surat C')->first();
        $this->actingAs($tu)->post("/surat-keluar/{$c->id}/ajukan");
        $this->actingAs($dekan)->post("/surat-keluar/{$c->id}/tandatangani", ['password' => 'password']);
        $this->assertStringStartsWith('003/', $c->fresh()->nomor, 'nomor 001 yang dibatalkan tidak dipakai ulang');
    }

    public function test_mahasiswa_tidak_boleh_dan_draf_orang_lain_tidak_terlihat(): void
    {
        $this->actingAs($this->u('21650012'))->get('/surat-keluar')->assertForbidden();
        $this->actingAs($this->u('21650012'))->post('/surat-keluar', $this->data())->assertForbidden();

        $dosen = $this->u('0912088704');
        $this->actingAs($this->u('198701012010011001'))->post('/surat-keluar', $this->data());
        $s = Surat::firstOrFail();
        $this->actingAs($dosen)->get("/surat-keluar/{$s->id}")->assertForbidden();
    }

    public function test_draf_yang_sudah_diajukan_tidak_bisa_diubah_dan_validasi_wajib(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/surat-keluar', ['perihal' => ''])->assertSessionHasErrors(['tujuan', 'perihal', 'isi', 'jabatan_id', 'klasifikasi_id']);
        $this->actingAs($tu)->post('/surat-keluar', $this->data());
        $s = Surat::firstOrFail();
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
        $this->actingAs($tu)->get("/surat-keluar/{$s->id}/ubah")->assertForbidden();
    }

    public function test_dasbor_dekan_memuat_surat_yang_menunggu_ttd(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/surat-keluar', $this->data(['perihal' => 'Undangan Penting XYZ']));
        $s = Surat::firstOrFail();
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
        $this->actingAs($this->u('0912038401'))->get('/dasbor')->assertOk()->assertSee('Undangan Penting XYZ');
    }

    public function test_surat_tanpa_qr_terbit_nomor_tetapi_tanpa_token_signature_dan_verifikasi(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/surat-keluar', $this->data(['mode_ttd' => 'basah', 'perihal' => 'Surat Tanpa QR']));
        $s = Surat::firstOrFail();
        $this->assertSame('basah', $s->mode_ttd);
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
        $this->actingAs($this->u('0912038401'))->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password'])->assertRedirect();

        $s->refresh();
        $this->assertSame('ditandatangani', $s->status);
        $this->assertMatchesRegularExpression('#^001/II\.3\.AU/FT-UMB/[IVX]+/\d{4}$#', $s->nomor, 'nomor tetap otomatis');
        $this->assertNull($s->qr_token);
        $this->assertNull($s->signature);
        Storage::disk('local')->assertExists($s->file_pdf);
        $this->actingAs($tu)->get("/surat/{$s->id}/pdf")->assertOk();
        $this->actingAs($tu)->get("/surat-keluar/{$s->id}")->assertOk()->assertSee('Tanpa QR');
        $this->assertSame(1, \App\Models\Penomoran::first()->nomor_terakhir);
    }

    public function test_nomor_berurutan_lintas_surat_qr_dan_tanpa_qr(): void
    {
        $tu = $this->u('198701012010011001');
        $dekan = $this->u('0912038401');
        foreach (['qr', 'basah', 'qr'] as $i => $mode) {
            $this->actingAs($tu)->post('/surat-keluar', $this->data(['mode_ttd' => $mode, 'perihal' => "Surat $i"]));
            $s = Surat::where('perihal', "Surat $i")->first();
            $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
            $this->actingAs($dekan)->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password']);
            $this->assertStringStartsWith(sprintf('%03d/', $i + 1), $s->fresh()->nomor);
        }
    }

    public function test_pengajuan_mengikuti_mode_jenis_surat_dan_pdf_tanpa_qr(): void
    {
        $jenis = \App\Models\JenisSurat::where('kode', 'KET-AKTIF')->first();
        $jenis->update(['mode_ttd' => 'basah']);
        $alur = app(\App\Services\AlurPengajuan::class);
        $p = $alur->ajukan($this->u('21650012'), $jenis, ['keperluan' => 'BPJS', 'semester' => '7', 'tahun_akademik' => 'x', 'keterangan' => '']);
        $p = $alur->verifikasi($p, $this->u('198701012010011001'));
        $this->assertSame('basah', $p->surat->mode_ttd);
        $p = $alur->tandatangani($p, $this->u('0912038401'));

        $this->assertNull($p->surat->qr_token);
        $this->actingAs($this->u('21650012'))->get("/pengajuan/{$p->id}")->assertOk()->assertDontSee('Verifikasi QR')->assertSee('tanda tangan basah');
    }
}
