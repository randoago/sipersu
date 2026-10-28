<?php

namespace App\Http\Controllers\Api;

use App\Enums\Peran;
use App\Http\Controllers\Controller;
use App\Http\Resources\JenisSuratResource;
use App\Models\JenisSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Katalog format surat yang aktif. Mahasiswa hanya melihat format untuk mahasiswa. */
class JenisSuratController extends Controller
{
    private function hanyaMahasiswa(Request $r): bool
    {
        return $r->user()->hasRole(Peran::Mahasiswa->value) && $r->user()->roles->count() === 1;
    }

    public function index(Request $request)
    {
        $f = $request->validate(['sasaran' => ['nullable', Rule::in(['mahasiswa', 'staf', 'masuk'])]]);
        $sasaran = $this->hanyaMahasiswa($request) ? 'mahasiswa' : ($f['sasaran'] ?? null);

        return JenisSuratResource::collection(
            JenisSurat::with('klasifikasi')->where('aktif', true)->when($sasaran, fn ($q, $s) => $q->where('sasaran', $s))->orderBy('sasaran')->orderBy('urutan')->orderBy('nama')->get()
        );
    }

    public function show(Request $request, JenisSurat $jenis)
    {
        abort_unless($jenis->aktif && (! $this->hanyaMahasiswa($request) || $jenis->sasaran === 'mahasiswa'), 404);

        return new JenisSuratResource($jenis->load('klasifikasi'));
    }
}
