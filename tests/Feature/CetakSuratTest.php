<?php

namespace Tests\Feature;

use App\Models\JenisSurat;
use App\Models\User;
use App\Services\AlurPengajuan;
use App\Services\AlurSuratKeluar;
use App\Services\KunciTte;
use App\Support\ContohIsian;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Pratinjau web surat yang dapat langsung dicetak, dengan tombol Tambahkan / Tanpa Tanda Tangan. */
class CetakSuratTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-ct-'.getmypid()]);
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

    public function test_surat_tanpa_qr_menawarkan_tambah_tanda_tangan_dan_cetak(): void
    {
        $tu = $this->u('198701012010011001');
        $alur = app(AlurSuratKeluar::class);
        $s = $alur->ajukan($alur->simpan($tu, ['mode_ttd' => 'basah', 'paraf_role' => null] + ContohIsian::umum()), $tu);

        $this->actingAs($tu)->get("/surat-keluar/{$s->id}")->assertOk()
            ->assertSee('Cetak')->assertSee('Tambahkan Tanda Tangan')->assertSee('id="kertas-cetak"', false)
            ->assertSee('class="ttd-spesimen"', false)->assertSee('ttd-kosong', false);
    }

    public function test_surat_ber_qr_terbit_menawarkan_tanpa_tanda_tangan(): void
    {
        [$tu, $wadek, $dekan] = [$this->u('198701012010011001'), $this->u('0912048102'), $this->u('0912038401')];
        $alur = app(AlurSuratKeluar::class);
        $s = $alur->simpan($tu, ['mode_ttd' => 'qr', 'paraf_role' => 'wakil_dekan'] + ContohIsian::umum());
        $s = $alur->tandatangani($alur->paraf($alur->ajukan($s, $tu), $wadek, null), $dekan);

        $this->actingAs($tu)->get("/surat-keluar/{$s->id}")->assertOk()->assertSee('Tanpa Tanda Tangan')->assertSee('Cetak')->assertSee('class="ttd-spesimen"', false);
    }

    public function test_halaman_pengajuan_menampilkan_pratinjau_web_untuk_petugas_dan_pemohon_setelah_terbit(): void
    {
        $alur = app(AlurPengajuan::class);
        [$tu, $dekan, $mhs] = [$this->u('198701012010011001'), $this->u('0912038401'), $this->u('21650012')];
        $p = $alur->ajukan($mhs, JenisSurat::where('kode', 'KET-AKTIF')->first(), ContohIsian::mahasiswa()['KET-AKTIF']);
        $p = $alur->verifikasi($p, $tu);                                         // disetujui: belum terbit

        $this->actingAs($tu)->get(route('pengajuan.show', $p))->assertOk()->assertSee('Pratinjau Surat')->assertSee('id="kertas-cetak"', false);
        $this->actingAs($mhs)->get(route('pengajuan.show', $p))->assertOk()->assertDontSee('Pratinjau Surat');

        $alur->tandatangani($p, $dekan);
        $this->actingAs($mhs)->get(route('pengajuan.show', $p))->assertOk()->assertSee('Pratinjau Surat')->assertSee('Cetak');
    }
}
