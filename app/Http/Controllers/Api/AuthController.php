<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/** Token pribadi (Sanctum): masuk dengan NPM/NIDN/username + kata sandi seperti di aplikasi web. */
class AuthController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $d = $request->validate([
            'nomor_induk' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:200'],
            'nama_perangkat' => ['nullable', 'string', 'max:60'],
        ]);

        $identitas = trim($d['nomor_induk']);
        $kunci = 'api|'.Str::lower($identitas).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $detik = RateLimiter::availableIn($kunci);

            return response()->json(['message' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$detik} detik."], 429, ['Retry-After' => $detik]);
        }

        $akun = User::where('nomor_induk', $identitas)->orWhereRaw('lower(username) = ?', [Str::lower($identitas)])->first();
        if (! $akun || ! $akun->aktif || ! Hash::check($d['password'], $akun->password)) {
            RateLimiter::hit($kunci, 60);
            LogAktivitas::catat('api_login_gagal', 'Percobaan token API gagal', null, ['nomor_induk' => $identitas], null);

            return response()->json(['message' => 'NPM/NIDN/username atau kata sandi salah, atau akun tidak aktif.'], 401);
        }

        RateLimiter::clear($kunci);
        $nama = $d['nama_perangkat'] ?? 'api';
        $akun->tokens()->where('name', $nama)->delete();   // satu token per nama perangkat
        $baru = $akun->createToken($nama, ['baca']);
        LogAktivitas::catat('api_token_dibuat', "Membuat token API \"{$nama}\"", $akun, [], $akun->id);

        $menit = (int) config('sanctum.expiration');

        return response()->json([
            'token' => $baru->plainTextToken,
            'token_type' => 'Bearer',
            'kedaluwarsa_pada' => $menit > 0 ? now()->addMinutes($menit)->toIso8601String() : null,
            'pengguna' => $this->ringkas($akun),
        ], 201);
    }

    public function cabut(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        LogAktivitas::catat('api_token_dicabut', 'Mencabut token API', $request->user(), [], $request->user()->id);

        return response()->json(null, 204);
    }

    public function saya(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->ringkas($request->user())]);
    }

    private function ringkas(User $u): array
    {
        $u->loadMissing('prodi');

        return [
            'id' => $u->id,
            'nomor_induk' => $u->nomor_induk,
            'nama' => $u->nama,
            'nama_lengkap' => $u->namaLengkap(),
            'peran' => $u->getRoleNames()->values(),
            'prodi' => $u->prodi ? ['kode' => $u->prodi->kode, 'nama' => $u->prodi->nama] : null,
        ];
    }
}
