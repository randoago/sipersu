<?php

namespace App\Http\Controllers\Api;

use App\Enums\Peran;
use App\Enums\StatusPengajuan as S;
use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanResource;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pengajuan surat: pemohon melihat miliknya; Admin/Dekan/Wakil Dekan semua; Kaprodi miliknya + prodinya (sama dengan PengajuanPolicy). */
class PengajuanController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $f = $request->validate([
            'status' => ['nullable', Rule::enum(S::class)], 'q' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $semua = $u->adalahAdmin() || $u->hasAnyRole([Peran::Dekan->value, Peran::WakilDekan->value]);
        $daftar = Pengajuan::with('jenis', 'pemohon.prodi', 'surat')
            ->when(! $semua, fn ($q) => $q->where(fn ($w) => $w->where('user_id', $u->id)
                ->when($u->hasRole(Peran::Kaprodi->value) && $u->prodi_id, fn ($k) => $k->orWhereHas('pemohon', fn ($p) => $p->where('prodi_id', $u->prodi_id)))))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('kode', 'like', "%$t%")
                ->orWhereHas('pemohon', fn ($p) => $p->where('nama', 'like', "%$t%")->orWhere('nomor_induk', 'like', "%$t%"))))
            ->latest('id')->paginate($f['per_page'] ?? 15)->withQueryString();

        return PengajuanResource::collection($daftar);
    }

    public function show(Request $request, Pengajuan $pengajuan)
    {
        abort_unless($request->user()->can('view', $pengajuan), 403, 'Anda tidak berhak melihat pengajuan ini.');

        return new PengajuanResource($pengajuan->load('jenis', 'pemohon.prodi', 'surat', 'persetujuan.user', 'lampiran'));
    }
}
