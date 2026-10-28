<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuratResource;
use App\Models\Surat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Surat masuk: Admin, Dekan, Wakil Dekan, Kaprodi. Surat rahasia hanya untuk Admin, Dekan, Wakil Dekan (SuratPolicy). */
class SuratMasukController extends Controller
{
    private function bolehLihat(Request $r): void
    {
        abort_unless($r->user()->adalahAdmin() || $r->user()->hasAnyRole(['dekan', 'wakil_dekan', 'kaprodi']), 403);
    }

    public function index(Request $request)
    {
        $this->bolehLihat($request);
        $u = $request->user();
        $f = $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'sifat' => ['nullable', Rule::in(['biasa', 'penting', 'segera', 'rahasia'])], 'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $daftar = Surat::with('klasifikasi', 'jenis')->where('arah', 'masuk')
            ->when(! $u->adalahAdmin() && ! $u->hasAnyRole(['dekan', 'wakil_dekan']), fn ($q) => $q->where('sifat', '!=', 'rahasia'))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('perihal', 'like', "%$t%")->orWhere('asal_tujuan', 'like', "%$t%")
                ->orWhere('no_agenda', 'like', "%$t%")->orWhereRaw("json_extract(data, '$.nomor_asal') like ?", ["%$t%"])))
            ->when($f['tahun'] ?? null, fn ($q, $y) => $q->whereYear('tgl_surat', $y))
            ->when($f['sifat'] ?? null, fn ($q, $s) => $q->where('sifat', $s))
            ->latest('id')->paginate($f['per_page'] ?? 15)->withQueryString();

        return SuratResource::collection($daftar);
    }

    public function show(Request $request, Surat $surat)
    {
        $this->bolehLihat($request);
        abort_unless($surat->arah === 'masuk', 404);
        abort_unless($request->user()->can('view', $surat), 403, 'Anda tidak berhak melihat surat ini.');

        return new SuratResource($surat->load('klasifikasi', 'jenis', 'lampiran'));
    }
}
