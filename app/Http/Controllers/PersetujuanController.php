<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengajuan as S;
use App\Models\Pengajuan;
use App\Services\AlurPengajuan;
use App\Services\PenyusunSurat;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PersetujuanController extends Controller
{
    public function index(Request $request, AlurPengajuan $alur)
    {
        $user = $request->user();
        $daftar = Pengajuan::with('jenis.penandatanganJabatan', 'pemohon.prodi')
            ->whereIn('status', [S::Diverifikasi->value, S::Disetujui->value])->latest()->get()
            ->filter(fn ($p) => $alur->bolehParaf($p, $user) || $alur->bolehTandatangan($p, $user))->values();

        return view('persetujuan.index', ['daftar' => $daftar]);
    }

    public function show(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur, PenyusunSurat $penyusun)
    {
        $user = $request->user();
        $this->authorize('view', $pengajuan);
        $pengajuan->load('jenis.penandatanganJabatan.pejabat', 'pemohon.prodi', 'persetujuan.user', 'surat.klasifikasi', 'lampiran');
        abort_unless($pengajuan->surat, 404, 'Surat belum disusun (belum diverifikasi).');

        return view('persetujuan.show', [
            'p' => $pengajuan,
            'surat' => $pengajuan->surat,
            'dokumen' => $penyusun->dataDokumen($pengajuan->surat),
            'bolehParaf' => $alur->bolehParaf($pengajuan, $user),
            'bolehTtd' => $alur->bolehTandatangan($pengajuan, $user),
        ]);
    }

    public function paraf(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:500']]);
        $alur->paraf($pengajuan, $request->user(), $data['catatan'] ?? null);

        return redirect()->route('persetujuan.index')->with('sukses', 'Surat telah diparaf dan diteruskan ke penandatangan.');
    }

    /** Konfirmasi kata sandi akun sebelum menandatangani (pengganti passphrase sertifikat). */
    public function tandatangani(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $request->validate(['password' => ['required', 'string']], ['password.required' => 'Masukkan kata sandi akun Anda untuk menandatangani.']);
        if (! \Hash::check($request->input('password'), $request->user()->password)) {
            \App\Models\LogAktivitas::catat('ttd_gagal', 'Kata sandi konfirmasi TTD salah', $pengajuan);
            throw ValidationException::withMessages(['password' => 'Kata sandi salah.']);
        }
        $p = $alur->tandatangani($pengajuan, $request->user());

        return redirect()->route('pengajuan.show', $p)->with('sukses', "Surat berhasil ditandatangani. Nomor: {$p->surat->nomor}");
    }

    public function kembalikan(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $data = $request->validate(['catatan' => ['required', 'string', 'min:5', 'max:500']], ['catatan.required' => 'Catatan revisi wajib diisi.']);
        $alur->kembalikan($pengajuan, $request->user(), $data['catatan']);

        return redirect()->route('persetujuan.index')->with('sukses', 'Pengajuan dikembalikan ke tahap verifikasi.');
    }

    public function tolak(Request $request, Pengajuan $pengajuan, AlurPengajuan $alur)
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:500']], ['alasan.required' => 'Alasan penolakan wajib diisi.']);
        $alur->tolak($pengajuan, $request->user(), $data['alasan']);

        return redirect()->route('persetujuan.index')->with('sukses', 'Pengajuan ditolak.');
    }
}
