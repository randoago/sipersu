<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Services\KunciTte;
use App\Services\TandaTanganService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Memeriksa keaslian surat ber-QR berdasarkan token pada halaman verifikasi (/v/{token}); tanda tangan diverifikasi ulang di server. */
class VerifikasiSuratController extends Controller
{
    public function show(Request $request, string $token): JsonResponse
    {
        $s = strlen($token) >= 32 ? Surat::with('klasifikasi')->where('qr_token', $token)->first() : null;
        if (! $s) {
            LogAktivitas::catat('verifikasi_api_gagal', 'Token verifikasi (API) tidak dikenal', null, ['token' => substr($token, 0, 12)], $request->user()->id);

            return response()->json(['message' => 'Surat dengan token itu tidak ditemukan.'], 404);
        }

        $sah = (bool) ($s->signature && KunciTte::verifikasi(app(TandaTanganService::class)->payload($s), $s->signature));
        LogAktivitas::catat('verifikasi_api', "Memeriksa surat {$s->nomor} lewat API", $s, ['sah' => $sah], $request->user()->id);

        return response()->json(['data' => [
            'tanda_tangan_sah' => $sah,
            'berlaku' => $sah && $s->status !== 'batal',
            'status' => $s->status,
            'nomor' => $s->nomor,
            'perihal' => $s->rahasia() ? null : $s->perihal,
            'tgl_surat' => $s->tgl_surat?->toDateString(),
            'penandatangan' => ['nama' => $s->penandatangan_nama, 'jabatan' => $s->penandatangan_jabatan],
            'ditandatangani_pada' => $s->ditandatangani_pada?->toIso8601String(),
            'dibatalkan_pada' => $s->dibatalkan_pada?->toIso8601String(),
            'alasan_batal' => $s->rahasia() ? null : $s->alasan_batal,
            'pdf_sha256' => $s->pdf_hash,
        ]]);
    }
}
