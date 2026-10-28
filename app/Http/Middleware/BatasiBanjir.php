<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Perlindungan banjir akses (flood): membatasi jumlah permintaan per alamat IP per menit, SEBELUM sesi dan basis data disentuh.
 * Menyimpan hitungan di cache berkas (bukan SQLite) supaya banjir permintaan tidak membuat basis data terkunci.
 *  - lewat batas per menit      → 429 sampai menit berikutnya
 *  - lewat 2× batas dalam 1 menit → IP diblokir sementara (blokir_detik), 429 tanpa memproses apa pun
 * Pengguna normal (satu halaman ≈ beberapa permintaan) tidak akan mendekati batas.
 * Pengaturan: config/sipersu.php → batas_laju (env LAJU_AKTIF, LAJU_PER_MENIT, LAJU_BLOKIR_DETIK).
 */
class BatasiBanjir
{
    public function handle(Request $request, Closure $next): Response
    {
        $c = config('sipersu.batas_laju');
        if (! $c['aktif'] || $request->is('up')) {
            return $next($request);
        }

        $ip = $request->server('REMOTE_ADDR') ?: ($request->ip() ?? '0.0.0.0');
        $cache = Cache::store($c['store']);

        if ($cache->has("banjir:blokir:$ip")) {
            $this->tolak($c['blokir_detik'], 'Terlalu banyak permintaan dari alamat Anda. Akses dihentikan sementara.');
        }

        $jendela = intdiv(time(), 60);
        $kunci = "banjir:hitung:$ip:$jendela";
        $cache->add($kunci, 0, 120);
        $n = (int) $cache->increment($kunci);

        if ($n > $c['per_menit'] * 2) {
            $cache->put("banjir:blokir:$ip", 1, $c['blokir_detik']);
            Log::channel('single')->warning('Banjir akses: IP diblokir sementara', ['ip' => $ip, 'permintaan_semenit' => $n, 'path' => $request->path()]);
            $this->tolak($c['blokir_detik'], 'Terlalu banyak permintaan dari alamat Anda. Akses dihentikan sementara.');
        }
        if ($n > $c['per_menit']) {
            if ($n === $c['per_menit'] + 1) {
                Log::channel('single')->warning('Batas permintaan per menit terlampaui', ['ip' => $ip, 'path' => $request->path()]);
            }
            $this->tolak(60 - (time() % 60), 'Terlalu banyak permintaan. Coba lagi sebentar.');
        }

        return $next($request);
    }

    private function tolak(int $detik, string $pesan): never
    {
        abort(429, $pesan, ['Retry-After' => max(1, $detik)]);
    }
}
