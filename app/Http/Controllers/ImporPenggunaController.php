<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Models\Prodi;
use App\Services\ImporPengguna;
use App\Support\MasterData;
use Illuminate\Http\Request;
use RuntimeException;

class ImporPenggunaController extends Controller
{
    public function __construct(private ImporPengguna $impor)
    {
    }

    private function dasar(): array
    {
        return ['entitas' => 'pengguna', 'semua' => MasterData::semua()];
    }

    public function form()
    {
        return view('master.impor', $this->dasar() + [
            'kolom' => ImporPengguna::KOLOM, 'maks' => ImporPengguna::MAKS_BARIS,
            'prodi' => Prodi::orderBy('kode')->get(), 'peran' => Peran::cases(),
            'superAdmin' => auth()->user()->hasRole(Peran::SuperAdmin->value),
        ]);
    }

    public function templat()
    {
        return response($this->impor->templat(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="templat-impor-pengguna.csv"',
        ]);
    }

    /** Langkah 1: baca & periksa (belum menyimpan). */
    public function periksa(Request $request)
    {
        $request->validate(['berkas' => ['required', 'file', 'extensions:csv,txt', 'max:1024']], [
            'berkas.required' => 'Pilih berkas CSV.', 'berkas.extensions' => 'Berkas harus berekstensi .csv (atau .txt).', 'berkas.max' => 'Ukuran berkas maksimal 1 MB.',
        ]);
        try {
            $hasil = $this->impor->periksa((string) file_get_contents($request->file('berkas')->getRealPath()), $request->user(), $request->boolean('perbarui'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['berkas' => $e->getMessage()]);
        }

        // Hanya baris valid yang disimpan sementara di sesi; kata sandi dari CSV tidak ditampilkan kembali.
        $request->session()->put('impor_pengguna', ['waktu' => now()->timestamp, 'baris' => array_values(array_filter($hasil['baris'], fn ($b) => in_array($b['status'], ['baru', 'perbarui'], true)))]);

        return view('master.impor-periksa', $this->dasar() + $hasil + ['namaBerkas' => $request->file('berkas')->getClientOriginalName(), 'perbarui' => $request->boolean('perbarui')]);
    }

    /** Langkah 2: simpan baris valid. */
    public function proses(Request $request)
    {
        $tersimpan = $request->session()->pull('impor_pengguna');
        if (! $tersimpan || now()->timestamp - $tersimpan['waktu'] > 1800 || ! $tersimpan['baris']) {
            return redirect()->route('master.impor')->with('galat', 'Data impor sudah kedaluwarsa atau kosong. Unggah ulang berkas CSV.');
        }
        $hasil = $this->impor->proses($tersimpan['baris'], $request->user());
        $request->session()->flash('hasil_impor', $hasil);

        return redirect()->route('master.impor.hasil');
    }

    public function hasil(Request $request)
    {
        $hasil = $request->session()->get('hasil_impor');
        if ($hasil === null) {
            return redirect()->route('master.daftar', 'pengguna');
        }

        return view('master.impor-hasil', $this->dasar() + ['hasil' => $hasil]);
    }
}
