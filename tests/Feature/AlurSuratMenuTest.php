<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Models\Persetujuan;
use App\Models\User;
use App\Services\KunciTte;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlurSuratMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-alur-'.getmypid(), 'sipersu.batas_laju.aktif' => false]);
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

    private function tu(): User
    {
        return User::where('username', 'TU')->firstOrFail();
    }

    private function format(string $sasaran): JenisSurat
    {
        return JenisSurat::where('sasaran', $sasaran)->where('mode_ttd', 'qr')->firstOrFail();
    }

    private function isi(JenisSurat $f, array $o = []): array
    {
        return $o + [
            'mode_ttd' => $f->mode_ttd, 'perlu_paraf' => $f->perlu_paraf ? '1' : null, 'paraf_role' => $f->paraf_role,
            'verifikator_role' => $f->verifikator_role, 'penandatangan_jabatan_id' => $f->penandatangan_jabatan_id, 'sla_hari' => $f->sla_hari,
        ];
    }

    public function test_hanya_tu_dan_super_admin_yang_membuka_menu(): void
    {
        $this->get(route('alur-surat.index'))->assertRedirect(route('login'));
        $this->actingAs(User::where('nomor_induk', '21650012')->firstOrFail())->get(route('alur-surat.index'))->assertForbidden();
        $this->actingAs($this->tu())->get(route('alur-surat.index'))->assertOk()->assertSee('Alur Surat')->assertSee($this->format('mahasiswa')->nama);
        $this->actingAs(User::where('username', 'superadmin')->firstOrFail())->get(route('alur-surat.index'))->assertOk();
    }

    public function test_menu_muncul_di_sidebar_tu(): void
    {
        $this->actingAs($this->tu())->get(route('dasbor'))->assertSee(route('alur-surat.index'), false);
    }

    public function test_tu_mengubah_pemaraf_dan_penandatangan_surat_keluar(): void
    {
        $f = $this->format('staf');
        $dekan = Jabatan::where('kode', 'dekan')->value('id');
        $this->actingAs($this->tu())->put(route('alur-surat.perbarui', $f), $this->isi($f, ['perlu_paraf' => '1', 'paraf_role' => 'wakil_dekan', 'penandatangan_jabatan_id' => $dekan]))
            ->assertRedirect(route('alur-surat.index'))->assertSessionHas('sukses');
        $f->refresh();
        $this->assertTrue($f->perlu_paraf);
        $this->assertSame('wakil_dekan', $f->paraf_role);
        $this->assertSame($dekan, $f->penandatangan_jabatan_id);
        $this->assertSame('admin_tu', $f->verifikator_role);
        $this->assertNotNull(LogAktivitas::where('aksi', 'alur_ubah')->first());
    }

    public function test_tanpa_qr_menghapus_paraf_dan_sasaran_non_mahasiswa_selalu_diverifikasi_tu(): void
    {
        $f = $this->format('staf');
        $this->actingAs($this->tu())->put(route('alur-surat.perbarui', $f), $this->isi($f, ['mode_ttd' => 'basah', 'perlu_paraf' => '1', 'paraf_role' => 'kaprodi', 'verifikator_role' => 'kaprodi']))->assertSessionHasNoErrors();
        $f->refresh();
        $this->assertTrue($f->langsungTerbit());
        $this->assertFalse($f->perlu_paraf);
        $this->assertNull($f->paraf_role);
        $this->assertSame('admin_tu', $f->verifikator_role);
    }

    public function test_paraf_wajib_memilih_pemaraf_dan_masukan_dijaga(): void
    {
        $f = $this->format('staf');
        $c = $this->actingAs($this->tu());
        $c->put(route('alur-surat.perbarui', $f), $this->isi($f, ['perlu_paraf' => '1', 'paraf_role' => null]))->assertSessionHasErrorsIn('f'.$f->id, 'paraf_role');
        $c->put(route('alur-surat.perbarui', $f), $this->isi($f, ['paraf_role' => 'dekan', 'perlu_paraf' => '1']))->assertSessionHasErrorsIn('f'.$f->id, 'paraf_role');
        $c->put(route('alur-surat.perbarui', $f), $this->isi($f, ['penandatangan_jabatan_id' => 99999]))->assertSessionHasErrorsIn('f'.$f->id, 'penandatangan_jabatan_id');
        $m = $this->format('mahasiswa');
        $c->put(route('alur-surat.perbarui', $m), $this->isi($m, ['sla_hari' => 0]))->assertSessionHasErrorsIn('f'.$m->id, 'sla_hari');
        $c->put(route('alur-surat.perbarui', $m), $this->isi($m, ['verifikator_role' => 'dekan']))->assertSessionHasErrorsIn('f'.$m->id, 'verifikator_role');
    }

    public function test_surat_masuk_tidak_punya_alur(): void
    {
        $masuk = JenisSurat::where('sasaran', 'masuk')->firstOrFail();
        $this->actingAs($this->tu())->put(route('alur-surat.perbarui', $masuk), $this->isi($masuk))->assertNotFound();
    }

    public function test_pengajuan_baru_mengikuti_alur_yang_diubah(): void
    {
        $this->seed(DemoSeeder::class);
        $f = JenisSurat::where('sasaran', 'mahasiswa')->where('mode_ttd', 'qr')->where('perlu_paraf', false)->where('verifikator_role', 'admin_tu')->firstOrFail();
        $f->pengajuan()->update(['status' => 'selesai']);
        $this->actingAs($this->tu())->put(route('alur-surat.perbarui', $f), $this->isi($f, ['perlu_paraf' => '1', 'paraf_role' => 'wakil_dekan']))->assertSessionHasNoErrors();

        $mhs = User::where('nomor_induk', '21650012')->firstOrFail();
        $p = app(\App\Services\AlurPengajuan::class)->ajukan($mhs, $f->refresh(), []);
        $this->assertSame(['verifikasi', 'paraf', 'ttd'], $p->persetujuan()->orderBy('urutan')->pluck('tahap')->all());
        $this->assertSame('wakil_dekan', $p->persetujuan()->where('tahap', 'paraf')->value('peran'));
    }

    public function test_bentuk_tidak_boleh_diubah_saat_ada_pengajuan_berjalan_tapi_penandatangan_boleh(): void
    {
        $this->seed(DemoSeeder::class);
        $p = Pengajuan::whereIn('status', ['diajukan', 'diverifikasi', 'disetujui'])->firstOrFail();
        $f = $p->jenis;
        $c = $this->actingAs($this->tu());

        $c->put(route('alur-surat.perbarui', $f), $this->isi($f, ['mode_ttd' => $f->mode_ttd === 'qr' ? 'basah' : 'qr']))->assertSessionHasErrorsIn('f'.$f->id, 'alur');
        $this->assertSame($f->mode_ttd, $f->fresh()->mode_ttd);

        $lain = Jabatan::where('aktif', true)->where('id', '!=', $f->penandatangan_jabatan_id)->firstOrFail();
        $c->put(route('alur-surat.perbarui', $f), $this->isi($f, ['penandatangan_jabatan_id' => $lain->id, 'sla_hari' => 9]))->assertSessionHasNoErrors();
        $this->assertSame($lain->id, $f->fresh()->penandatangan_jabatan_id);
        $this->assertSame(9, $f->fresh()->sla_hari);
        $ttd = Persetujuan::where('pengajuan_id', $p->id)->where('tahap', 'ttd')->where('status', 'menunggu')->first();
        if ($ttd) {
            $this->assertSame($lain->id, $ttd->jabatan_id);
        }
    }

    public function test_langkah_alur_menggambarkan_bentuk_surat(): void
    {
        $f = $this->format('mahasiswa');
        $f->fill(['perlu_paraf' => true, 'paraf_role' => 'wakil_dekan', 'verifikator_role' => 'kaprodi']);
        $this->assertSame(['Pengajuan', 'Verifikasi', 'Paraf', 'Tanda tangan (QR)', 'Terbit'], array_column($f->langkahAlur(), 'judul'));
        $f->mode_ttd = 'basah';
        $this->assertSame(['Pengajuan', 'Verifikasi', 'Terbit & cetak'], array_column($f->langkahAlur(), 'judul'));
        $s = $this->format('staf');
        $this->assertSame('Draf surat', $s->langkahAlur()[0]['judul']);
    }
}
