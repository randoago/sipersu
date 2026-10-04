<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Services\SpesimenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function tampil(Request $request)
    {
        $u = $request->user()->load('prodi', 'roles');

        return view('profil.show', ['u' => $u, 'pejabat' => $u->jabatanAktif()->exists() || $u->hasAnyRole(['dekan', 'wakil_dekan', 'kaprodi'])]);
    }

    public function kataSandi(Request $request)
    {
        $data = $request->validate([
            'password_lama' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.', 'password_lama.current_password' => 'Kata sandi lama salah.']);

        $request->user()->update(['password' => $data['password']]);
        LogAktivitas::catat('ganti_sandi', 'Mengganti kata sandi');

        return back()->with('sukses', 'Kata sandi berhasil diganti.');
    }

    /** Unggah spesimen sendiri: jenis "ttd" (tanda tangan saja) atau "stempel" (tanda tangan + stempel, dipakai surat ber-QR). */
    public function spesimen(Request $request, SpesimenService $layanan)
    {
        abort_unless(SpesimenService::adalahPejabat($request->user()), 403);
        $request->validate(['jenis' => ['nullable', 'in:ttd,stempel'], 'spesimen' => SpesimenService::ATURAN], SpesimenService::PESAN);
        $layanan->simpan($request->user(), $request->input('jenis'), $request->file('spesimen'), $request->user());

        return back()->with('sukses', 'Spesimen disimpan.');
    }

    public function lihatSpesimen(Request $request)
    {
        $u = $request->user();
        $path = $request->query('jenis') === 'stempel' ? $u->spesimen_stempel : $u->spesimen_ttd;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=60']);
    }
}
