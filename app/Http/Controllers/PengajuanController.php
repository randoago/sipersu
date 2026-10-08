<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Enums\StatusPengajuan as S;
use App\Models\JenisSurat;
use App\Models\Pengajuan;
use App\Services\AlurPengajuan;
use App\Support\NomorManual;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PengajuanController extends Controller
{
    public function katalog(Request $request)
    {
        return view('layanan.katalog', ['jenis' => JenisSurat::untukMahasiswa()->where('aktif', true)->orderBy('urutan')->get()]);
    }

    public function riwayat(Request $request)
    {
        $data = $request->validate(['status' => ['nullable', Rule::enum(S::class)]]);
        $daftar = Pengajuan::with('jenis')->where('user_id', $request->user()->id)
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(10)->withQueryString();

        return view('layanan.riwayat', ['daftar' => $daftar, 'status' => $data['status'] ?? null]);
    }

    /** Kotak pencarian "Lacak Status": cari kode pengajuan milik sendiri (staf: semua yang berhak dilihat). */
    public function lacak(Request $request)
    {
        $kode = trim((string) $request->query('kode', ''));
        $user = $request->user();
        if ($kode !== '') {
            $p = Pengajuan::where('kode', $kode)->first();
            if ($p && $user->can('view', $p)) {
                return redirect()->route('pengajuan.show', $p);
            }
            session()->flash('galat', "Pengajuan dengan kode \"$kode\" tidak ditemukan.");
        }

        return view('layanan.lacak', [
            'terbaru' => Pengajuan::with('jenis')->where('user_id', $user->id)->latest()->limit(6)->get(),
            'kode' => $kode,
        ]);
    }

    /** Antrean untuk petugas (Admin TU, Kaprodi, Wakil Dekan, Dekan). */
    public function index(Request $request, AlurPengajuan $alur)
    {
        $user = $request->user();
        abort_if($user->hasRole(Peran::Mahasiswa->value) && $user->roles->count() === 1, 403);

        $data = $request->validate(['status' => ['nullable', Rule::enum(S::class)], 'q' => ['nullable', 'string', 'max:100']]);
        $daftar = Pengajuan::with('jenis', 'pemohon.prodi')
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($data['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('kode', 'like', "%$t%")
                ->orWhereHas('pemohon', fn ($u) => $u->where('nama', 'like', "%$t%")->orWhere('nomor_induk', 'like', "%$t%"))))
            ->when($user->hasRole(Peran::Kaprodi->value) && ! $user->adalahAdmin() && ! $user->hasAnyRole([Peran::Dekan->value, Peran::WakilDekan->value]),
                fn ($q) => $q->whereHas('pemohon', fn ($u) => $u->where('prodi_id', $user->prodi_id)))
            ->latest()->paginate(12)->withQueryString();

        return view('pengajuan.index', ['daftar' => $daftar, 'status' => $data['status'] ?? null, 'q' => $data['q'] ?? '', 'alur' => $alur]);
    }

    public function show(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $this->authorize('view', $pengajuan);
        $pengajuan->load('jenis.penandatanganJabatan', 'pemohon.prodi', 'lampiran', 'surat', 'persetujuan.user');

        return view('pengajuan.show', ['p' => $pengajuan, 'alur' => $alur, 'user' => $request->user()]);
    }

    public function verifikasi(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:500'], 'nomor_surat' => ['nullable', 'string', 'regex:'.NomorManual::POLA]],
            ['nomor_surat.regex' => NomorManual::PESAN['nomor_surat.regex']]);
        $alur->verifikasi($pengajuan, $request->user(), $data['catatan'] ?? null, $data['nomor_surat'] ?? null);

        return redirect()->route('pengajuan.show', $pengajuan)->with('sukses', 'Pengajuan berhasil diverifikasi dan diteruskan.');
    }

    /** Admin TU mengisi / mengubah nomor surat pengajuan sebelum surat terbit (penomoran mandiri). */
    public function nomor(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $pengajuan->loadMissing('surat');
        $request->validate(['nomor_manual' => NomorManual::aturan($pengajuan->surat?->id, $pengajuan->surat?->klasifikasi_id, $pengajuan->surat?->tgl_surat?->toDateString())], NomorManual::PESAN, ['nomor_manual' => 'nomor surat']);
        $alur->aturNomor($pengajuan, $request->user(), $request->input('nomor_manual'));

        return redirect()->route('pengajuan.show', $pengajuan)->with('sukses', 'Nomor urut disimpan.');
    }

    public function tolak(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:500']], ['alasan.required' => 'Alasan penolakan wajib diisi.', 'alasan.min' => 'Alasan penolakan terlalu singkat.']);
        $alur->tolak($pengajuan, $request->user(), $data['alasan']);

        return redirect()->route('pengajuan.show', $pengajuan)->with('sukses', 'Pengajuan ditolak dan pemohon telah diberi tahu.');
    }

    public function selesai(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $alur->selesaikan($pengajuan, $request->user());

        return redirect()->route('pengajuan.show', $pengajuan)->with('sukses', 'Pengajuan ditandai selesai.');
    }
}
