<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Models\Surat;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PratinjauSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function u(string $nik): User
    {
        return User::where('nomor_induk', $nik)->firstOrFail();
    }

    public function test_pratinjau_surat_dari_format_menampilkan_isian_dan_penanda_untuk_yang_kosong(): void
    {
        $r = $this->actingAs($this->u('198701012010011001'))->postJson('/surat-keluar/pratinjau', [
            'format' => 'SURAT-TUGAS', 'isian' => ['kegiatan' => 'Pengabdian Uji', 'ditugaskan' => [['Dosen Uji', 'Teknik Sipil']]],
        ])->assertOk();
        $html = $r->json('html');

        $this->assertStringContainsString('header-undangan.png', $html, 'header hijau');
        $this->assertStringContainsString('SURAT TUGAS', $html);
        $this->assertStringContainsString('Dosen Uji', $html);
        $this->assertStringContainsString('<table class="tabel-isi">', $html);
        $this->assertStringContainsString('[Dasar / pertimbangan penugasan]', $html, 'isian kosong tampil sebagai penanda');
        $this->assertStringContainsString('[Waktu]', $html);
        $this->assertStringContainsString('QR tanda tangan elektronik', $html);
        $this->assertStringContainsString('Agusman', $html, 'nama penandatangan');
        $this->assertSame(0, Surat::count(), 'pratinjau tidak menyimpan apa pun');
        $this->assertSame(0, \DB::table('nomor_agenda')->count() + \App\Models\Penomoran::count());
    }

    public function test_pratinjau_format_tanpa_qr_menampilkan_ruang_tanda_tangan_basah(): void
    {
        $html = $this->actingAs($this->u('198701012010011001'))->postJson('/surat-keluar/pratinjau', ['format' => 'SURAT-PEMBERITAHUAN', 'isian' => ['hal' => 'Libur']])->assertOk()->json('html');
        $this->assertStringContainsString('Ruang tanda tangan basah', $html);
        $this->assertStringNotContainsString('QR tanda tangan elektronik', $html);
    }

    public function test_pratinjau_surat_bebas_dan_xss_di_isian_di_escape(): void
    {
        $html = $this->actingAs($this->u('198701012010011001'))->postJson('/surat-keluar/pratinjau', [
            'perihal' => 'Uji <script>x</script>', 'tujuan' => 'Kepada Yth', 'isi' => "Paragraf satu\n\nParagraf dua", 'salam' => '1',
            'jabatan_id' => Jabatan::where('kode', 'dekan')->value('id'), 'mode_ttd' => 'qr',
        ])->assertOk()->json('html');
        $this->assertStringContainsString('Paragraf dua', $html);
        $this->assertStringNotContainsString('<script>x', $html);
        $this->assertStringContainsString('Assalamu', $html);
    }

    public function test_pratinjau_dibatasi_untuk_pembuat_surat_dan_format_staf(): void
    {
        $this->actingAs($this->u('21650012'))->postJson('/surat-keluar/pratinjau', ['format' => 'SURAT-TUGAS'])->assertForbidden();
        $this->actingAs($this->u('198701012010011001'))->postJson('/surat-keluar/pratinjau', ['format' => 'KET-AKTIF'])->assertNotFound();   // format mahasiswa
    }

    public function test_pratinjau_pada_editor_format_memakai_tampilan_surat_penuh(): void
    {
        $html = $this->actingAs($this->u('198701012010011001'))->postJson('/format-surat/pratinjau', [
            'judul_surat' => 'Surat Contoh', 'mode_ttd' => 'basah', 'penandatangan_jabatan_id' => Jabatan::where('kode', 'dekan')->value('id'),
            'template_html' => '<p>Halo {{ isian.nama_dosen }} dari {{ pembuat.nama }}</p>', 'fields' => [['label' => 'Nama Dosen', 'tipe' => 'teks']],
        ])->assertOk()->json('html');

        $this->assertStringContainsString('header-undangan.png', $html);
        $this->assertStringContainsString('SURAT CONTOH', $html);
        $this->assertStringContainsString('[Nama Dosen]', $html);
        $this->assertStringContainsString('Ruang tanda tangan basah', $html);
    }

    public function test_pratinjau_pengajuan_mahasiswa_lewat_livewire_mengirim_event(): void
    {
        $jenis = JenisSurat::where('kode', 'IZIN-PENELITIAN')->first();
        $t = Livewire::actingAs($this->u('21650012'))->test(\App\Livewire\AjukanSurat::class, ['jenis' => $jenis])
            ->set('isian.judul', 'Judul Uji Pratinjau')->call('pratinjau');

        $t->assertDispatched('tampil-pratinjau');
        $html = $t->effects['dispatches'][0]['params']['html'] ?? '';
        $this->assertStringContainsString('Judul Uji Pratinjau', $html);
        $this->assertStringContainsString('MUHAMMAD FAUZAN', $html);
        $this->assertStringContainsString('[Ditujukan Kepada', $html);
        $this->assertSame(0, Pengajuan::count());
    }

    public function test_halaman_formulir_memuat_tombol_lihat_tampilan_surat(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->get('/surat-keluar/format/SURAT-TUGAS')->assertOk()->assertSee('Lihat Tampilan Surat');
        $tu->get('/surat-keluar/buat/bebas')->assertOk()->assertSee('Lihat Tampilan Surat');
        $tu->get('/format-surat/buat')->assertOk()->assertSee('Lihat Tampilan Surat');
        $this->actingAs($this->u('21650012'))->get('/layanan/IZIN-PENELITIAN/ajukan')->assertOk()->assertSee('Lihat Tampilan Surat');
    }

    public function test_tanggal_hijriah_dan_masehi_dua_baris_sejajar_di_kolom_yang_sama(): void
    {
        $html = $this->actingAs($this->u('198701012010011001'))->postJson('/surat-keluar/pratinjau', ['format' => 'UNDANGAN-RAPAT'])->assertOk()->json('html');
        $this->assertMatchesRegularExpression('#class="tgl-kota">Baubau,</td><td>\d{1,2} \S+ \d{4} H<br>\s*\d{2} \S+ \d{4} M</td>#u', $html, 'Hijriah (baris 1) dan Masehi (baris 2) dalam kolom yang sama');

        $biasa = $this->actingAs($this->u('198701012010011001'))->postJson('/surat-keluar/pratinjau', ['format' => 'SURAT-TUGAS'])->json('html');
        $this->assertStringContainsString('Dikeluarkan di : Baubau', $biasa, 'format lain tetap memakai Dikeluarkan di');
    }
}
