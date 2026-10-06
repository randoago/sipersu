<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use App\Models\Surat;
use App\Models\User;
use App\Services\KunciTte;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FormatSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-fs-'.getmypid()]);
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

    private function payload(array $o = []): array
    {
        return $o + [
            'nama' => 'Surat Izin Cuti Dosen', 'deskripsi' => 'Izin cuti dosen', 'ikon' => 'event', 'sasaran' => 'staf',
            'judul_surat' => 'SURAT IZIN CUTI', 'perihal_template' => 'Izin Cuti {{ isian.nama_dosen }}',
            'klasifikasi_id' => KlasifikasiSurat::where('kode', 'II.6.SK')->value('id'),
            'penandatangan_jabatan_id' => Jabatan::where('kode', 'dekan')->value('id'),
            'mode_ttd' => 'qr', 'sla_hari' => 1, 'urutan' => 5, 'aktif' => '1',
            'fields' => [
                ['label' => 'Nama Dosen', 'tipe' => 'teks', 'wajib' => '1', 'nama' => ''],
                ['label' => 'Alasan Cuti', 'tipe' => 'area', 'wajib' => '1', 'nama' => ''],
                ['label' => 'Jenis Cuti', 'tipe' => 'pilihan', 'wajib' => '1', 'opsi' => "Cuti Tahunan\nCuti Sakit", 'nama' => ''],
            ],
            'template_html' => '<p>Diberikan izin cuti kepada <strong>{{ isian.nama_dosen }}</strong> ({{ isian.jenis_cuti }}) karena {{ isian.alasan_cuti }}. Oleh {{ pembuat.nama }}.</p>',
        ];
    }

    public function test_hanya_tu_dan_super_admin_yang_mengatur_format(): void
    {
        foreach (['/format-surat', '/format-surat/buat'] as $u) {
            $this->actingAs($this->u('198701012010011001'))->get($u)->assertOk();
            $this->actingAs($this->u('0000000001'))->get($u)->assertOk();
            foreach (['21650012', '0912038401', '0912088704'] as $nik) {
                $this->actingAs($this->u($nik))->get($u)->assertForbidden();
            }
        }
        $this->actingAs($this->u('0912088704'))->post('/format-surat', $this->payload())->assertForbidden();
        $this->actingAs($this->u('198701012010011001'))->get('/format-surat/buat')->assertSee('{{ isian.nama_rapat }}', false)->assertDontSee('php echo');
    }

    public function test_tu_membuat_format_baru_dengan_kunci_isian_otomatis(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/format-surat', $this->payload())->assertRedirect('/format-surat');

        $f = JenisSurat::where('nama', 'Surat Izin Cuti Dosen')->firstOrFail();
        $this->assertSame('staf', $f->sasaran);
        $this->assertSame(['nama_dosen', 'alasan_cuti', 'jenis_cuti'], array_column($f->field_formulir, 'nama'));
        $this->assertSame(['Cuti Tahunan', 'Cuti Sakit'], $f->field_formulir[2]['opsi']);
        $this->assertTrue($f->field_formulir[0]['wajib']);
        $this->assertSame('SURAT-IZIN-CUTI-DOSEN', $f->kode);
        $this->assertSame('admin_tu', $f->verifikator_role);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'format_buat']);
    }

    public function test_kunci_isian_kembar_diberi_akhiran_dan_validasi_format(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $p = $this->payload(['fields' => [['label' => 'Nama', 'tipe' => 'teks'], ['label' => 'Nama', 'tipe' => 'teks'], ['label' => '3 Hari', 'tipe' => 'angka']], 'template_html' => '<p>{{ isian.nama }} {{ isian.nama_2 }} {{ isian.isian_3_hari }}</p>']);
        $tu->post('/format-surat', $p)->assertRedirect();
        $this->assertSame(['nama', 'nama_2', 'isian_3_hari'], array_column(JenisSurat::where('nama', 'Surat Izin Cuti Dosen')->first()->field_formulir, 'nama'));

        $tu->post('/format-surat', $this->payload(['fields' => []]))->assertSessionHasErrors('fields');
        $tu->post('/format-surat', $this->payload(['fields' => [['label' => 'Jenis', 'tipe' => 'pilihan', 'opsi' => '']]]))->assertSessionHasErrors('fields');
        $tu->post('/format-surat', $this->payload(['template_html' => '<p>{{ isian.tidak_ada }}</p>']))->assertSessionHasErrors('template_html');
        $tu->post('/format-surat', $this->payload(['nama' => '']))->assertSessionHasErrors('nama');
        $tu->post('/format-surat', $this->payload(['mode_ttd' => 'lain']))->assertSessionHasErrors('mode_ttd');
    }

    public function test_templat_dibersihkan_dari_script_dan_atribut_berbahaya(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/format-surat', $this->payload(['template_html' => '<p onclick="x()">{{ isian.nama_dosen }}</p><script>alert(1)</script><a href="javascript:alert(2)">k</a>']));
        $html = JenisSurat::where('nama', 'Surat Izin Cuti Dosen')->first()->template_html;
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_pratinjau_memakai_data_contoh(): void
    {
        $p = $this->payload();
        $res = $this->actingAs($this->u('198701012010011001'))->postJson('/format-surat/pratinjau', $p)->assertOk()->json('html');
        $this->assertStringContainsString('SURAT IZIN CUTI', $res);
        $this->assertStringContainsString('[Nama Dosen]', $res);
        $this->assertStringContainsString('Cuti Tahunan', $res);
    }

    public function test_salin_dan_nonaktifkan_format(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $f = JenisSurat::where('kode', 'SURAT-TUGAS')->first();
        $tu->post("/format-surat/{$f->id}/salin")->assertRedirect();
        $this->assertDatabaseHas('jenis_surat', ['nama' => 'Surat Tugas (salinan)', 'aktif' => false]);
        $tu->post("/format-surat/{$f->id}/aktif");
        $this->assertFalse($f->fresh()->aktif);
        $tu->get('/surat-keluar/format/SURAT-TUGAS')->assertNotFound();
    }

    public function test_menu_buat_surat_menampilkan_format_staf_saja(): void
    {
        $this->actingAs($this->u('198701012010011001'))->get('/surat-keluar/buat?bentuk=qr')->assertOk()
            ->assertSee('Surat Undangan')->assertSee('Surat Tugas')->assertSee('Surat Bebas')->assertDontSee('Surat Cuti Akademik');
        $this->actingAs($this->u('21650012'))->get('/surat-keluar/buat')->assertForbidden();
    }

    public function test_mengisi_formulir_membuat_draf_dengan_isi_dirender_dan_aman_dari_xss(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->get('/surat-keluar/format/SURAT-TUGAS')->assertOk()->assertSee('Yang ditugaskan')->assertSee('Tambah Baris')->assertSee('Program Studi');

        $this->actingAs($tu)->post('/surat-keluar/format/SURAT-TUGAS', ['isian' => [
            'dasar' => "Berdasarkan ketentuan Tridarma\nPerguruan Tinggi <script>x</script>",
            'ditugaskan' => [['La Sianto, S.T., M.T', 'Teknik Sipil'], ['Rando <b>X</b>', 'Rekayasa Sistem Komputer'], ['', '']],
            'kegiatan' => 'Pengabdian Kepada Masyarakat', 'tema' => 'Tata Guna Air Irigasi', 'mitra' => '', 'waktu' => '21 April 2026 - Selesai',
            'tembusan' => "1. Rektor Universitas Muhammadiyah Buton\n- Yang bersangkutan\nArsip",
        ]])->assertRedirect();

        $s = Surat::firstOrFail();
        $this->assertSame('draf', $s->status);
        $this->assertSame('II.6.SK', $s->klasifikasi->kode);
        $this->assertStringContainsString('Surat Tugas', $s->perihal);
        $h = $s->isi_html;
        $this->assertStringContainsString('<table class="tabel-isi">', $h);
        $this->assertStringContainsString('<th>Program Studi</th>', $h);
        $this->assertStringContainsString('<td>La Sianto, S.T., M.T</td>', $h);
        $this->assertStringContainsString('<td align="center">2.</td>', $h);
        $this->assertStringNotContainsString('<td align="center">3.</td>', $h, 'baris kosong dibuang');
        $this->assertStringContainsString('Rando &lt;b&gt;X&lt;/b&gt;', $h, 'isi sel di-escape');
        $this->assertStringNotContainsString('<script', $h);
        $this->assertStringContainsString('Dekan Fakultas Teknik Universitas Muhammadiyah Buton Menugaskan', $h);
        $this->assertStringContainsString('<ol class="daftar-isi"><li>Rektor Universitas Muhammadiyah Buton</li><li>Yang bersangkutan</li><li>Arsip</li></ol>', $h, 'nomor/penanda awal baris dibuang');
        $this->assertStringContainsString('Tema', $h);
        $this->assertStringNotContainsString('>Mitra<', $h, 'baris bersyarat hilang bila kosong');
        $this->assertStringContainsString('{%ttd%}', $h, 'penanda posisi tanda tangan tersimpan');
        $this->actingAs($tu)->get("/surat-keluar/{$s->id}")->assertOk()->assertSee('SURAT TUGAS')->assertSee('Tembusan:');
        $this->assertEquals([['La Sianto, S.T., M.T', 'Teknik Sipil'], ['Rando <b>X</b>', 'Rekayasa Sistem Komputer'], ['', '']], $s->data['isian_mentah']['ditugaskan']);
    }

    public function test_validasi_isian_wajib_tabel_dan_daftar(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/surat-keluar/format/SURAT-TUGAS', ['isian' => ['dasar' => '']])->assertSessionHasErrors(['isian.dasar', 'isian.ditugaskan', 'isian.kegiatan', 'isian.waktu']);
        $tu->post('/surat-keluar/format/SURAT-TUGAS', ['isian' => ['dasar' => 'x', 'kegiatan' => 'y', 'waktu' => 'z', 'ditugaskan' => [['', '']]]])->assertSessionHasErrors('isian.ditugaskan');
        $tu->post('/surat-keluar/format/SURAT-TUGAS', ['isian' => ['dasar' => 'x', 'kegiatan' => 'y', 'waktu' => 'z', 'ditugaskan' => [['A', 'B']], 'tembusan' => str_repeat('a', 1100)]])->assertSessionHasErrors('isian.tembusan');
        $this->assertSame(0, Surat::count());
    }

    public function test_surat_dari_format_mengikuti_alur_ttd_nomor_dan_bentuk_qr(): void
    {
        $tu = $this->u('198701012010011001');
        $dekan = $this->u('0912038401');
        $isian = ['kepada' => "Ketua Prodi\ndi Tempat", 'perihal' => 'Undangan Rapat Mutu', 'sehubungan' => 'persiapan akreditasi', 'sebagai' => 'menghadiri rapat', 'hari_tanggal' => '2026-10-20', 'waktu' => '09.00', 'tempat' => 'Aula'];

        $this->actingAs($tu)->post('/surat-keluar/format/UNDANGAN-RAPAT', ['isian' => $isian])->assertRedirect();
        $this->actingAs($tu)->post('/surat-keluar/format/SURAT-PEMBERITAHUAN', ['isian' => ['kepada' => 'Semua Dosen', 'hal' => 'Libur', 'isi' => 'Kampus libur.']])->assertRedirect();

        $undangan = Surat::where('perihal', 'Undangan Rapat Mutu')->firstOrFail();
        $biasa = Surat::where('perihal', 'Libur')->firstOrFail();
        $this->assertSame('qr', $undangan->mode_ttd);
        $this->assertSame('basah', $biasa->mode_ttd);

        foreach ([$undangan, $biasa] as $s) {
            $this->actingAs($tu)->post("/surat-keluar/{$s->id}/ajukan");
            if ($s->fresh()->status === 'menunggu_ttd') {          // format tanpa QR (Surat Pemberitahuan) tidak melalui persetujuan: sudah terbit langsung
                $this->actingAs($dekan)->post("/surat-keluar/{$s->id}/tandatangani", ['password' => 'password'])->assertRedirect();
            }
        }
        $this->assertTrue($biasa->fresh()->langsungTerbit());
        $this->assertSame('ditandatangani', $biasa->fresh()->status);
        $this->assertStringStartsWith('001/II.3.AU', $undangan->fresh()->nomor);
        $this->assertStringStartsWith('002/II.3.AU', $biasa->fresh()->nomor);
        $this->assertNotNull($undangan->fresh()->qr_token);
        $this->assertNull($biasa->fresh()->qr_token, 'format tanpa QR tidak punya token');
    }

    public function test_ubah_draf_dari_format_memperbarui_isi(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/surat-keluar/format/SURAT-PEMBERITAHUAN', ['isian' => ['kepada' => 'A', 'hal' => 'Lama', 'isi' => 'isi lama']]);
        $s = Surat::firstOrFail();

        $this->actingAs($tu)->get("/surat-keluar/{$s->id}/ubah")->assertOk()->assertSee('Lama');
        $this->actingAs($tu)->put("/surat-keluar/{$s->id}", ['isian' => ['kepada' => 'A', 'hal' => 'Baru', 'isi' => 'isi baru']])->assertRedirect();
        $this->assertSame('Baru', $s->fresh()->perihal);
        $this->assertStringContainsString('isi baru', $s->fresh()->isi_html);
    }

    public function test_format_staf_tidak_muncul_di_katalog_mahasiswa_dan_tidak_bisa_diajukan(): void
    {
        $mhs = $this->u('21650012');
        $this->actingAs($mhs)->get('/layanan')->assertOk()->assertSee('Surat Izin Penelitian')->assertDontSee('Surat Undangan Rapat')->assertDontSee('Surat Tugas');
        $this->actingAs($mhs)->get('/dasbor')->assertOk()->assertDontSee('Surat Undangan Rapat');
        $this->actingAs($mhs)->get('/layanan/SURAT-TUGAS/ajukan')->assertNotFound();
    }

    public function test_format_mahasiswa_buatan_tu_langsung_tampil_di_katalog_dan_bisa_diajukan(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/format-surat', $this->payload([
            'nama' => 'Surat Keterangan Bebas Pustaka', 'sasaran' => 'mahasiswa', 'judul_surat' => 'SURAT KETERANGAN BEBAS PUSTAKA', 'verifikator_role' => 'admin_tu',
            'fields' => [['label' => 'Keperluan', 'tipe' => 'teks', 'wajib' => '1']], 'perihal_template' => '',
            'template_html' => '<p>Mahasiswa {{ pemohon.nama }} bebas pustaka untuk {{ isian.keperluan }}.</p>',
            'syarat' => [['label' => 'KTM', 'wajib' => '1']],
        ]))->assertRedirect();

        $mhs = $this->u('21650012');
        $this->actingAs($mhs)->get('/layanan')->assertSee('Surat Keterangan Bebas Pustaka');
        $jenis = JenisSurat::where('nama', 'Surat Keterangan Bebas Pustaka')->first();
        $this->assertSame([['label' => 'KTM', 'wajib' => true]], $jenis->syarat);

        Livewire::actingAs($mhs)->test(\App\Livewire\AjukanSurat::class, ['jenis' => $jenis])
            ->set('isian.keperluan', 'wisuda')->call('lanjut')
            ->set('berkas.0', \Illuminate\Http\UploadedFile::fake()->create('ktm.pdf', 100, 'application/pdf'))
            ->call('lanjut')->set('setuju', true)->call('kirim')->assertHasNoErrors();
        $this->assertSame(1, \App\Models\Pengajuan::count());
    }
}
