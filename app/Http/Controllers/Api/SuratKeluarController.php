<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuratResource;
use App\Models\Surat;
use App\Services\AlurSuratKeluar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Surat keluar buatan staf (bukan surat hasil pengajuan mahasiswa; itu lewat /pengajuan). Aturan lihat sama dengan aplikasi web. */
class SuratKeluarController extends Controller
{
    private const STATUS = ['draf', 'menunggu_paraf', 'menunggu_ttd', 'ditandatangani', 'batal'];

    public function __construct(private AlurSuratKeluar $alur)
    {
    }

    public function index(Request $request)
    {
        $u = $request->user();
        abort_unless($this->alur->bolehMembuat($u), 403);
        $f = $request->validate([
            'status' => ['nullable', Rule::in(self::STATUS)], 'q' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $daftar = Surat::with('klasifikasi', 'jenis', 'pembuat')->where('arah', 'keluar')->whereNull('pengajuan_id')
            ->when(! $u->adalahAdmin() && ! $u->hasAnyRole(['dekan', 'wakil_dekan']), fn ($q) => $q->where(fn ($w) => $w->where('dibuat_oleh', $u->id)
                ->orWhereHas('jabatan', fn ($j) => $j->where('user_id', $u->id))))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('perihal', 'like', "%$t%")->orWhere('nomor', 'like', "%$t%")->orWhere('asal_tujuan', 'like', "%$t%")))
            ->latest('id')->paginate($f['per_page'] ?? 15)->withQueryString();

        return SuratResource::collection($daftar);
    }

    public function show(Request $request, Surat $surat)
    {
        abort_unless($surat->arah === 'keluar' && $surat->pengajuan_id === null, 404);
        abort_unless($this->alur->bolehMelihat($surat, $request->user()), 403, 'Anda tidak berhak melihat surat ini.');

        return new SuratResource($surat->load('klasifikasi', 'jenis', 'pembuat', 'lampiran', 'persetujuan.user'));
    }
}
