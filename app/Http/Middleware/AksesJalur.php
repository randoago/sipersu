<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fase 1: aplikasi HANYA untuk jaringan lokal kampus. Tidak ada rute yang dibuka ke internet.
 * Diizinkan: IP privat (10/8, 172.16/12, 192.168/16), loopback, dan Tailscale (100.64/10, admin remote opsional).
 * IP lain ditolak 403. Daftar rentang diatur lewat config sipersu.jaringan_lokal (LAN_CIDRS).
 * Header proksi (X-Forwarded-For, CF-*) sengaja diabaikan: yang dinilai hanya alamat koneksi sebenarnya.
 */
class AksesJalur
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->server('REMOTE_ADDR') ?: ($request->ip() ?? '');

        if (! IpUtils::checkIp($ip, config('sipersu.jaringan_lokal'))) {
            Log::channel('single')->warning('Akses dari luar jaringan lokal ditolak', ['ip' => $ip, 'path' => $request->path()]);
            abort(403, 'Aplikasi ini hanya dapat diakses dari jaringan lokal fakultas.');
        }

        return $next($request);
    }
}
