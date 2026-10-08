<?php

namespace App\Http\Controllers;

use App\Models\Pembukuan;
use App\Services\PembukuanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Pembukuan surat: buku agenda surat masuk, keluar, dan lainnya berdasarkan nomor surat (manual atau impor CSV). */
class PembukuanController extends Controller
{
    public function __construct(private PembukuanService $buku)
    {
    }

    private function filter(Request $request): array
    {
        return $request->validate([
            'arah' => ['nullable', Rule::in(array_keys(PembukuanService::ARAH))], 'tahun' => ['nullable', 'integer', 'between:1990,2100'],
            'sumber' => ['nullable', Rule::in(['aplikasi', 'buku'])], 'q' => ['nullable', 'string', 'max:100'],
        ]);
    }

    public function index(Request $request)
    {
        $f = $this->filter($request);
        $daftar = $this->buku->query($f)->paginate(25)->withQueryString();
        $ringkas = $this->buku->query([])->reorder()->selectRaw('arah, count(*) as jumlah')->groupBy('arah')->pluck('jumlah', 'arah');

        return view('pembukuan.index', ['daftar' => $daftar, 'f' => $f, 'ringkas' => $ringkas, 'admin' => $request->user()->adalahAdmin(),
            'tahun' => $this->buku->query([])->reorder()->selectRaw("distinct strftime('%Y', tgl_surat) as t")->whereNotNull('tgl_surat')->orderByDesc('t')->pluck('t')]);
    }

    public function ekspor(Request $request)
    {
        $f = $this->filter($request);

        return response($this->buku->ekspor($f, ! $request->user()->adalahAdmin()), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="pembukuan-surat-'.now()->format('Ymd').'.csv"',
        ]);
    }

    // ---- catatan manual -----------------------------------------------------------------------------------

    public function buat()
    {
        return view('pembukuan.form', ['b' => null]);
    }

    public function ubah(Pembukuan $pembukuan)
    {
        return view('pembukuan.form', ['b' => $pembukuan]);
    }

    private function validasi(Request $request, ?Pembukuan $b): array
    {
        $d = $request->validate([
            'arah' => ['required', Rule::in(array_keys(PembukuanService::ARAH))],
            'nomor' => ['required', 'string', 'max:120', 'regex:/^[\p{L}\p{N}\/\.\-\(\)_, ]+$/u'],
            'tgl_surat' => ['required', 'date'], 'tgl_diterima' => ['nullable', 'date'], 'pihak' => ['nullable', 'string', 'max:255'],
            'perihal' => ['required', 'string', 'max:255'], 'lampiran' => ['nullable', 'string', 'max:120'], 'sifat' => ['required', Rule::in(['biasa', 'penting', 'segera', 'rahasia'])],
            'jenis' => ['nullable', 'string', 'max:80'], 'no_agenda' => ['nullable', 'string', 'max:40'], 'keterangan' => ['nullable', 'string', 'max:1000'],
        ], ['nomor.regex' => 'Nomor memuat karakter yang tidak diizinkan.', 'nomor.required' => 'Nomor surat wajib diisi.']);

        if ($this->buku->ada($d['arah'], $d['nomor'], $d['pihak'] ?? null, $d['tgl_surat'], $b?->id)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['nomor' => 'Nomor surat ini sudah ada di pembukuan/aplikasi.']);
        }

        return $d;
    }

    public function simpan(Request $request)
    {
        $d = $this->validasi($request, null);
        $this->buku->simpanManual($d, $request->user(), null, $request->boolean('sinkron', true));

        return redirect()->route('pembukuan.index')->with('sukses', 'Catatan pembukuan disimpan.');
    }

    public function perbarui(Request $request, Pembukuan $pembukuan)
    {
        $d = $this->validasi($request, $pembukuan);
        $this->buku->simpanManual($d, $request->user(), $pembukuan, $request->boolean('sinkron', true));

        return redirect()->route('pembukuan.index')->with('sukses', 'Catatan pembukuan diperbarui.');
    }

    public function hapus(Request $request, Pembukuan $pembukuan)
    {
        \App\Models\LogAktivitas::catat('pembukuan_hapus', "Menghapus catatan pembukuan {$pembukuan->arah} {$pembukuan->nomor}", null, [], $request->user()->id);
        $pembukuan->delete();

        return redirect()->route('pembukuan.index')->with('sukses', 'Catatan pembukuan dihapus.');
    }

    // ---- impor CSV ----------------------------------------------------------------------------------------

    public function impor()
    {
        return view('pembukuan.impor', ['kolom' => PembukuanService::KOLOM, 'maks' => PembukuanService::MAKS_BARIS]);
    }

    public function templat()
    {
        return response($this->buku->templat(), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="templat-impor-pembukuan.csv"']);
    }

    public function periksa(Request $request)
    {
        $request->validate(['berkas' => ['required', 'file', 'extensions:csv,txt', 'max:2048']], [
            'berkas.required' => 'Pilih berkas CSV.', 'berkas.extensions' => 'Berkas harus berekstensi .csv (atau .txt).', 'berkas.max' => 'Ukuran berkas maksimal 2 MB.',
        ]);
        try {
            $hasil = $this->buku->periksa((string) file_get_contents($request->file('berkas')->getRealPath()));
        } catch (RuntimeException $e) {
            return back()->withErrors(['berkas' => $e->getMessage()]);
        }
        $request->session()->put('impor_pembukuan', ['waktu' => now()->timestamp, 'baris' => array_values(array_filter($hasil['baris'], fn ($b) => $b['status'] === 'baru'))]);

        return view('pembukuan.impor-periksa', $hasil + ['namaBerkas' => $request->file('berkas')->getClientOriginalName()]);
    }

    public function proses(Request $request)
    {
        $tersimpan = $request->session()->pull('impor_pembukuan');
        if (! $tersimpan || now()->timestamp - $tersimpan['waktu'] > 1800 || ! $tersimpan['baris']) {
            return redirect()->route('pembukuan.impor')->with('galat', 'Data impor sudah kedaluwarsa atau kosong. Unggah ulang berkas CSV.');
        }
        $hasil = $this->buku->proses($tersimpan['baris'], $request->user(), $request->boolean('sinkron'));
        $request->session()->flash('hasil_impor_pembukuan', $hasil);

        return redirect()->route('pembukuan.impor.hasil');
    }

    public function hasil(Request $request)
    {
        $hasil = $request->session()->get('hasil_impor_pembukuan');
        if ($hasil === null) {
            return redirect()->route('pembukuan.index');
        }

        return view('pembukuan.impor-hasil', ['hasil' => $hasil]);
    }
}
