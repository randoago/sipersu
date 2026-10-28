<?php

namespace Tests\Feature;

use App\Models\BackupRiwayat;
use App\Models\Lampiran;
use App\Models\Notifikasi;
use App\Models\Pembukuan;
use App\Models\Pengajuan;
use App\Models\Surat;
use App\Models\User;
use App\Services\KunciTte;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\DemoSuratMasukSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uji asap: membuka SEMUA halaman (GET) yang dapat dibuka tanpa parameter, ditambah halaman berparameter dengan data demo,
 * sebagai tiap peran. Tidak boleh ada galat server (5xx) atau pengecualian. Bukan menguji isi, melainkan mencegah halaman rusak.
 */
class UjiAsapWebTest extends TestCase
{
    use RefreshDatabase;

    private const PERAN = ['superadmin', 'TU', '0912038401', '0912048102', '0912058301', '0912088704', '21650012'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['sipersu.kunci_path' => sys_get_temp_dir().'/sipersu-kunci-asap-'.getmypid(), 'sipersu.batas_laju.aktif' => false]);
        KunciTte::buat(true);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSuratMasukSeeder::class);
    }

    protected function tearDown(): void
    {
        @unlink(KunciTte::jalur('ed25519.secret'));
        @unlink(KunciTte::jalur('ed25519.public'));
        @rmdir(config('sipersu.kunci_path'));
        parent::tearDown();
    }

    private function user(string $id): User
    {
        return User::where('nomor_induk', $id)->orWhere('username', $id)->firstOrFail();
    }

    /** Halaman GET tanpa parameter (kecuali aset Livewire, penyimpanan, dan unduhan/aksi). */
    private function halamanTanpaParameter(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true) && ! str_contains($r->uri(), '{') && ! preg_match('#^(livewire|up$|storage|api/|sanctum|v/)#', $r->uri()))
            ->map(fn ($r) => '/'.ltrim($r->uri(), '/'))
            ->reject(fn ($u) => str_contains($u, 'ekspor') || str_contains($u, 'templat'))   // unduhan berkas dites terpisah
            ->unique()->values()->all();
    }

    private function halamanBerparameter(): array
    {
        $penga = Pengajuan::orderBy('id')->get();
        $p = fn ($status) => $penga->firstWhere('status.value', $status);
        $u = [
            '/pengajuan/'.$penga->first()->id, '/persetujuan/'.$p('disetujui')->id, '/persetujuan/'.$p('diverifikasi')->id, '/persetujuan/'.$p('ditandatangani')->id,
            '/surat/'.$p('ditandatangani')->surat->id.'/pdf', '/surat-keluar/format/UNDANGAN-RAPAT', '/surat-keluar/format/SURAT-TUGAS',
            '/layanan/KET-AKTIF/ajukan', '/layanan/IZIN-PENELITIAN/ajukan', '/surat-masuk/catat/SM-UMUM', '/surat-masuk/catat/SM-PERMOHONAN',
            '/surat-masuk/'.Surat::where('arah', 'masuk')->value('id'), '/surat-masuk/'.Surat::where('arah', 'masuk')->value('id').'/ubah',
            '/format-surat/'.\App\Models\JenisSurat::where('kode', 'UNDANGAN-RAPAT')->value('id').'/ubah',
            '/master/spesimen/'.$this->user('0912038401')->id.'/lihat',
            '/v/'.Surat::whereNotNull('qr_token')->value('qr_token'), '/v/'.str_repeat('z', 43),
        ];
        foreach (['pengguna', 'prodi', 'jabatan', 'klasifikasi', 'seruan'] as $e) {
            $u[] = "/master/$e";
            $u[] = "/master/$e/buat";
        }
        $b = Pembukuan::create(['arah' => 'keluar', 'nomor' => '999/TES/2026', 'tgl_surat' => '2026-01-01', 'perihal' => 'Uji asap']);
        $u[] = "/pembukuan/{$b->id}/ubah";
        $u[] = '/pembukuan/ekspor';
        $u[] = '/pembukuan/impor/templat';
        $u[] = '/master/pengguna/impor/templat';
        $u[] = '/halaman/tidak/ada';

        return $u;
    }

    public function test_semua_halaman_dapat_dibuka_tanpa_galat_server_untuk_setiap_peran(): void
    {
        $halaman = array_merge($this->halamanTanpaParameter(), $this->halamanBerparameter());
        $this->assertGreaterThan(40, count($halaman));
        $galat = [];

        foreach (self::PERAN as $id) {
            $this->actingAs($this->user($id));
            foreach ($halaman as $url) {
                try {
                    $status = $this->get($url)->getStatusCode();
                } catch (\Throwable $e) {
                    $galat[] = "$id GET $url → ".get_class($e).': '.\Illuminate\Support\Str::limit($e->getMessage(), 140);

                    continue;
                }
                if ($status >= 500) {
                    $galat[] = "$id GET $url → $status";
                }
            }
        }

        $this->assertSame([], $galat, "Halaman yang galat:\n".implode("\n", $galat));
    }

    public function test_halaman_inti_terbuka_200_untuk_peran_yang_berhak(): void
    {
        $inti = [
            'TU' => ['/dasbor', '/pengajuan', '/surat-keluar', '/surat-keluar/buat', '/surat-masuk', '/pembukuan', '/format-surat', '/master/pengguna', '/master/spesimen',
                '/pengaturan/nomor', '/pengaturan/backup', '/pengaturan/log', '/notifikasi', '/profil'],
            '0912038401' => ['/dasbor', '/persetujuan', '/surat-masuk', '/surat-keluar', '/pembukuan', '/profil'],
            '0912048102' => ['/dasbor', '/persetujuan', '/pembukuan'],
            '0912058301' => ['/dasbor', '/pengajuan', '/persetujuan', '/surat-masuk'],
            '0912088704' => ['/dasbor', '/surat-keluar', '/surat-keluar/buat', '/profil'],
            '21650012' => ['/dasbor', '/layanan', '/layanan/lacak', '/layanan/riwayat', '/layanan/KET-AKTIF/ajukan', '/profil', '/notifikasi'],
        ];
        foreach ($inti as $id => $urls) {
            $this->actingAs($this->user($id));
            foreach ($urls as $url) {
                $this->get($url)->assertOk();
            }
        }
    }

    public function test_halaman_masuk_dan_halaman_galat_dapat_dirender(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/bantuan')->assertOk();
        $this->get('/up')->assertOk();
        $this->get('/tidak-ada-halaman-ini')->assertNotFound();
        $this->get('/dasbor')->assertRedirect('/login');                       // tamu diarahkan ke halaman masuk
        $this->actingAs($this->user('21650012'))->get('/pengaturan/nomor')->assertForbidden();   // mahasiswa dilarang
    }

    public function test_unduhan_berkas_menghasilkan_csv_dan_pdf(): void
    {
        $this->actingAs($this->user('TU'));
        $this->get('/pembukuan/ekspor')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->get('/pembukuan/impor/templat')->assertOk();
        $this->get('/master/pengguna/impor/templat')->assertOk();
        $this->get('/surat/'.Surat::whereNotNull('qr_token')->value('id').'/pdf')->assertOk();
    }
}
