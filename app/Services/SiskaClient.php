<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien API integrasi SISKA (baca saja). URL dan kunci API disimpan di tabel pengaturan
 * (kunci dienkripsi dengan APP_KEY); diatur di Master Data → Koneksi SISKA.
 */
class SiskaClient
{
    public function url(): ?string
    {
        $u = trim((string) Pengaturan::ambil('siska_url'));

        return $u !== '' ? rtrim($u, '/') : null;
    }

    public function kunci(): ?string
    {
        $terenkripsi = Pengaturan::ambil('siska_kunci');
        if (! $terenkripsi) {
            return null;
        }
        try {
            return Crypt::decryptString($terenkripsi);
        } catch (\Throwable) {
            return null;                                     // APP_KEY berganti → kunci harus dimasukkan ulang
        }
    }

    public static function simpanKunci(string $kunci): void
    {
        Pengaturan::simpan('siska_kunci', Crypt::encryptString($kunci));
    }

    public function terkonfigurasi(): bool
    {
        return $this->url() !== null && $this->kunci() !== null;
    }

    /** @return array<int, array{id_prodi: string, kode_program_studi: ?string, nama_program_studi: string, jenjang: ?string, aktif: bool}> */
    public function prodi(): array
    {
        return $this->ambil('prodi')['data'] ?? [];
    }

    /**
     * @param  array<int,string>  $idProdi
     * @param  array<int,string>  $status
     * @return array{data: array<int, array>, meta: array{halaman: int, halaman_terakhir: int, total: int}}
     */
    public function mahasiswa(array $idProdi, array $status, int $halaman = 1, int $perHalaman = 200): array
    {
        return $this->ambil('mahasiswa', [
            'id_prodi' => implode(',', $idProdi), 'status' => implode(',', $status), 'page' => $halaman, 'per_page' => $perHalaman,
        ]);
    }

    private function ambil(string $jalur, array $query = []): array
    {
        if (! $this->terkonfigurasi()) {
            throw new RuntimeException('Koneksi SISKA belum diatur. Isi alamat dan kunci API di Master Data → Koneksi SISKA.');
        }
        try {
            // Tanpa redirect: Guzzle meneruskan header khusus (X-Api-Key) ke host tujuan pengalihan.
            $r = Http::withHeaders(['X-Api-Key' => $this->kunci()])->acceptJson()->withoutRedirecting()->connectTimeout(5)->timeout(60)
                ->get($this->url().'/api/integrasi/v1/'.$jalur, $query);
        } catch (ConnectionException) {
            throw new RuntimeException('Tidak dapat terhubung ke SISKA. Periksa alamat server dan pastikan SISKA berjalan.');
        }
        if ($r->redirect()) {
            throw new RuntimeException('SISKA mengalihkan permintaan (HTTP '.$r->status().'). Periksa alamat server, mis. gunakan https:// bila server memaksa HTTPS.');
        }
        if ($r->status() === 401) {
            throw new RuntimeException('SISKA menolak kunci API (tidak valid, dicabut, atau IP tidak diizinkan).');
        }
        if (! $r->successful() || ! is_array($r->json())) {
            throw new RuntimeException('SISKA mengembalikan galat (HTTP '.$r->status().').');
        }

        return $r->json();
    }
}
