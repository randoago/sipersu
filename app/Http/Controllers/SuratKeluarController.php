<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\KlasifikasiSurat;
use App\Models\Surat;
use App\Services\AlurSuratKeluar;
use App\Services\PenyusunSurat;
use App\Support\NomorManual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratKeluarController extends Controller
{
    private const STATUS = ['draf' => 'Draf', 'menunggu_paraf' => 'Menunggu Paraf', 'menunggu_ttd' => 'Menunggu TTD', 'ditandatangani' => 'Ditandatangani', 'batal' => 'Dibatalkan'];

    public function __construct(private AlurSuratKeluar $alur)
    {
    }

    public function index(Request $request)
    {
        $u = $request->user();
        abort_unless($this->alur->bolehMembuat($u), 403);
        $f = $request->validate(['status' => ['nullable', Rule::in(array_keys(self::STATUS))], 'q' => ['nullable', 'string', 'max:100']]);

        $daftar = Surat::with('klasifikasi', 'jabatan.pejabat', 'pembuat')->where('arah', 'keluar')->whereNull('pengajuan_id')
            ->when(! $u->adalahAdmin() && ! $u->hasAnyRole(['dekan', 'wakil_dekan']), fn ($q) => $q->where(fn ($w) => $w->where('dibuat_oleh', $u->id)->orWhereHas('jabatan', fn ($j) => $j->where('user_id', $u->id))))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('perihal', 'like', "%$t%")->orWhere('nomor', 'like', "%$t%")->orWhere('asal_tujuan', 'like', "%$t%")))
            ->latest()->paginate(12)->withQueryString();

        return view('surat-keluar.index', ['daftar' => $daftar, 'f' => $f, 'statusList' => self::STATUS]);
    }

    private function bentuk(Request $request): ?string
    {
        return in_array($request->query('bentuk'), ['qr', 'basah'], true) ? $request->query('bentuk') : null;
    }

    /** Langkah 1: pilih bentuk surat (ber-QR / tanpa QR); langkah 2: semua format surat ditampilkan untuk bentuk itu. */
    public function buat(Request $request)
    {
        abort_unless($this->alur->bolehMembuat($request->user()), 403);

        return view('surat-keluar.pilih', ['bentuk' => $this->bentuk($request), 'formats' => JenisSurat::untukStaf()->where('aktif', true)->with('penandatanganJabatan.pejabat', 'klasifikasi')->orderBy('urutan')->orderBy('nama')->get()]);
    }

    public function bebas(Request $request)
    {
        abort_unless($this->alur->bolehMembuat($request->user()), 403);

        return view('surat-keluar.form', $this->dataForm(null));
    }

    /** Langkah 2: formulir isian dari format. */
    public function isi(Request $request, JenisSurat $jenis)
    {
        abort_unless($this->alur->bolehMembuat($request->user()) && $jenis->aktif && $jenis->adalahUntukStaf(), 404);

        return view('surat-keluar.isi', ['jenis' => $jenis->load('penandatanganJabatan.pejabat', 'klasifikasi'), 's' => null, 'nilai' => [], 'bentuk' => $this->bentuk($request) ?? $jenis->mode_ttd]);
    }

    public function simpanFormat(Request $request, JenisSurat $jenis)
    {
        abort_unless($this->alur->bolehMembuat($request->user()) && $jenis->aktif && $jenis->adalahUntukStaf(), 404);
        $data = $request->validate($jenis->aturanIsian() + $this->aturanTanggal() + ['mode_ttd' => ['nullable', Rule::in(['qr', 'basah'])]] + $this->aturanNomor($request, null, $jenis->klasifikasi_id), $this->pesanTanggal() + NomorManual::PESAN, $jenis->atributIsian() + ['tanggal_surat' => 'tanggal surat', 'nomor_manual' => 'nomor urut surat']);
        $s = $this->alur->simpanDariFormat($request->user(), $jenis, $data['isian'] ?? [], null, $data['tanggal_surat'] ?? null, $data['mode_ttd'] ?? null);
        $this->simpanNomor($request, $s);

        return redirect()->route('surat-keluar.show', $s)->with('sukses', 'Draf surat disimpan. Periksa pratinjau, lalu '.($s->langsungTerbit() ? 'terbitkan (tanpa QR tidak perlu persetujuan).' : 'ajukan untuk '.(($s->data['paraf_role'] ?? null) ? 'paraf' : 'tanda tangan').'.'));
    }

    public function simpan(Request $request)
    {
        abort_unless($this->alur->bolehMembuat($request->user()), 403);
        $s = $this->alur->simpan($request->user(), $this->validasi($request));
        $this->simpanNomor($request, $s);

        return redirect()->route('surat-keluar.show', $s)->with('sukses', 'Draf surat disimpan. Periksa pratinjau, lalu ajukan untuk '.(($s->data['paraf_role'] ?? null) ? 'paraf' : 'tanda tangan').'.');
    }

    /** Pratinjau tampilan surat dari isian formulir yang belum disimpan (format TU atau surat bebas). */
    public function pratinjau(Request $request, PenyusunSurat $penyusun)
    {
        abort_unless($this->alur->bolehMembuat($request->user()), 403);

        if ($request->filled('format')) {
            $jenis = JenisSurat::where('kode', $request->input('format'))->where('sasaran', 'staf')->with('penandatanganJabatan.pejabat')->firstOrFail();
            $isian = $penyusun->isianPratinjau($jenis->field_formulir, (array) $request->input('isian', []));
            $h = $penyusun->dariFormat($jenis, $isian, $request->user());
            $html = $penyusun->htmlPratinjau($h['isi'], $jenis->judul_surat, $jenis->penandatanganJabatan, in_array($request->input('mode_ttd'), ['qr', 'basah'], true) ? $request->input('mode_ttd') : ($jenis->mode_ttd ?? 'qr'), $h['perihal'], $jenis->gaya_tanggal ?? 'dikeluarkan', $this->tanggalPratinjau($request));
        } else {
            $isi = $penyusun->suratUmum([
                'lampiran' => trim((string) $request->input('lampiran')) ?: '-', 'perihal' => trim((string) $request->input('perihal')) ?: '[Perihal]',
                'tujuan' => trim((string) $request->input('tujuan')) ?: '[Tujuan surat]', 'isi' => trim((string) $request->input('isi')) ?: '[Isi surat]',
                'salam' => $request->boolean('salam'),
            ]);
            $jabatan = Jabatan::with('pejabat')->find($request->input('jabatan_id'));
            $html = $penyusun->htmlPratinjau($isi, null, $jabatan, in_array($request->input('mode_ttd'), ['qr', 'basah'], true) ? $request->input('mode_ttd') : 'qr', '', 'dikeluarkan', $this->tanggalPratinjau($request));
        }

        return response()->json(['html' => $html]);
    }

    public function ubah(Request $request, Surat $surat)
    {
        $this->milik($surat);
        abort_unless($this->alur->bolehMengubah($surat, $request->user()), 403, 'Surat hanya dapat diubah saat berstatus draf.');
        if ($surat->jenis_surat_id) {
            return view('surat-keluar.isi', ['jenis' => $surat->jenis->load('penandatanganJabatan.pejabat', 'klasifikasi'), 's' => $surat, 'nilai' => $surat->data['isian_mentah'] ?? [], 'bentuk' => $surat->mode_ttd]);
        }

        return view('surat-keluar.form', $this->dataForm($surat));
    }

    public function perbarui(Request $request, Surat $surat)
    {
        $this->milik($surat);
        abort_unless($this->alur->bolehMengubah($surat, $request->user()), 403);
        if ($surat->jenis_surat_id) {
            $jenis = $surat->jenis;
            $data = $request->validate($jenis->aturanIsian() + $this->aturanTanggal() + $this->aturanNomor($request, $surat->id, $surat->klasifikasi_id), $this->pesanTanggal() + NomorManual::PESAN, $jenis->atributIsian() + ['tanggal_surat' => 'tanggal surat', 'nomor_manual' => 'nomor urut surat']);
            $this->alur->simpanDariFormat($request->user(), $jenis, $data['isian'] ?? [], $surat, $data['tanggal_surat'] ?? null);
        } else {
            $this->alur->simpan($request->user(), $this->validasi($request, $surat->id), $surat);
        }
        $this->simpanNomor($request, $surat);

        return redirect()->route('surat-keluar.show', $surat)->with('sukses', 'Draf diperbarui.');
    }

    public function show(Request $request, Surat $surat, PenyusunSurat $penyusun)
    {
        $this->milik($surat);
        abort_unless($this->alur->bolehMelihat($surat, $request->user()), 403);
        $surat->load('klasifikasi', 'jabatan.pejabat', 'pembuat', 'persetujuan.user', 'penandatangan');
        $u = $request->user();

        $qr = null;
        if ($surat->pakaiQr() && in_array($surat->status, ['ditandatangani', 'batal'], true)) {
            $tte = app(\App\Services\TandaTanganService::class);
            $qr = $tte->svgQr($tte->urlQr($surat));
        }

        return view('surat-keluar.show', [
            's' => $surat, 'dokumen' => $penyusun->dataDokumen($surat, false, $qr), 'statusList' => self::STATUS,
            'izin' => [
                'ubah' => $this->alur->bolehMengubah($surat, $u), 'paraf' => $this->alur->bolehParaf($surat, $u),
                'ttd' => $this->alur->bolehTandatangan($surat, $u), 'batal' => $this->alur->bolehBatal($surat, $u),
            ],
        ]);
    }

    public function ajukan(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $hasil = $this->alur->ajukan($surat, $request->user());

        return redirect()->route('surat-keluar.show', $surat)->with('sukses', $hasil->langsungTerbit()
            ? "Surat diterbitkan tanpa persetujuan dengan nomor {$hasil->nomor}. Unduh PDF, cetak, tanda tangani basah, lalu beri cap." : 'Surat diajukan.');
    }

    public function paraf(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $d = $request->validate(['catatan' => ['nullable', 'string', 'max:500']]);
        $this->alur->paraf($surat, $request->user(), $d['catatan'] ?? null);

        return redirect()->route('surat-keluar.show', $surat)->with('sukses', 'Surat diparaf dan diteruskan ke penandatangan.');
    }

    public function tandatangani(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $request->validate(['password' => ['required', 'string']], ['password.required' => 'Masukkan kata sandi akun Anda untuk menandatangani.']);
        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Kata sandi salah.']);
        }
        $s = $this->alur->tandatangani($surat, $request->user());

        return redirect()->route('surat-keluar.show', $s)->with('sukses', "Surat ditandatangani. Nomor: {$s->nomor}");
    }

    public function kembalikan(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $d = $request->validate(['catatan' => ['required', 'string', 'min:5', 'max:500']], ['catatan.required' => 'Catatan wajib diisi.']);
        $this->alur->kembalikan($surat, $request->user(), $d['catatan']);

        return redirect()->route('surat-keluar.index')->with('sukses', 'Surat dikembalikan ke pembuat.');
    }

    public function batalkan(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $d = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:500']], ['alasan.required' => 'Alasan pembatalan wajib diisi.']);
        $this->alur->batalkan($surat, $request->user(), $d['alasan']);

        return redirect()->route('surat-keluar.show', $surat)->with('sukses', 'Surat dibatalkan. Nomor tidak akan dipakai ulang.');
    }

    public function hapus(Request $request, Surat $surat)
    {
        $this->milik($surat);
        abort_unless($this->alur->bolehMengubah($surat, $request->user()) && ! $surat->nomor, 403);
        $surat->persetujuan()->delete();
        $surat->delete();

        return redirect()->route('surat-keluar.index')->with('sukses', 'Draf dihapus.');
    }

    /** Tanggal surat: dari 30 hari lalu sampai 90 hari ke depan (menjaga konsistensi nomor surat). */
    private function aturanTanggal(): array
    {
        return ['tanggal_surat' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.now()->subDays(30)->toDateString(), 'before_or_equal:'.now()->addDays(90)->toDateString()]];
    }

    private function pesanTanggal(): array
    {
        return [
            'tanggal_surat.date_format' => 'Tanggal surat tidak valid.',
            'tanggal_surat.after_or_equal' => 'Tanggal surat tidak boleh lebih dari 30 hari ke belakang.',
            'tanggal_surat.before_or_equal' => 'Tanggal surat tidak boleh lebih dari 90 hari ke depan.',
        ];
    }

    private function tanggalPratinjau(Request $request): ?string
    {
        $t = (string) $request->input('tanggal_surat');

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) && strtotime($t) ? $t : null;
    }

    private function milik(Surat $s): void
    {
        abort_unless($s->arah === 'keluar' && $s->pengajuan_id === null, 404);
    }

    private function dataForm(?Surat $s): array
    {
        return [
            's' => $s,
            'klasifikasi' => KlasifikasiSurat::where('aktif', true)->orderBy('kode')->get(),
            'jabatan' => Jabatan::with('pejabat')->where('aktif', true)->whereNotNull('user_id')->orderBy('nama')->get(),
        ];
    }

    /** Aturan isian nomor surat (hanya Admin TU yang mengetik nomor). */
    private function aturanNomor(Request $request, ?int $suratId, ?int $klasifikasiId): array
    {
        return $request->user()->adalahAdmin() ? ['nomor_manual' => NomorManual::aturan($suratId, $klasifikasiId, $request->input('tanggal_surat'), NomorManual::wajib())] : [];
    }

    private function simpanNomor(Request $request, Surat $s): void
    {
        if ($request->user()->adalahAdmin() && $request->exists('nomor_manual')) {
            $this->alur->aturNomor($s, $request->user(), $request->input('nomor_manual'));
        }
    }

    /** Admin TU mengisi / mengubah nomor surat dari halaman surat (sebelum terbit). */
    public function nomor(Request $request, Surat $surat)
    {
        $this->milik($surat);
        $request->validate(['nomor_manual' => NomorManual::aturan($surat->id, $surat->klasifikasi_id, $surat->tgl_surat?->toDateString())], NomorManual::PESAN, ['nomor_manual' => 'nomor urut surat']);
        $this->alur->aturNomor($surat, $request->user(), $request->input('nomor_manual'));

        return redirect()->route('surat-keluar.show', $surat)->with('sukses', $request->filled('nomor_manual') ? 'Nomor urut disimpan.' : 'Nomor urut manual dikosongkan; nomor otomatis dipakai saat terbit.');
    }

    private function validasi(Request $request, ?int $suratId = null): array
    {
        return $request->validate([
            'klasifikasi_id' => ['required', 'exists:klasifikasi_surat,id'],
            'sifat' => ['required', Rule::in(['biasa', 'penting', 'segera', 'rahasia'])],
            'tujuan' => ['required', 'string', 'max:500'],
            'perihal' => ['required', 'string', 'max:200'],
            'lampiran' => ['nullable', 'string', 'max:100'],
            'isi' => ['required', 'string', 'max:8000'],
            'salam' => ['nullable', 'boolean'],
            'jabatan_id' => ['required', Rule::exists('jabatan', 'id')->where('aktif', true)->whereNotNull('user_id')],
            'paraf_role' => ['nullable', Rule::in(['wakil_dekan', 'kaprodi'])],
            'mode_ttd' => ['required', Rule::in(['qr', 'basah'])],
        ] + $this->aturanTanggal() + $this->aturanNomor($request, $suratId, (int) $request->input('klasifikasi_id') ?: null), $this->pesanTanggal() + NomorManual::PESAN + [
            'tujuan.required' => 'Tujuan surat wajib diisi.', 'perihal.required' => 'Perihal wajib diisi.', 'isi.required' => 'Isi surat wajib diisi.',
            'jabatan_id.required' => 'Pilih penandatangan.', 'klasifikasi_id.required' => 'Pilih klasifikasi.',
        ]);
    }
}
