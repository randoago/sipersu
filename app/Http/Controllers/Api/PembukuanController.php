<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PembukuanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Buku agenda: Admin, Dekan, Wakil Dekan. Perihal surat rahasia disamarkan selain untuk Admin (sama dengan ekspor CSV). */
class PembukuanController extends Controller
{
    public function __construct(private PembukuanService $buku)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $u = $request->user();
        abort_unless($u->adalahAdmin() || $u->hasAnyRole(['dekan', 'wakil_dekan']), 403);
        $f = $request->validate([
            'arah' => ['nullable', Rule::in(array_keys(PembukuanService::ARAH))], 'tahun' => ['nullable', 'integer', 'between:1990,2100'],
            'sumber' => ['nullable', Rule::in(['aplikasi', 'buku'])], 'q' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $halaman = $this->buku->query($f)->paginate($f['per_page'] ?? 25)->withQueryString();
        $samarkan = ! $u->adalahAdmin();
        $data = $halaman->getCollection()->map(fn ($r) => [
            'sumber' => $r->sumber === 'aplikasi' ? 'aplikasi' : 'pembukuan', 'arah' => $r->arah, 'nomor' => $r->nomor, 'no_agenda' => $r->no_agenda,
            'tgl_surat' => $r->tgl_surat ? substr($r->tgl_surat, 0, 10) : null, 'tgl_diterima' => $r->tgl_diterima ? substr($r->tgl_diterima, 0, 10) : null, 'pihak' => $r->pihak,
            'perihal' => $samarkan && $r->sifat === 'rahasia' ? '(rahasia)' : $r->perihal, 'sifat' => $r->sifat, 'jenis' => $r->jenis, 'keterangan' => $r->keterangan,
        ])->values();

        return response()->json([
            'data' => $data,
            'meta' => ['halaman' => $halaman->currentPage(), 'per_page' => $halaman->perPage(), 'total' => $halaman->total(), 'halaman_terakhir' => $halaman->lastPage()],
        ]);
    }
}
