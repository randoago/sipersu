<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SpesimenService;
use App\Support\MasterData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Admin TU / Super Admin mengelola spesimen tanda tangan pejabat (Dekan, Wakil Dekan, Kaprodi, dst.). */
class SpesimenController extends Controller
{
    public function __construct(private SpesimenService $layanan)
    {
    }

    public function index()
    {
        $pejabat = User::with(['prodi', 'roles', 'jabatanAktif'])
            ->where('aktif', true)
            ->where(fn ($q) => $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['dekan', 'wakil_dekan', 'kaprodi']))->orWhereHas('jabatanAktif'))
            ->get()
            ->sortBy(fn ($u) => [$u->hasRole('dekan') ? 0 : ($u->hasRole('wakil_dekan') ? 1 : 2), $u->nama])
            ->values();

        return view('master.spesimen', ['entitas' => 'spesimen', 'semua' => MasterData::semua(), 'pejabat' => $pejabat]);
    }

    public function simpan(Request $request, User $user)
    {
        abort_unless(SpesimenService::adalahPejabat($user), 404);
        $request->validate(['jenis' => ['nullable', 'in:ttd,stempel'], 'spesimen' => SpesimenService::ATURAN], SpesimenService::PESAN);
        $this->layanan->simpan($user, $request->input('jenis'), $request->file('spesimen'), $request->user());

        return back()->with('sukses', 'Spesimen '.SpesimenService::label($request->input('jenis'))." {$user->namaLengkap()} disimpan.");
    }

    public function hapus(Request $request, User $user, string $jenis)
    {
        abort_unless(SpesimenService::adalahPejabat($user) && in_array($jenis, ['ttd', 'stempel'], true), 404);
        $this->layanan->hapus($user, $jenis, $request->user());

        return back()->with('sukses', 'Spesimen dihapus.');
    }

    public function lihat(Request $request, User $user)
    {
        abort_unless(SpesimenService::adalahPejabat($user), 404);
        $path = $user->{SpesimenService::kolom($request->query('jenis'))};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=60']);
    }
}
