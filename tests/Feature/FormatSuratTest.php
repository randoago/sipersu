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
            'klasifikasi_id' => KlasifikasiSurat::where('kode', 'D')->value('id'),
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

    public function test_alur_tidak_diatur_dari_format_surat_tetapi_dari_menu_alur_surat(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        // Format baru memakai alur bawaan, walau formulir mengirim pilihan lain (diabaikan).
        $tu->post('/format-surat', $this->payload(['mode_ttd' => 'basah', 'perlu_paraf' => '1', 'paraf_role' => 'kaprodi']))->assertRedirect('/format-surat');
        $f = JenisSurat::where('nama', 'Surat Izin Cuti Dosen')->firstOrFail();
        $this->assertSame('qr', $f->mode_ttd);
        $this->assertSame([], $f->tahapAlur());

        $f->update(['alur' => [['tahap' => 'paraf', 'peran' => 'kaprodi'], ['tahap' => 'paraf', 'peran' => 'wakil_dekan']]]);
        $tu->put("/format-surat/{$f->id}", $this->payload(['judul_surat' => 'SURAT CUTI', 'paraf_role' => 'wakil_dekan']))->assertSessionHasNoErrors();
        $f->refresh();
        $this->assertSame('SURAT CUTI', $f->judul_surat);
        $this->assertCount(2, $f->tahapAlur());
        $this->assertSame('qr', $f->mode_ttd);
    }

    public function test_format_mahasiswa_baru_mendapat_verifikasi_bawaan(): void
    {
        $this->actingAs($this->u('198701012010011001'))->post('/format-surat', $this->payload(['sasaran' => 'mahasiswa']))->assertRedirect('/format-surat');
        $f = JenisSurat::where('nama', 'Surat Izin Cuti Dosen')->firstOrFail();
        $this->assertSame([['tahap' => 'verifikasi', 'peran' => 'admin_tu']], $f->tahapAlur());
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
    }

    public function test_format_memilih_seruan_dan_pokok_masalah_untuk_nomor_surat(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->get('/format-surat/buat')->assertOk()->assertSee('Seruan (kode jenis surat pada nomor)')->assertSee('Pokok Masalah')->assertSee('Susunan nomor surat');

        $tu->post('/format-surat', $this->payload(['kekhususan' => 'TGS']))->assertRedirect('/format-surat');
        $f = JenisSurat::where('nama', 'Surat Izin Cuti Dosen')->firstOrFail();
        $this->assertSame('TGS', $f->kekhususan);
        $this->assertSame('D', $f->klasifikasi->kode);

        $tu->post('/format-surat', $this->payload(['nama' => 'Surat Biasa Uji', 'kekhususan' => '']))->assertRedirect('/format-surat');
        $this->assertNull(JenisSurat::where('nama', 'Surat Biasa Uji')->value('kekhususan'), 'kosong = surat biasa (tanpa seruan)');

        $tu->post('/format-surat', $this->payload(['nama' => 'Seruan Ngawur', 'kekhususan' => 'XYZ']))->assertSessionHasErrors('kekhususan');
    }

    public function test_seruan_dikelola_di_master_data_dan_menjadi_pilihan_format(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->get('/master/seruan')->assertOk()->assertSee('Seruan (Kode Kekhususan)')->assertSee('Surat Tugas')->assertSee('SRN');

        $tu->post('/master/seruan', ['kode' => 'UND', 'nama' => 'Surat Undangan', 'aktif' => '1'])->assertSessionDoesntHaveErrors();
        $this->assertTrue(\App\Models\Seruan::where('kode', 'UND')->exists());
        $tu->post('/master/seruan', ['kode' => 'und', 'nama' => 'Huruf kecil', 'aktif' => '1'])->assertSessionHasErrors('kode');
        $tu->post('/master/seruan', ['kode' => 'UND', 'nama' => 'Kembar', 'aktif' => '1'])->assertSessionHasErrors('kode');

        $tu->get('/format-surat/buat')->assertSee('UND — Surat Undangan');
        $tu->post('/format-surat', $this->payload(['nama' => 'Undangan Master', 'kekhususan' => 'UND']))->assertRedirect('/format-surat');
        $this->assertSame('UND', JenisSurat::where('nama', 'Undangan Master')->value('kekhususan'));

        // seruan nonaktif tidak lagi dapat dipilih pada format baru
        $id = \App\Models\Seruan::where('kode', 'UND')->value('id');
        $tu->post("/master/seruan/{$id}/aktif");
        $tu->post('/format-surat', $this->payload(['nama' => 'Undangan Lain', 'kekhususan' => 'UND']))->assertSessionHasErrors('kekhususan');
    }

    public function test_pokok_masalah_dan_seruan_dapat_diubah_dan_dihapus_bila_belum_dipakai(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->get('/master/klasifikasi')->assertOk()->assertSee('Hapus');

        // ubah
        $baru = \App\Models\KlasifikasiSurat::create(['kode' => 'ZZ', 'nama' => 'Uji Hapus', 'aktif' => true]);
        $tu->put("/master/klasifikasi/{$baru->id}", ['kode' => 'ZZ', 'nama' => 'Uji Ubah', 'aktif' => '1'])->assertSessionDoesntHaveErrors();
        $this->assertSame('Uji Ubah', $baru->fresh()->nama);

        // hapus yang belum dipakai
        $tu->delete("/master/klasifikasi/{$baru->id}")->assertSessionHas('sukses');
        $this->assertNull(\App\Models\KlasifikasiSurat::find($baru->id));

        // yang sudah dipakai format ditolak
        $dipakai = JenisSurat::first()->klasifikasi_id;
        $tu->delete("/master/klasifikasi/{$dipakai}")->assertSessionHas('galat');
        $this->assertNotNull(\App\Models\KlasifikasiSurat::find($dipakai));

        // seruan: dipakai format → ditolak; baru → boleh
        $kode = JenisSurat::whereNotNull('kekhususan')->value('kekhususan');
        $tu->delete('/master/seruan/'.\App\Models\Seruan::where('kode', $kode)->value('id'))->assertSessionHas('galat');
        $s = \App\Models\Seruan::create(['kode' => 'UJI', 'nama' => 'Uji', 'aktif' => true]);
        $tu->delete("/master/seruan/{$s->id}")->assertSessionHas('sukses');

        // entitas lain tidak dapat dihapus lewat rute ini
        $tu->delete('/master/prodi/'.\App\Models\Prodi::value('id'))->assertStatus(405);
    }

    public function test_posisi_nomor_surat_kiri_atau_tengah_per_format(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->get('/format-surat/buat')->assertSee('Posisi nomor surat');
        $tu->post('/format-surat', $this->payload(['nama' => 'Nomor Kiri', 'posisi_nomor' => 'kiri']))->assertRedirect('/format-surat');
        $tu->post('/format-surat', $this->payload(['nama' => 'Nomor Tengah']))->assertRedirect('/format-surat');
        $tu->post('/format-surat', $this->payload(['nama' => 'Nomor Ngawur', 'posisi_nomor' => 'kanan']))->assertSessionHasErrors('posisi_nomor');
        $kiri = JenisSurat::where('nama', 'Nomor Kiri')->firstOrFail();
        $tengah = JenisSurat::where('nama', 'Nomor Tengah')->firstOrFail();
        $this->assertSame('kiri', $kiri->posisi_nomor);
        $this->assertSame('tengah', $tengah->posisi_nomor, 'bawaan tengah');

        $h = $tu->postJson('/surat-keluar/pratinjau', ['format' => $kiri->kode])->json('html');
        $this->assertStringContainsString('<table class="nomor-atas"', $h);
        $this->assertStringNotContainsString('<div class="nomor">', $h);
        $t = $tu->postJson('/surat-keluar/pratinjau', ['format' => $tengah->kode])->json('html');
        $this->assertStringContainsString('<div class="nomor">Nomor :', $t);
    }

    public function test_pindaian_surat_hanya_tampil_pada_format_yang_mengaktifkannya(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/format-surat', $this->payload(['nama' => 'Format Dengan Pindaian', 'pindaian' => '1']))->assertRedirect('/format-surat');
        $tu->post('/format-surat', $this->payload(['nama' => 'Format Tanpa Pindaian']))->assertRedirect('/format-surat');
        $ada = JenisSurat::where('nama', 'Format Dengan Pindaian')->firstOrFail();
        $tak = JenisSurat::where('nama', 'Format Tanpa Pindaian')->firstOrFail();
        $this->assertTrue($ada->pindaian);
        $this->assertFalse($tak->pindaian);

        $tu->get('/surat-keluar/format/'.$ada->kode)->assertOk()->assertSee('Pindaian Surat')->assertSee('multipart/form-data', false);
        $tu->get('/surat-keluar/format/'.$tak->kode)->assertOk()->assertDontSee('Pindaian Surat');
    }

    public function test_pindaian_diunggah_disimpan_dan_dapat_dibuka_dari_halaman_surat(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/format-surat', $this->payload(['nama' => 'Format Pindai', 'pindaian' => '1']));
        $f = JenisSurat::where('nama', 'Format Pindai')->firstOrFail();
        $isian = ['nama_dosen' => 'Dosen Uji', 'alasan_cuti' => 'Keperluan keluarga', 'jenis_cuti' => 'Cuti Sakit'];

        $tu->post('/surat-keluar/format/'.$f->kode, ['isian' => $isian, 'scan' => \Illuminate\Http\UploadedFile::fake()->create('scan.exe', 10, 'application/x-msdownload')])->assertSessionHasErrors('scan');

        $tu->post('/surat-keluar/format/'.$f->kode, ['isian' => $isian, 'scan' => \Illuminate\Http\UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf')]);
        $s = Surat::where('jenis_surat_id', $f->id)->latest('id')->firstOrFail();
        $l = $s->lampiran()->where('label', 'Pindaian surat')->firstOrFail();
        Storage::disk('local')->assertExists($l->path);
        $tu->get('/surat-keluar/'.$s->id)->assertOk()->assertSee('Buka Pindaian');
        $tu->get('/lampiran/'.$l->id)->assertOk();
    }

    public function test_penanda_nomor_dan_tanggal_memindahkan_posisinya_dari_templat(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/format-surat', $this->payload(['nama' => 'Posisi Bebas', 'template_html' => '<p>{%tanggal%}</p><p>{%nomor%}</p><p>{{ isian.nama_dosen }}</p>{%ttd%}']))->assertRedirect('/format-surat');
        $f = JenisSurat::where('nama', 'Posisi Bebas')->firstOrFail();
        $html = $tu->postJson('/surat-keluar/pratinjau', ['format' => $f->kode, 'isian' => ['nama_dosen' => 'Dosen Uji']])->assertOk()->json('html');

        $this->assertStringNotContainsString('<div class="nomor">', $html, 'nomor tidak lagi di kepala surat');
        $this->assertMatchesRegularExpression('#<td width="14%">Nomor</td><td width="3%">:</td><td>000/#', $html, 'nomor muncul di posisi penanda');
        $this->assertStringContainsString('text-align:right', $html);
        $this->assertStringNotContainsString('Dikeluarkan di', $html, 'tanggal dipindah dari blok tanda tangan');
        $this->assertStringNotContainsString('{%', $html, 'penanda tidak tampil mentah');
        $this->assertLessThan(strpos($html, 'Dosen Uji</p>'), strpos($html, '>Nomor<'), 'urutan mengikuti templat');

        // tanpa penanda: perilaku lama (nomor di kepala, tanggal di blok tanda tangan)
        $lama = $tu->postJson('/surat-keluar/pratinjau', ['format' => 'SURAT-TUGAS'])->json('html');
        $this->assertStringContainsString('<div class="nomor">Nomor :', $lama);
        $this->assertStringContainsString('Dikeluarkan di : Baubau', $lama);
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
        $this->assertSame('D', $s->klasifikasi->kode);
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
