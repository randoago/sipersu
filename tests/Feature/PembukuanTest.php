<?php

namespace Tests\Feature;

use App\Models\KlasifikasiSurat;
use App\Models\Pembukuan;
use App\Models\Penomoran;
use App\Models\User;
use App\Services\AlurSuratKeluar;
use App\Services\KunciTte;
use App\Support\ContohIsian;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pembukuan surat (buku agenda): catatan manual dan impor CSV berdasarkan nomor surat. */
class PembukuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-pb2-'.getmypid()]);
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

    private function csv(string $isi): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('pembukuan.csv', "\xEF\xBB\xBF".$isi);
    }

    private const CSV = <<<'CSV'
arah,nomor,tanggal_surat,pihak,perihal,lampiran,sifat,jenis,tanggal_diterima,no_agenda,keterangan
keluar,001/II.3.AU/UMB-06/A/2026,2026-01-05,Seluruh Dosen,Pemberitahuan Libur,-,biasa,Surat Pemberitahuan,,,
keluar,045/II.3.AU/UMB-06/A/2026,12/03/2026,Rektor,Laporan Triwulan,1 berkas,penting,,,,
masuk,B-018/REK/UMB/I/2026,2026-01-08,Rektorat UMB,Edaran Evaluasi Kinerja,2 berkas,penting,,2026-01-09,AGD-2026/I/0001,
lain,SK/045/FT-UMB/I/2026,2026-01-12,,SK Dekan Panitia Wisuda,,biasa,SK Dekan,,,
keluar,045/II.3.AU/UMB-06/A/2026,12/03/2026,Rektor,Duplikat dalam berkas,,,,,,
foo,009/X,2026-01-01,,Arah salah,,,,,,
keluar,010/II.3.AU/UMB-06/A/2026,31/02/2026,,Tanggal salah,,,,,,
CSV;

    public function test_hanya_admin_dekan_wadek_melihat_dan_hanya_admin_mencatat(): void
    {
        foreach (['198701012010011001', '0000000001', '0912038401', '0912048102'] as $nik) {
            $this->actingAs($this->u($nik))->get('/pembukuan')->assertOk()->assertSee('Pembukuan Surat');
        }
        foreach (['0912088704', '21650012', '0912078603'] as $nik) {
            $this->actingAs($this->u($nik))->get('/pembukuan')->assertForbidden();
        }
        foreach (['/pembukuan/impor', '/pembukuan/buat'] as $url) {
            $this->actingAs($this->u('0912038401'))->get($url)->assertForbidden();
            $this->actingAs($this->u('198701012010011001'))->get($url)->assertOk();
        }
        $this->actingAs($this->u('198701012010011001'))->get('/pembukuan/impor/templat')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_impor_csv_periksa_lalu_proses_dan_penghitung_otomatis_melanjutkan(): void
    {
        $tu = $this->u('198701012010011001');
        $r = $this->actingAs($tu)->post('/pembukuan/impor/periksa', ['berkas' => $this->csv(self::CSV)])->assertOk();
        $r->assertSee('Penghitung nomor otomatis')->assertSee('II.3.AU')->assertSee('046')->assertSee('arah harus masuk / keluar / lain')->assertSee('tanggal_surat tidak valid')->assertSee('Nomor sama dengan baris');
        $this->assertSame(0, Pembukuan::count(), 'periksa belum menyimpan');

        $this->actingAs($tu)->post('/pembukuan/impor/proses', ['sinkron' => '1'])->assertRedirect('/pembukuan/impor/hasil');
        $this->assertSame(4, Pembukuan::count());
        $per = Pembukuan::query()->selectRaw('arah, count(*) c')->groupBy('arah')->pluck('c', 'arah')->all();
        ksort($per);
        $this->assertSame(['keluar' => 2, 'lain' => 1, 'masuk' => 1], $per);
        $this->assertSame(45, Pembukuan::where('nomor', '045/II.3.AU/UMB-06/A/2026')->value('no_urut'));
        $this->assertSame('2026-03-12', Pembukuan::where('nomor', '045/II.3.AU/UMB-06/A/2026')->first()->tgl_surat->toDateString());
        $this->assertSame('2026-01-09', Pembukuan::where('arah', 'masuk')->first()->tgl_diterima->toDateString());
        $this->actingAs($tu)->get('/pembukuan/impor/hasil')->assertOk()->assertSee('4 surat dicatat');

        $this->assertSame(45, (int) Penomoran::where(['unit' => 'UMB-06', 'tahun' => 2026])->value('nomor_terakhir'));

        $alur = app(AlurSuratKeluar::class);
        $s = $alur->ajukan($alur->simpan($tu, ['mode_ttd' => 'basah', 'paraf_role' => null] + ContohIsian::umum()), $tu);
        $this->assertStringStartsWith('046/II.3.AU', $s->nomor, 'nomor otomatis melanjutkan nomor tertinggi di buku');
    }

    public function test_tanpa_sinkron_penghitung_tidak_berubah_tetapi_nomor_terdaftar_dilewati_saat_terbit(): void
    {
        $tu = $this->u('198701012010011001');
        $csv = "arah,nomor,tanggal_surat,perihal\nkeluar,001/II.3.AU/UMB-06/A/2026,2026-10-01,Surat lama\nkeluar,002/II.3.AU/UMB-06/A/2026,2026-10-02,Surat lama dua\n";
        $this->actingAs($tu)->post('/pembukuan/impor/periksa', ['berkas' => $this->csv($csv)])->assertOk();
        $this->actingAs($tu)->post('/pembukuan/impor/proses', [])->assertRedirect();   // tanpa centang sinkron
        $this->assertSame(0, Penomoran::count());

        $alur = app(AlurSuratKeluar::class);
        $s = $alur->ajukan($alur->simpan($tu, ['mode_ttd' => 'basah', 'paraf_role' => null] + ContohIsian::umum()), $tu);
        $this->assertStringStartsWith('003/II.3.AU', $s->nomor, 'nomor 001 dan 002 sudah tercatat: dilewati');
    }

    public function test_impor_ulang_dan_nomor_surat_aplikasi_dilewati(): void
    {
        $tu = $this->u('198701012010011001');
        $alur = app(AlurSuratKeluar::class);
        $terbit = $alur->ajukan($alur->simpan($tu, ['mode_ttd' => 'basah', 'paraf_role' => null] + ContohIsian::umum()), $tu);
        $csv = "arah,nomor,tanggal_surat,perihal\nkeluar,{$terbit->nomor},2026-10-01,Bentrok dengan surat aplikasi\nkeluar,777/II.3.AU/UMB-06/A/2026,2026-10-01,Baru\n";

        $this->actingAs($tu)->post('/pembukuan/impor/periksa', ['berkas' => $this->csv($csv)])->assertOk()->assertSee('sudah ada di pembukuan/aplikasi');
        $this->actingAs($tu)->post('/pembukuan/impor/proses', [])->assertRedirect();
        $this->assertSame(1, Pembukuan::count());

        $this->actingAs($tu)->post('/pembukuan/impor/periksa', ['berkas' => $this->csv($csv)])->assertOk();     // impor ulang: semua dilewati
        $this->actingAs($tu)->post('/pembukuan/impor/proses', [])->assertRedirect('/pembukuan/impor');            // tidak ada baris baru
        $this->assertSame(1, Pembukuan::count());
    }

    public function test_berkas_tidak_valid_ditolak_dengan_pesan(): void
    {
        $tu = $this->actingAs($this->u('198701012010011001'));
        $tu->post('/pembukuan/impor/periksa', ['berkas' => UploadedFile::fake()->createWithContent('x.csv', "nama,umur\nA,1\n")])->assertSessionHasErrors('berkas');
        $tu->post('/pembukuan/impor/periksa', ['berkas' => UploadedFile::fake()->createWithContent('x.csv', "arah,nomor,tanggal_surat,perihal\n")])->assertSessionHasErrors('berkas');
        $tu->post('/pembukuan/impor/proses', [])->assertRedirect('/pembukuan/impor');
    }

    public function test_buku_menggabungkan_surat_aplikasi_dan_catatan_dengan_filter_dan_ekspor(): void
    {
        $tu = $this->u('198701012010011001');
        $alur = app(AlurSuratKeluar::class);
        $terbit = $alur->ajukan($alur->simpan($tu, ['mode_ttd' => 'basah', 'paraf_role' => null, 'perihal' => 'Surat dari Aplikasi'] + ContohIsian::umum()), $tu);
        Pembukuan::create(['arah' => 'masuk', 'nomor' => 'B-1/2025', 'tgl_surat' => '2025-05-01', 'pihak' => 'Dinas X', 'perihal' => 'Rahasia Lama', 'sifat' => 'rahasia', 'sumber' => 'impor']);
        Pembukuan::create(['arah' => 'keluar', 'nomor' => '=HYPERLINK("x")', 'tgl_surat' => '2025-06-01', 'perihal' => 'Rumus berbahaya', 'sumber' => 'manual']);

        $this->actingAs($tu)->get('/pembukuan')->assertOk()->assertSee($terbit->nomor)->assertSee('Surat dari Aplikasi')->assertSee('B-1/2025')->assertSee('Rahasia Lama');
        $this->actingAs($tu)->get('/pembukuan?arah=masuk')->assertOk()->assertSee('B-1/2025')->assertDontSee('Surat dari Aplikasi');
        $this->actingAs($tu)->get('/pembukuan?tahun=2025')->assertOk()->assertSee('B-1/2025')->assertDontSee($terbit->nomor);
        $this->actingAs($tu)->get('/pembukuan?sumber=aplikasi')->assertOk()->assertSee('Surat dari Aplikasi')->assertDontSee('B-1/2025');
        $this->actingAs($tu)->get('/pembukuan?q=Dinas')->assertOk()->assertSee('B-1/2025');

        $dekan = $this->u('0912038401');
        $this->actingAs($dekan)->get('/pembukuan')->assertOk()->assertSee('(rahasia)')->assertDontSee('Rahasia Lama');

        $csv = $this->actingAs($tu)->get('/pembukuan/ekspor')->assertOk()->getContent();
        $this->assertStringContainsString($terbit->nomor, $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv, 'sel berawalan = diberi tanda kutip agar tidak jadi rumus');
        $this->assertStringContainsString('Rahasia Lama', $csv);
        $this->assertStringNotContainsString('Rahasia Lama', $this->actingAs($dekan)->get('/pembukuan/ekspor')->getContent(), 'perihal rahasia disamarkan untuk non-admin');
    }

    public function test_catatan_manual_ubah_hapus_dan_tolak_nomor_ganda(): void
    {
        $tu = $this->u('198701012010011001');
        $isi = ['arah' => 'keluar', 'nomor' => '012/TGS/II.3.AU/UMB-06/D/2026', 'tgl_surat' => '2026-02-03', 'perihal' => 'Surat Tugas Lama', 'sifat' => 'biasa', 'sinkron' => '1'];
        $this->actingAs($tu)->post('/pembukuan', $isi)->assertRedirect('/pembukuan');
        $b = Pembukuan::firstOrFail();
        $this->assertSame(12, $b->no_urut);
        $this->assertSame(12, (int) Penomoran::where('tahun', 2026)->value('nomor_terakhir'));

        $this->actingAs($tu)->post('/pembukuan', $isi)->assertSessionHasErrors('nomor');                                          // ganda
        $this->actingAs($tu)->post('/pembukuan', ['nomor' => '<b>x</b>'] + $isi)->assertSessionHasErrors('nomor');
        $this->actingAs($tu)->put("/pembukuan/{$b->id}", ['perihal' => 'Surat Tugas Lama (revisi)'] + $isi)->assertRedirect('/pembukuan');
        $this->assertSame('Surat Tugas Lama (revisi)', $b->fresh()->perihal);
        $this->actingAs($tu)->get('/pembukuan?q=revisi')->assertOk()->assertSee('012/TGS/II.3.AU/UMB-06/D/2026');

        $this->actingAs($this->u('0912038401'))->delete("/pembukuan/{$b->id}")->assertForbidden();
        $this->actingAs($tu)->delete("/pembukuan/{$b->id}")->assertRedirect('/pembukuan');
        $this->assertSame(0, Pembukuan::count());
    }

    public function test_nomor_manual_tu_bentrok_dengan_nomor_di_pembukuan(): void
    {
        $tu = $this->u('198701012010011001');
        $lengkap = app(\App\Services\PenomoranService::class)->format(9, 'A', now());
        Pembukuan::create(['arah' => 'keluar', 'nomor' => $lengkap, 'tgl_surat' => now()->toDateString(), 'perihal' => 'Sudah dibukukan', 'sumber' => 'manual']);

        $this->actingAs($tu)->post('/surat-keluar', ['nomor_manual' => '9', 'mode_ttd' => 'qr'] + ContohIsian::umum())->assertSessionHasErrors('nomor_manual');
    }
}
