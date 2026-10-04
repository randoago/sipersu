<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
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

    public function spesimen(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['dekan', 'wakil_dekan', 'kaprodi']) || $request->user()->jabatanAktif()->exists(), 403);
        $request->validate(['spesimen' => ['required', 'file', 'mimes:png,jpg,jpeg', 'max:2048', 'dimensions:min_width=100,min_height=50']], [
            'spesimen.mimes' => 'Spesimen harus berformat PNG atau JPG.',
            'spesimen.max' => 'Ukuran spesimen maksimal 2 MB.',
            'spesimen.dimensions' => 'Gambar terlalu kecil (minimal 100×50 piksel).',
        ]);
        $u = $request->user();
        if ($u->spesimen_ttd) {
            Storage::disk('local')->delete($u->spesimen_ttd);
        }
        $ext = strtolower($request->file('spesimen')->guessExtension() ?: 'png');
        $path = $request->file('spesimen')->storeAs('spesimen', 'user-'.$u->id.'-'.bin2hex(random_bytes(4)).'.'.$ext, 'local');
        $u->update(['spesimen_ttd' => $path]);
        LogAktivitas::catat('spesimen_ttd', 'Mengunggah spesimen tanda tangan');

        return back()->with('sukses', 'Spesimen tanda tangan disimpan.');
    }

    public function lihatSpesimen(Request $request)
    {
        $path = $request->user()->spesimen_ttd;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=60']);
    }
}
