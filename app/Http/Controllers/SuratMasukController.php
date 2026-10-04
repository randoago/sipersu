<?php

namespace App\Http\Controllers;

use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use App\Models\Surat;
use App\Services\SuratMasukService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuratMasukController extends Controller
{
    private const SIFAT = ['biasa' => 'Biasa', 'penting' => 'Penting', 'segera' => 'Segera', 'rahasia' => 'Rahasia'];

    public function __construct(private SuratMasukService $layanan)
    {
    }

    private function bolehLihat(Request $r): void
    {
        abort_unless($r->user()->adalahAdmin() || $r->user()->hasAnyRole(['dekan', 'wakil_dekan', 'kaprodi']), 403);
    }

    private function bolehCatat(Request $r): void
    {
        abort_unless($r->user()->adalahAdmin(), 403, 'Hanya Admin TU yang dapat mencatat surat masuk.');
    }

    public function index(Request $request)
    {
        $this->bolehLihat($request);
        $f = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'sifat' => ['nullable', Rule::in(array_keys(self::SIFAT))], 'jenis' => ['nullable', 'integer']]);

        $daftar = Surat::with('klasifikasi', 'jenis', 'lampiran')->where('arah', 'masuk')
            ->when(! $request->user()->adalahAdmin() && ! $request->user()->hasAnyRole(['dekan', 'wakil_dekan']), fn ($q) => $q->where('sifat', '!=', 'rahasia'))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('perihal', 'like', "%$t%")->orWhere('asal_tujuan', 'like', "%$t%")
                ->orWhere('no_agenda', 'like', "%$t%")->orWhereRaw("json_extract(data, '$.nomor_asal') like ?", ["%$t%"])))
            ->when($f['tahun'] ?? null, fn ($q, $y) => $q->whereYear('tgl_surat', $y))
            ->when($f['sifat'] ?? null, fn ($q, $s) => $q->where('sifat', $s))
            ->when($f['jenis'] ?? null, fn ($q, $j) => $q->where('jenis_surat_id', $j))
            ->latest('id')->paginate(15)->withQueryString();

        return view('surat-masuk.index', [
            'daftar' => $daftar, 'f' => $f, 'sifat' => self::SIFAT, 'formats' => JenisSurat::where('sasaran', 'masuk')->orderBy('nama')->get(),
            'bolehCatat' => $request->user()->adalahAdmin(), 'total' => Surat::where('arah', 'masuk')->count(),
            'tahunList' => Surat::where('arah', 'masuk')->whereNotNull('tgl_surat')->selectRaw("distinct strftime('%Y', tgl_surat) as y")->orderByDesc('y')->pluck('y'),
        ]);
    }

    public function pilih(Request $request)
    {
        $this->bolehCatat($request);

        return view('surat-masuk.pilih', ['formats' => JenisSurat::where('sasaran', 'masuk')->where('aktif', true)->orderBy('urutan')->orderBy('nama')->get()]);
    }

    public function isi(Request $request, JenisSurat $jenis)
    {
        $this->bolehCatat($request);
        abort_unless($jenis->aktif && $jenis->sasaran === 'masuk', 404);

        return $this->formulir($jenis, null);
    }

    public function simpan(Request $request, JenisSurat $jenis)
    {
        $this->bolehCatat($request);
        abort_unless($jenis->aktif && $jenis->sasaran === 'masuk', 404);
        $s = $this->layanan->catat($request->user(), $jenis, $this->validasi($request, $jenis), $request->file('scan'));

        return redirect()->route('surat-masuk.show', $s)->with('sukses', "Surat masuk dicatat. Nomor Agenda: {$s->no_agenda}");
    }

    public function show(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $this->authorize('view', $surat);
        $surat->load('klasifikasi', 'jenis', 'lampiran', 'pembuat');

        return view('surat-masuk.show', ['s' => $surat, 'sifat' => self::SIFAT, 'bolehUbah' => $request->user()->adalahAdmin()]);
    }

    public function ubah(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $this->bolehCatat($request);

        return $this->formulir($surat->jenis, $surat);
    }

    public function perbarui(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $this->bolehCatat($request);
        $s = $this->layanan->catat($request->user(), $surat->jenis, $this->validasi($request, $surat->jenis), $request->file('scan'), $surat);

        return redirect()->route('surat-masuk.show', $s)->with('sukses', 'Data surat masuk diperbarui. Nomor agenda tidak berubah.');
    }

    private function milik(Surat $s): void
    {
        abort_unless($s->arah === 'masuk', 404);
    }

    private function formulir(JenisSurat $jenis, ?Surat $s)
    {
        return view('surat-masuk.form', [
            'jenis' => $jenis, 's' => $s, 'sifat' => self::SIFAT,
            'klasifikasi' => KlasifikasiSurat::where('aktif', true)->orderBy('kode')->get(),
            'nilai' => $s?->data['isian_mentah'] ?? [],
        ]);
    }

    private function validasi(Request $request, JenisSurat $jenis): array
    {
        $d = $request->validate([
            'nomor_asal' => ['required', 'string', 'max:100'],
            'asal' => ['required', 'string', 'max:200'],
            'tgl_surat' => ['required', 'date', 'before_or_equal:today'],
            'tgl_diterima' => ['required', 'date', 'after_or_equal:tgl_surat', 'before_or_equal:today'],
            'perihal' => ['required', 'string', 'max:255'],
            'sifat' => ['required', Rule::in(array_keys(self::SIFAT))],
            'klasifikasi_id' => ['nullable', 'exists:klasifikasi_surat,id'],
            'lampiran' => ['nullable', 'string', 'max:100'],
            'scan' => ['nullable', 'file', 'mimes:'.implode(',', config('sipersu.lampiran.mimes')), 'max:'.config('sipersu.lampiran.maks_kb')],
        ] + $jenis->aturanIsian(), [
            'nomor_asal.required' => 'Nomor surat asal wajib diisi.', 'asal.required' => 'Asal surat (pengirim) wajib diisi.',
            'tgl_surat.required' => 'Tanggal surat wajib diisi.', 'tgl_surat.before_or_equal' => 'Tanggal surat tidak boleh di masa depan.',
            'tgl_diterima.after_or_equal' => 'Tanggal diterima tidak boleh sebelum tanggal surat.', 'tgl_diterima.before_or_equal' => 'Tanggal diterima tidak boleh di masa depan.',
            'perihal.required' => 'Perihal wajib diisi.', 'scan.mimes' => 'Pindaian harus berformat PDF, JPG, atau PNG.', 'scan.max' => 'Ukuran pindaian maksimal 2 MB.',
        ], $jenis->atributIsian() + ['tgl_diterima' => 'tanggal diterima', 'tgl_surat' => 'tanggal surat']);

        return $d;
    }
}
