<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Token API ikut tidak berlaku begitu akun dinonaktifkan (Master Data → Pengguna); token dihapus. */
class AkunApiAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->aktif) {
            $user->tokens()->delete();

            return response()->json(['message' => 'Akun tidak aktif.'], 401);
        }

        return $next($request);
    }
}
