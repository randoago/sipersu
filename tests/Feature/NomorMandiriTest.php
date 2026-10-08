<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\Pengaturan;
use App\Models\Penomoran;
use App\Models\Surat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\AlurSuratKeluar;
use App\Services\KunciTte;
use App\Support\ContohIsian;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Penomoran mandiri: TU hanya mengetik NOMOR URUT depan (mis. 009); sisanya mengikuti pola penomoran. */
class NomorMandiriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-nm-'.getmypid()]);
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

    private function lengkap(int $urut, string $klasifikasi = 'A', ?string $kekhususan = null): string
    {
        return app(\App\Services\PenomoranService::class)->format($urut, $klasifikasi, now(), null, $kekhususan);
    }

    private function bebas(array $o = []): array
    {
        return $o + ContohIsian::umum() + ['mode_ttd' => 'qr'];
    }

    public function test_tu_mengetik_angka_urut_dan_sisanya_mengikuti_pola(): void
    {
        [$tu, $wadek, $dekan] = [$this->u('198701012010011001'), $this->u('0912048102'), $this->u('0912038401')];
        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => ' 9 ', 'paraf_role' => 'wakil_dekan']))->assertRedirect();
        $s = Surat::firstOrFail();
        $this->assertSame('9', $s->nomor_manual);
        $this->assertNull($s->nomor, 'belum terbit');
        $this->actingAs($tu)->get("/surat-keluar/{$s->id}")->assertOk()->assertSee($this->lengkap(9));   // pratinjau sudah memuat nomor lengkap

        $alur = app(AlurSuratKeluar::class);
        $s = $alur->tandatangani($alur->paraf($alur->ajukan($s, $tu), $wadek, null), $dekan);

        $this->assertSame($this->lengkap(9), $s->nomor);
        $this->assertStringStartsWith('009/II.3.AU/UMB-06/A/', $s->nomor);
        $this->assertNotNull($s->qr_token);
        $this->assertSame($this->lengkap(9), json_decode(app(\App\Services\TandaTanganService::class)->payload($s), true)['n']);
        $this->assertSame(9, (int) Penomoran::first()->nomor_terakhir, 'penghitung otomatis menyesuaikan nomor manual');

        $berikut = $alur->ajukan($alur->simpan($tu, $this->bebas(['mode_ttd' => 'basah', 'paraf_role' => null])), $tu);
        $this->assertStringStartsWith('010/II.3.AU/UMB-06/A', $berikut->nomor, 'nomor otomatis berikutnya melanjutkan');
    }

    public function test_kosong_tetap_nomor_otomatis(): void
    {
        $tu = $this->u('198701012010011001');
        $alur = app(AlurSuratKeluar::class);
        $s = $alur->ajukan($alur->simpan($tu, $this->bebas(['mode_ttd' => 'basah', 'paraf_role' => null])), $tu);

        $this->assertStringStartsWith('001/II.3.AU', $s->nomor);
    }

    public function test_nomor_urut_ganda_dan_bukan_angka_ditolak(): void
    {
        $tu = $this->u('198701012010011001');
        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => '009']))->assertRedirect();

        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => '9']))->assertSessionHasErrors('nomor_manual');      // 009 = 9: nomor lengkap sama dengan draf lain
        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => '009/II.3.AU/UMB-06/A/'.now()->year]))->assertSessionHasErrors('nomor_manual');   // nomor lengkap yang sama dengan 009
        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => '#12']))->assertSessionHasErrors('nomor_manual');
        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => '<script>']))->assertSessionHasErrors('nomor_manual');
        $this->assertSame(1, Surat::count());

        // klasifikasi berbeda = nomor lengkap berbeda: boleh
        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => '009', 'klasifikasi_id' => \App\Models\KlasifikasiSurat::where('kode', 'F')->value('id')]))->assertRedirect();
        $this->assertSame(2, Surat::count());

        $terbit = app(AlurSuratKeluar::class)->ajukan(app(AlurSuratKeluar::class)->simpan($tu, $this->bebas(['mode_ttd' => 'basah', 'paraf_role' => null, 'klasifikasi_id' => \App\Models\KlasifikasiSurat::where('kode', 'F')->value('id')])), $tu);
        $this->assertStringStartsWith('001/II.3.AU/UMB-06/F', $terbit->nomor);
        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => '1', 'klasifikasi_id' => $terbit->klasifikasi_id]))->assertSessionHasErrors('nomor_manual');   // bentrok dengan nomor yang sudah terbit
    }

    public function test_hanya_admin_yang_mengetik_nomor_dan_hanya_sebelum_terbit(): void
    {
        $dosen = $this->u('0912088704');
        $tu = $this->u('198701012010011001');
        $this->actingAs($dosen)->post('/surat-keluar', $this->bebas(['nomor_manual' => '5']))->assertRedirect();
        $s = Surat::firstOrFail();
        $this->assertNull($s->nomor_manual, 'isian nomor dari non-admin diabaikan');

        $this->actingAs($dosen)->post("/surat-keluar/{$s->id}/nomor", ['nomor_manual' => '5'])->assertForbidden();
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/nomor", ['nomor_manual' => '12'])->assertRedirect();
        $this->assertSame('12', $s->fresh()->nomor_manual);

        $s->update(['mode_ttd' => 'basah']);
        app(AlurSuratKeluar::class)->ajukan($s->fresh(), $tu);
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/nomor", ['nomor_manual' => '13'])->assertSessionHasErrors('nomor_manual');   // sudah terbit
        $this->assertSame($this->lengkap(12), $s->fresh()->nomor);
    }

    public function test_mode_manual_mewajibkan_nomor_sebelum_diajukan(): void
    {
        Pengaturan::simpan('penomoran_mode', 'manual');
        $tu = $this->u('198701012010011001');
        $alur = app(AlurSuratKeluar::class);

        $this->actingAs($tu)->post('/surat-keluar', $this->bebas())->assertSessionHasErrors('nomor_manual');          // form admin: wajib

        $s = $alur->simpan($tu, $this->bebas(['mode_ttd' => 'basah', 'paraf_role' => null]));                       // draf dari non-form
        try {
            $alur->ajukan($s, $tu);
            $this->fail('seharusnya ditolak');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('nomor_manual', $e->errors());
        }

        $alur->aturNomor($s, $tu, '007');
        $this->assertSame($this->lengkap(7), $alur->ajukan($s->fresh(), $tu)->nomor);
    }

    public function test_pengajuan_mahasiswa_memakai_nomor_dari_tu_saat_verifikasi(): void
    {
        [$tu, $dekan, $mhs] = [$this->u('198701012010011001'), $this->u('0912038401'), $this->u('21650012')];
        $alur = app(AlurPengajuan::class);
        $jenis = JenisSurat::where('kode', 'KET-AKTIF')->first();
        $this->actingAs($tu)->post(route('pengajuan.verifikasi', $alur->ajukan($mhs, $jenis, ContohIsian::mahasiswa()['KET-AKTIF'])), ['nomor_surat' => '123'])->assertRedirect();

        $p = \App\Models\Pengajuan::firstOrFail();
        $this->assertSame('123', $p->surat->nomor_manual);
        $p = $alur->tandatangani($p, $dekan);
        $this->assertSame($this->lengkap(123, 'F', 'KET'), $p->surat->nomor);

        // mode manual: verifikasi tanpa nomor ditolak; nomor dapat diisi lewat halaman pengajuan sebelum TTD
        Pengaturan::simpan('penomoran_mode', 'manual');
        $p2 = $alur->ajukan($mhs, $jenis, ContohIsian::mahasiswa()['KET-AKTIF']);
        $this->actingAs($tu)->post(route('pengajuan.verifikasi', $p2), [])->assertSessionHasErrors('nomor_surat');
        $this->actingAs($tu)->post(route('pengajuan.verifikasi', $p2), ['nomor_surat' => '124'])->assertRedirect();
        $this->actingAs($tu)->post(route('pengajuan.nomor', $p2), ['nomor_manual' => '125'])->assertRedirect();
        $this->assertSame('125', $p2->fresh()->surat->nomor_manual);
    }

    public function test_pengajuan_tanpa_qr_terbit_langsung_dengan_nomor_dari_tu(): void
    {
        JenisSurat::where('kode', 'KET-AKTIF')->update(['mode_ttd' => 'basah']);
        $alur = app(AlurPengajuan::class);
        $p = $alur->ajukan($this->u('21650012'), JenisSurat::where('kode', 'KET-AKTIF')->first(), ContohIsian::mahasiswa()['KET-AKTIF']);
        $p = $alur->verifikasi($p, $this->u('198701012010011001'), null, '9');

        $this->assertSame('ditandatangani', $p->status->value);
        $this->assertSame($this->lengkap(9, 'F', 'KET'), $p->surat->nomor);
    }

    public function test_nomor_lengkap_dapat_diedit_tu_dan_dipakai_apa_adanya(): void
    {
        [$tu, $dekan] = [$this->u('198701012010011001'), $this->u('0912038401')];
        $alur = app(AlurSuratKeluar::class);
        $nomor = '12/KET/II.3.AU/UMB-06.2/F/'.now()->year;   // diedit TU: unit Rekayasa Sistem Komputer, kekhususan Keterangan, pokok masalah F

        $s = $alur->simpan($tu, $this->bebas(['mode_ttd' => 'basah', 'paraf_role' => null]));
        $this->actingAs($tu)->post("/surat-keluar/{$s->id}/nomor", ['nomor_manual' => $nomor])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($nomor, $s->fresh()->nomor_manual);
        $this->actingAs($tu)->get("/surat-keluar/{$s->id}")->assertOk()->assertSee($nomor);

        $terbit = $alur->ajukan($s->fresh(), $tu);
        $this->assertSame($nomor, $terbit->nomor);

        // unit lain (UMB-06.2) tidak menggeser penghitung fakultas (UMB-06)
        $this->assertNull(Penomoran::where('unit', 'UMB-06')->first());
        $berikut = $alur->ajukan($alur->simpan($tu, $this->bebas(['mode_ttd' => 'basah', 'paraf_role' => null])), $tu);
        $this->assertStringStartsWith('001/II.3.AU/UMB-06/A', $berikut->nomor);

        // nomor lengkap yang sama dengan surat terbit ditolak
        $this->actingAs($tu)->post('/surat-keluar', $this->bebas(['nomor_manual' => $nomor]))->assertSessionHasErrors('nomor_manual');
    }

    public function test_nomor_lengkap_unit_fakultas_menggeser_penghitung(): void
    {
        $tu = $this->u('198701012010011001');
        $alur = app(AlurSuratKeluar::class);
        $s = $alur->simpan($tu, $this->bebas(['mode_ttd' => 'basah', 'paraf_role' => null]));
        $alur->aturNomor($s, $tu, '45/II.3.AU/UMB-06/A/'.now()->year);
        $this->assertSame('045/II.3.AU/UMB-06/A/'.now()->year, app(\App\Services\PenomoranService::class)->format(45, 'A', now()));
        $alur->ajukan($s->fresh(), $tu);
        $this->assertSame(45, (int) Penomoran::where('unit', 'UMB-06')->value('nomor_terakhir'));
    }

    public function test_pengaturan_menampilkan_pilihan_cara_penomoran(): void
    {
        $this->actingAs($this->u('198701012010011001'))->get('/pengaturan/nomor')->assertOk()->assertSee('Cara penomoran surat keluar')->assertSee('Manual (diisi TU)');
    }
}
