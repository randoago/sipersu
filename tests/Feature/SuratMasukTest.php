<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\Surat;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratMasukTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
    }

    private function u(string $nik): User
    {
        return User::where('nomor_induk', $nik)->firstOrFail();
    }

    private function data(array $o = []): array
    {
        return $o + [
            'nomor_asal' => '0451/LL9/TU/2026', 'asal' => 'LLDIKTI Wilayah IX', 'tgl_surat' => now()->subDays(3)->toDateString(), 'tgl_diterima' => now()->subDay()->toDateString(),
            'perihal' => 'Undangan Rapat Koordinasi', 'sifat' => 'penting', 'lampiran' => '1 berkas',
        ];
    }

    private function catat(array $o = [], string $format = 'SM-UMUM', ?UploadedFile $scan = null)
    {
        $d = $this->data($o);
        if ($scan) {
            $d['scan'] = $scan;
        }

        return $this->actingAs($this->u('198701012010011001'))->post("/surat-masuk/catat/$format", $d);
    }

    public function test_tu_mencatat_surat_masuk_dengan_nomor_agenda_otomatis_dan_pindaian(): void
    {
        $this->catat([], 'SM-UMUM', UploadedFile::fake()->create('surat.pdf', 400, 'application/pdf'))->assertRedirect();

        $s = Surat::where('arah', 'masuk')->firstOrFail();
        $this->assertMatchesRegularExpression('#^AGD-\d{4}/[IVX]+/0001$#', $s->no_agenda);
        $this->assertNull($s->nomor, 'nomor surat asal disimpan terpisah, bukan di kolom nomor surat keluar');
        $this->assertSame('0451/LL9/TU/2026', $s->data['nomor_asal']);
        $this->assertSame('tercatat', $s->status);
        $this->assertSame(1, $s->lampiran()->count());
        Storage::disk('local')->assertExists($s->lampiran->first()->path);
        $this->assertStringStartsWith('lampiran/surat-masuk/', $s->lampiran->first()->path);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'surat_masuk']);
    }

    public function test_nomor_agenda_berurutan_dan_reset_tiap_tahun(): void
    {
        $this->catat(['nomor_asal' => 'A-1', 'tgl_surat' => '2026-03-01', 'tgl_diterima' => '2026-03-02']);
        $this->catat(['nomor_asal' => 'A-2', 'tgl_surat' => '2026-03-01', 'tgl_diterima' => '2026-03-02']);
        $this->assertSame(['AGD-2026/III/0001', 'AGD-2026/III/0002'], Surat::where('arah', 'masuk')->orderBy('id')->pluck('no_agenda')->all());

        $this->travelTo(now()->setDate(2027, 1, 5));
        $this->catat(['nomor_asal' => 'B-1', 'tgl_surat' => '2027-01-03', 'tgl_diterima' => '2027-01-04']);
        $this->assertSame('AGD-2027/I/0001', Surat::latest('id')->value('no_agenda'));
    }

    public function test_format_nomor_agenda_dapat_diubah_di_pengaturan(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/pengaturan/nomor', [
            'format_nomor' => '{urut}/{klasifikasi}/FT-UMB/{bulan_romawi}/{tahun}', 'panjang_urut' => 3, 'format_agenda' => 'SM/{tahun}/{urut}', 'panjang_agenda' => 5,
            'kota_surat' => 'Baubau', 'alamat_fakultas' => 'Jl. X', 'email_fakultas' => 'a@b.id', 'web_fakultas' => 'ft.id',
        ])->assertSessionHasNoErrors();
        $this->catat();
        $this->assertSame('SM/'.now()->year.'/00001', Surat::where('arah', 'masuk')->value('no_agenda'));

        $this->actingAs($this->u('198701012010011001'))->post('/pengaturan/nomor', ['format_agenda' => 'tanpa-urut'] + [
            'format_nomor' => '{urut}/{klasifikasi}', 'panjang_urut' => 3, 'panjang_agenda' => 4, 'kota_surat' => 'x', 'alamat_fakultas' => 'x', 'email_fakultas' => 'a@b.id', 'web_fakultas' => 'x',
        ])->assertSessionHasErrors('format_agenda');
    }

    public function test_surat_yang_sama_tidak_boleh_dicatat_dua_kali(): void
    {
        $this->catat();
        $this->catat(['nomor_asal' => '0451/ll9/tu/2026'])->assertSessionHasErrors('nomor_asal');   // huruf besar/kecil diabaikan
        $this->assertSame(1, Surat::where('arah', 'masuk')->count());
        $this->catat(['asal' => 'Pengirim Lain'])->assertSessionHasNoErrors();                       // pengirim berbeda boleh memakai nomor sama
        $this->assertSame(2, Surat::where('arah', 'masuk')->count());
    }

    public function test_validasi_tanggal_dan_kolom_wajib(): void
    {
        $this->catat(['tgl_surat' => now()->addDay()->toDateString()])->assertSessionHasErrors('tgl_surat');
        $this->catat(['tgl_surat' => now()->subDay()->toDateString(), 'tgl_diterima' => now()->subDays(3)->toDateString()])->assertSessionHasErrors('tgl_diterima');
        $this->catat(['nomor_asal' => '', 'asal' => '', 'perihal' => '', 'sifat' => 'aneh'])->assertSessionHasErrors(['nomor_asal', 'asal', 'perihal', 'sifat']);
        $this->assertSame(0, Surat::where('arah', 'masuk')->count());
    }

    public function test_pindaian_hanya_pdf_jpg_png_maks_2mb(): void
    {
        $this->catat([], 'SM-UMUM', UploadedFile::fake()->create('besar.pdf', 3000, 'application/pdf'))->assertSessionHasErrors('scan');
        $this->catat([], 'SM-UMUM', UploadedFile::fake()->create('virus.exe', 10))->assertSessionHasErrors('scan');
        $this->catat([], 'SM-UMUM', UploadedFile::fake()->create('dok.docx', 10))->assertSessionHasErrors('scan');
        $this->assertSame(0, Surat::where('arah', 'masuk')->count());
        $this->catat(['nomor_asal' => 'OK-1'], 'SM-UMUM', UploadedFile::fake()->image('foto.jpg'))->assertSessionHasNoErrors();
    }

    public function test_kolom_khusus_dari_format_divalidasi_dan_disimpan(): void
    {
        $this->catat([], 'SM-UNDANGAN')->assertSessionHasErrors('isian.tanggal_kegiatan');
        $this->catat([], 'SM-UNDANGAN')->assertSessionHasErrors();
        $this->actingAs($this->u('198701012010011001'))->post('/surat-masuk/catat/SM-UNDANGAN', $this->data() + ['isian' => ['tanggal_kegiatan' => '2026-11-10', 'waktu' => '09.00', 'tempat' => 'Aula']])->assertRedirect();

        $s = Surat::where('arah', 'masuk')->firstOrFail();
        $this->assertSame('Aula', $s->data['isian']['tempat']);
        $this->assertSame('10 November 2026', $s->data['isian']['tanggal_kegiatan']);
        $this->actingAs($this->u('198701012010011001'))->get("/surat-masuk/{$s->id}")->assertOk()->assertSee('Aula')->assertSee($s->no_agenda);
    }

    public function test_ubah_tidak_mengubah_nomor_agenda_dan_mengganti_pindaian(): void
    {
        $this->catat([], 'SM-UMUM', UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'));
        $s = Surat::where('arah', 'masuk')->firstOrFail();
        $agenda = $s->no_agenda;
        $lama = $s->lampiran->first()->path;

        $this->actingAs($this->u('198701012010011001'))->get("/surat-masuk/{$s->id}/ubah")->assertOk();
        $this->actingAs($this->u('198701012010011001'))->put("/surat-masuk/{$s->id}", $this->data(['perihal' => 'Perihal Baru']) + ['scan' => UploadedFile::fake()->create('b.pdf', 100, 'application/pdf')])->assertRedirect();

        $s->refresh();
        $this->assertSame($agenda, $s->no_agenda);
        $this->assertSame('Perihal Baru', $s->perihal);
        $this->assertSame(1, $s->lampiran()->count());
        Storage::disk('local')->assertMissing($lama);
        Storage::disk('local')->assertExists($s->lampiran()->first()->path);
    }

    public function test_hak_akses_surat_masuk(): void
    {
        $this->catat([], 'SM-UMUM', UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'));
        $this->catat(['nomor_asal' => 'R-1', 'sifat' => 'rahasia', 'perihal' => 'Dokumen Rahasia XYZ'], 'SM-UMUM', UploadedFile::fake()->create('r.pdf', 100, 'application/pdf'));
        [$biasa, $rahasia] = [Surat::where('sifat', 'penting')->first(), Surat::where('sifat', 'rahasia')->first()];

        // hanya TU yang mencatat/mengubah
        foreach (['0912038401', '0912078603', '0912088704', '21650012'] as $nik) {
            $this->actingAs($this->u($nik))->post('/surat-masuk/catat/SM-UMUM', $this->data(['nomor_asal' => "X-$nik"]))->assertForbidden();
        }
        $this->actingAs($this->u('0912038401'))->get("/surat-masuk/{$biasa->id}/ubah")->assertForbidden();

        // melihat: TU, Dekan, Wakil Dekan, Kaprodi (bukan rahasia); dosen & mahasiswa tidak
        $this->actingAs($this->u('0912038401'))->get('/surat-masuk')->assertOk()->assertSee('Dokumen Rahasia XYZ');
        $this->actingAs($this->u('0912078603'))->get('/surat-masuk')->assertOk()->assertDontSee('Dokumen Rahasia XYZ')->assertSee('Undangan Rapat Koordinasi');
        $this->actingAs($this->u('0912078603'))->get("/surat-masuk/{$rahasia->id}")->assertForbidden();
        $this->actingAs($this->u('0912078603'))->get("/lampiran/{$rahasia->lampiran->first()->id}")->assertForbidden();
        $this->actingAs($this->u('0912078603'))->get("/lampiran/{$biasa->lampiran->first()->id}")->assertOk();
        foreach (['0912088704', '21650012'] as $nik) {
            $this->actingAs($this->u($nik))->get('/surat-masuk')->assertForbidden();
            $this->actingAs($this->u($nik))->get("/lampiran/{$biasa->lampiran->first()->id}")->assertForbidden();
        }
    }

    public function test_pencarian_dan_penyaringan_daftar(): void
    {
        $this->catat(['nomor_asal' => 'N-1', 'perihal' => 'Surat Alfa', 'asal' => 'Instansi Satu']);
        $this->catat(['nomor_asal' => 'N-2', 'perihal' => 'Surat Beta', 'asal' => 'Instansi Dua', 'sifat' => 'segera']);
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->get('/surat-masuk?q=Alfa')->assertSee('Surat Alfa')->assertDontSee('Surat Beta');
        $tu->get('/surat-masuk?q=N-2')->assertSee('Surat Beta')->assertDontSee('Surat Alfa');
        $tu->get('/surat-masuk?sifat=segera')->assertSee('Surat Beta')->assertDontSee('Surat Alfa');
    }

    public function test_format_surat_masuk_dibuat_tu_tanpa_templat_dan_muncul_di_menu_catat(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/format-surat', [
            'nama' => 'Surat Pemberitahuan Hibah', 'sasaran' => 'masuk', 'ikon' => 'mail', 'deskripsi' => 'Hibah dari kementerian',
            'fields' => [['label' => 'Nama Program', 'tipe' => 'teks', 'wajib' => '1'], ['label' => 'Nilai Hibah (Rp)', 'tipe' => 'angka']], 'aktif' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect('/format-surat');

        $f = JenisSurat::where('nama', 'Surat Pemberitahuan Hibah')->firstOrFail();
        $this->assertSame('masuk', $f->sasaran);
        $this->assertSame(['nama_program', 'nilai_hibah_rp'], array_column($f->field_formulir, 'nama'));
        $this->assertSame('', $f->template_html);
        $this->assertNull($f->penandatangan_jabatan_id);

        $tu->get('/surat-masuk/catat')->assertSee('Surat Pemberitahuan Hibah')->assertSee('Surat Masuk Umum');
        $tu->get('/surat-keluar/buat?bentuk=qr')->assertDontSee('Surat Pemberitahuan Hibah');             // format masuk tidak muncul di surat keluar
        $this->actingAs($this->u('21650012'))->get('/layanan')->assertDontSee('Surat Pemberitahuan Hibah');
        $this->actingAs($this->u('198701012010011001'))->get('/surat-keluar/format/'.$f->kode)->assertNotFound();
        $this->actingAs($this->u('198701012010011001'))->get('/format-surat?sasaran=masuk')->assertSee('Surat Pemberitahuan Hibah')->assertDontSee('Surat Tugas');
    }

    public function test_format_masuk_tanpa_kolom_khusus_diizinkan_tetapi_keluar_wajib_ada_isian(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/format-surat', ['nama' => 'Masuk Polos', 'sasaran' => 'masuk', 'ikon' => 'mail'])->assertSessionHasNoErrors();
        $tu->post('/format-surat', ['nama' => 'Keluar Kosong', 'sasaran' => 'staf', 'ikon' => 'mail'])->assertSessionHasErrors();
    }

    public function test_dasbor_staf_menampilkan_kartu_surat_masuk(): void
    {
        $this->catat();
        $this->actingAs($this->u('198701012010011001'))->get('/dasbor')->assertOk()->assertSee('Surat Masuk Bulan Ini')->assertSee('Agenda terakhir');
    }
}
