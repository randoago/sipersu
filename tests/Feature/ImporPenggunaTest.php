<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ImporPengguna;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImporPenggunaTest extends TestCase
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

    private function csv(string $isi, string $nama = 'pengguna.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nama, $isi);
    }

    private const HEADER = "nomor_induk,nama,peran,prodi,email,no_hp,angkatan,tempat_lahir,tanggal_lahir,alamat,gelar_depan,gelar_belakang,password,aktif\n";

    private function periksa(string $isi, array $o = [], string $nik = '198701012010011001')
    {
        return $this->actingAs($this->u($nik))->post('/master/pengguna/impor/periksa', ['berkas' => $this->csv($isi)] + $o);
    }

    private function proses(string $nik = '198701012010011001')
    {
        return $this->actingAs($this->u($nik))->post('/master/pengguna/impor/proses');
    }

    public function test_templat_dapat_diunduh_dan_dibaca_kembali_tanpa_galat(): void
    {
        $r = $this->actingAs($this->u('198701012010011001'))->get('/master/pengguna/impor/templat')->assertOk();
        $this->assertStringContainsString('text/csv', $r->headers->get('content-type'));
        $this->assertStringContainsString('templat-impor-pengguna.csv', $r->headers->get('content-disposition'));
        $isi = $r->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFnomor_induk,nama,peran", $isi);

        $hasil = app(ImporPengguna::class)->periksa($isi, $this->u('198701012010011001'), false);
        $this->assertSame(['baru' => 3, 'perbarui' => 0, 'lewati' => 0, 'galat' => 0], array_intersect_key($hasil['ringkas'], array_flip(['baru', 'perbarui', 'lewati', 'galat'])), 'templat sendiri harus valid');
    }

    public function test_periksa_tidak_menyimpan_lalu_proses_membuat_pengguna_dengan_peran_dan_sandi(): void
    {
        $isi = self::HEADER
            ."22650101,Ahmad Fauzi,mahasiswa,STI,ahmad@mhs.id,081234,2022,Baubau,2003-08-17,\"Jl. A, Baubau\",,,rahasia123,ya\n"
            ."0912099001,Siti Aisyah,Dosen/Tendik,Teknik Sipil,,,,,17/08/1985,,,\"S.T., M.T.\",,\n"
            ."0912099002,Budi,dosen_tendik|kaprodi,RSK,,,,,,,,,,tidak\n";

        $this->periksa($isi)->assertOk()->assertSee('Akan dibuat')->assertSee('Impor 3 Pengguna');
        $this->assertNull(User::where('nomor_induk', '22650101')->first(), 'periksa tidak menyimpan');

        $this->proses()->assertRedirect('/master/pengguna/impor/hasil');
        $a = $this->u('22650101');
        $this->assertTrue($a->hasRole('mahasiswa'));
        $this->assertTrue(\Hash::check('rahasia123', $a->password));
        $this->assertSame('STI', $a->prodi->kode);
        $this->assertSame('Jl. A, Baubau', $a->alamat);
        $this->assertSame('2003-08-17', $a->tanggal_lahir->toDateString());

        $s = $this->u('0912099001');
        $this->assertTrue($s->hasRole('dosen_tendik'));
        $this->assertSame('Teknik Sipil', $s->prodi->nama);
        $this->assertSame('1985-08-17', $s->tanggal_lahir->toDateString());
        $this->assertSame('S.T., M.T.', $s->gelar_belakang);
        $this->assertNotSame('', $s->password);

        $b = $this->u('0912099002');
        $this->assertEqualsCanonicalizing(['dosen_tendik', 'kaprodi'], $b->getRoleNames()->all());
        $this->assertFalse($b->aktif);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'impor_pengguna']);
    }

    public function test_kata_sandi_acak_dibuat_untuk_yang_kosong_dan_tampil_sekali(): void
    {
        $this->periksa(self::HEADER."22650102,Tanpa Sandi,mahasiswa,STI,,,,,,,,,,\n");
        $r = $this->proses();
        $r->assertRedirect('/master/pengguna/impor/hasil');
        $hasil = session('hasil_impor');
        $this->assertCount(1, $hasil);
        $pw = $hasil[0]['password'];
        $this->assertGreaterThanOrEqual(10, strlen($pw));
        $this->assertTrue(\Hash::check($pw, $this->u('22650102')->password));

        $this->get('/master/pengguna/impor/hasil')->assertOk()->assertSee($pw)->assertSee('hanya ditampilkan SEKALI');
        $this->get('/master/pengguna/impor/hasil')->assertRedirect('/master/pengguna');   // flash: tidak bisa dilihat lagi
    }

    public function test_pemisah_titik_koma_bom_dan_nama_kolom_alternatif(): void
    {
        $isi = "\xEF\xBB\xBFNIM;Nama Lengkap;Role;Program Studi;Kata Sandi\r\n22650103;Pakai Titik Koma;mahasiswa;STI;sandiku123\r\n";
        $this->periksa($isi)->assertOk()->assertSee('Akan dibuat');
        $this->proses();
        $this->assertTrue(\Hash::check('sandiku123', $this->u('22650103')->password));
    }

    public function test_pengkodean_windows_1252_dikonversi(): void
    {
        $isi = mb_convert_encoding(self::HEADER."22650104,Zoë Nuñez,mahasiswa,STI,,,,,,,,,,\n", 'Windows-1252', 'UTF-8');
        $this->periksa($isi);
        $this->proses();
        $this->assertSame('Zoë Nuñez', $this->u('22650104')->nama);
    }

    public function test_baris_bermasalah_dilaporkan_per_baris_dan_tidak_diimpor_tetapi_yang_valid_tetap_bisa(): void
    {
        $isi = self::HEADER
            ."22650110,Valid Satu,mahasiswa,STI,,,,,,,,,,\n"
            ."22650111,,mahasiswa,STI,,,,,,,,,,\n"                         // nama kosong
            ."22650112,Peran Salah,tukang,STI,,,,,,,,,,\n"                 // peran tak dikenal
            ."22650113,Prodi Salah,mahasiswa,XYZ,,,,,,,,,,\n"              // prodi tak ada
            ."22650114,Tanggal Salah,mahasiswa,STI,,,,,31/02/2000,,,,,\n"  // tanggal tak valid
            ."22650115,Email Salah,mahasiswa,STI,bukan-email,,,,,,,,,\n"
            ."22650116,Sandi Pendek,mahasiswa,STI,,,,,,,,,abc,\n"
            ."22650110,Dobel,mahasiswa,STI,,,,,,,,,,\n"                    // duplikat dalam berkas
            ."22650117,Super,super_admin,STI,,,,,,,,,,\n";                 // TU tidak boleh memberi super_admin
        $r = $this->periksa($isi)->assertOk();
        $r->assertSee('nama kosong')->assertSee('tidak dikenal')->assertSee('tidak ditemukan')->assertSee('tanggal_lahir tidak valid')->assertSee('email tidak valid')
            ->assertSee('minimal 8')->assertSee('sama dengan baris 2')->assertSee('Impor 1 Pengguna');

        $this->proses();
        $this->assertNotNull(User::where('nomor_induk', '22650110')->first());
        foreach (['22650111', '22650112', '22650113', '22650114', '22650115', '22650116', '22650117'] as $nik) {
            $this->assertNull(User::where('nomor_induk', $nik)->first(), "$nik tidak boleh masuk");
        }
    }

    public function test_pengguna_yang_sudah_ada_dilewati_atau_diperbarui_sesuai_pilihan(): void
    {
        $isi = self::HEADER."21650012,Nama Baru Fauzan,mahasiswa,TS,,,,,,,,,,\n";
        $this->periksa($isi)->assertSee('Dilewati');
        $this->proses();
        $this->assertSame('Muhammad Fauzan', $this->u('21650012')->nama);

        $this->periksa($isi, ['perbarui' => '1'])->assertSee('Akan diperbarui');
        $this->proses();
        $f = $this->u('21650012');
        $this->assertSame('Nama Baru Fauzan', $f->nama);
        $this->assertSame('TS', $f->prodi->kode);
        $this->assertTrue(\Hash::check('password', $f->password), 'sandi lama tidak berubah bila kolom kosong');
    }

    public function test_email_yang_sudah_dipakai_pengguna_lain_ditolak(): void
    {
        $this->periksa(self::HEADER."22650120,Email Dobel,mahasiswa,STI,fauzan@mhs.umbuton.ac.id,,,,,,,,,\n")->assertSee('email sudah dipakai');
    }

    public function test_admin_tu_tidak_dapat_menimpa_super_admin_tetapi_super_admin_dapat(): void
    {
        $isi = self::HEADER."0000000001,Super Diubah,super_admin,,,,,,,,,,,\n";
        $this->periksa($isi, ['perbarui' => '1'])->assertSee('Super Admin hanya dapat diubah');
        $this->proses();
        $this->assertSame('Super Admin', $this->u('0000000001')->nama);

        $this->periksa($isi, ['perbarui' => '1'], '0000000001')->assertSee('Akan diperbarui');
        $this->proses('0000000001');
        $this->assertSame('Super Diubah', $this->u('0000000001')->nama);
    }

    public function test_berkas_tidak_valid_ditolak_dengan_pesan_jelas(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/master/pengguna/impor/periksa', ['berkas' => UploadedFile::fake()->create('data.xlsx', 10)])->assertSessionHasErrors('berkas');
        $tu->post('/master/pengguna/impor/periksa', [])->assertSessionHasErrors('berkas');
        $tu->post('/master/pengguna/impor/periksa', ['berkas' => UploadedFile::fake()->createWithContent('kosong.csv', self::HEADER)])->assertSessionHasErrors('berkas');
        $tu->post('/master/pengguna/impor/periksa', ['berkas' => UploadedFile::fake()->createWithContent('salah.csv', "nama,umur\nA,3\n")])->assertSessionHasErrors('berkas');
        $tu->post('/master/pengguna/impor/periksa', ['berkas' => UploadedFile::fake()->createWithContent('biner.csv', "nomor_induk,nama\0,peran\n")])->assertSessionHasErrors('berkas');
        $baris = self::HEADER.implode('', array_map(fn ($i) => "7{$i},N{$i},mahasiswa,STI,,,,,,,,,,\n", range(1000, 1000 + ImporPengguna::MAKS_BARIS)));
        $tu->post('/master/pengguna/impor/periksa', ['berkas' => UploadedFile::fake()->createWithContent('besar.csv', $baris)])->assertSessionHasErrors('berkas');
    }

    public function test_proses_tanpa_periksa_atau_kedaluwarsa_ditolak(): void
    {
        $this->proses()->assertRedirect('/master/pengguna/impor');
        $this->periksa(self::HEADER."22650130,Kadaluarsa,mahasiswa,STI,,,,,,,,,,\n");
        $this->travel(31)->minutes();
        $this->proses()->assertRedirect('/master/pengguna/impor');
        $this->assertNull(User::where('nomor_induk', '22650130')->first());
    }

    public function test_hanya_tu_dan_super_admin_yang_boleh_mengimpor(): void
    {
        foreach (['21650012', '0912038401', '0912078603', '0912088704'] as $nik) {
            $u = $this->actingAs($this->u($nik));
            $u->get('/master/pengguna/impor')->assertForbidden();
            $u->get('/master/pengguna/impor/templat')->assertForbidden();
            $u->post('/master/pengguna/impor/periksa', ['berkas' => $this->csv(self::HEADER."1,A,mahasiswa,STI,,,,,,,,,,\n")])->assertForbidden();
            $u->post('/master/pengguna/impor/proses')->assertForbidden();
        }
        $this->actingAs($this->u('198701012010011001'))->get('/master/pengguna/impor')->assertOk()->assertSee('Unduh Templat CSV')->assertSee('nomor_induk');
        $this->actingAs($this->u('198701012010011001'))->get('/master/pengguna')->assertOk()->assertSee('Impor CSV');
    }
}
