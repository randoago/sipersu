<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemisahan akses:
 *  - Lewat Cloudflare Tunnel (ada header CF-* atau Host = host APP_PUBLIC_URL): HANYA /v/* (+ aset statis).
 *  - Langsung ke server: hanya IP jaringan lokal (config sipersu.jaringan_lokal).
 * cloudflared terhubung dari 127.0.0.1, jadi IP sumber saja TIDAK cukup untuk membedakan lalu lintas internet.
 */
class HanyaJaringanLokal
{
    private const JALUR_PUBLIK = ['v/*', 'css/*', 'fonts/*', 'images/*', 'js/*', 'favicon.ico', 'robots.txt'];

    public function handle(Request $request, Closure $next): Response
    {
        $lewatTunnel = $request->hasHeader('CF-Connecting-IP') || $request->hasHeader('CF-Ray') || $this->hostPublik($request);

        if ($lewatTunnel) {
            if (! $request->is(...self::JALUR_PUBLIK)) {
                Log::channel('single')->warning('Akses publik ke rute non-publik ditolak', ['path' => $request->path(), 'ip' => $request->header('CF-Connecting-IP')]);
                abort(404);
            }

            return $next($request);
        }

        if (! IpUtils::checkIp($request->ip() ?? '', config('sipersu.jaringan_lokal'))) {
            abort(403, 'Aplikasi ini hanya dapat diakses dari jaringan lokal fakultas.');
        }

        return $next($request);
    }

    private function hostPublik(Request $request): bool
    {
        $publik = parse_url((string) config('app.public_url'), PHP_URL_HOST);
        $lokal = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $publik && $publik !== $lokal && strcasecmp($request->getHost(), $publik) === 0;
    }
}
