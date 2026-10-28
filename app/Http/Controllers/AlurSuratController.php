<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Persetujuan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Menu Alur Surat: TU mengatur siapa yang memverifikasi, memaraf, dan menandatangani tiap format surat. */
class AlurSuratController extends Controller
{
    /** Pengajuan mahasiswa yang masih berjalan (belum terbit/ditolak) memakai alur format secara langsung. */
    private const BERJALAN = ['diajukan', 'diverifikasi', 'disetujui'];

    public function index()
    {
        $daftar = JenisSurat::with('penandatanganJabatan.pejabat')
            ->withCount(['pengajuan as berjalan_count' => fn ($q) => $q->whereIn('status', self::BERJALAN)])
            ->whereIn('sasaran', ['mahasiswa', 'staf'])
            ->orderBy('urutan')->orderBy('nama')->get()->groupBy('sasaran');

        return view('alur-surat.index', [
            'mahasiswa' => $daftar->get('mahasiswa', collect()),
            'staf' => $daftar->get('staf', collect()),
            'jabatan' => Jabatan::with('pejabat')->where('aktif', true)->orderBy('nama')->get(),
        ]);
    }

    public function perbarui(Request $request, JenisSurat $format)
    {
        abort_if($format->sasaran === 'masuk', 404);
        $mhs = $format->sasaran === 'mahasiswa';
        $qr = $request->input('mode_ttd') === 'qr';

        $d = $request->validateWithBag('f'.$format->id, [
            'mode_ttd' => ['required', Rule::in(['qr', 'basah'])],
            'perlu_paraf' => ['nullable', 'boolean'],
            'paraf_role' => [Rule::requiredIf($qr && $request->boolean('perlu_paraf')), 'nullable', Rule::in(['wakil_dekan', 'kaprodi'])],
            'verifikator_role' => [$mhs ? 'required' : 'nullable', Rule::in(['admin_tu', 'kaprodi'])],
            'penandatangan_jabatan_id' => ['required', Rule::exists('jabatan', 'id')->where(fn ($q) => $q->where('aktif', true)->orWhere('id', $format->penandatangan_jabatan_id))],
            'sla_hari' => [$mhs ? 'required' : 'nullable', 'integer', 'min:1', 'max:30'],
        ], [
            'paraf_role.required' => 'Pilih pemaraf bila surat memerlukan paraf.',
            'verifikator_role.required' => 'Pilih pemverifikasi.',
            'penandatangan_jabatan_id.required' => 'Pilih penandatangan.',
            'penandatangan_jabatan_id.exists' => 'Penandatangan tidak valid.',
            'sla_hari.required' => 'Isi target selesai (hari kerja).',
        ]);

        $baru = JenisSurat::rapikanAlur([
            'sasaran' => $format->sasaran, 'mode_ttd' => $d['mode_ttd'], 'perlu_paraf' => $request->boolean('perlu_paraf'),
            'paraf_role' => $d['paraf_role'] ?? null, 'verifikator_role' => $d['verifikator_role'] ?? $format->verifikator_role,
        ]) + ['penandatangan_jabatan_id' => (int) $d['penandatangan_jabatan_id'], 'sla_hari' => $mhs ? (int) $d['sla_hari'] : $format->sla_hari];
        unset($baru['sasaran']);

        $sebelum = $format->only(array_keys($baru));
        $tahap = ['mode_ttd', 'perlu_paraf', 'paraf_role', 'verifikator_role'];
        $berjalan = $mhs ? $format->pengajuan()->whereIn('status', self::BERJALAN)->count() : 0;
        if ($berjalan && array_diff_assoc(array_intersect_key($baru, array_flip($tahap)), $format->only($tahap))) {
            throw ValidationException::withMessages(['alur' => "Masih ada {$berjalan} pengajuan yang sedang berjalan pada format ini. Selesaikan atau tolak dulu sebelum mengubah bentuk, pemverifikasi, atau paraf. (Mengganti penandatangan dan target waktu tetap bisa.)"])
                ->errorBag('f'.$format->id);
        }

        $format->update($baru);
        if ($mhs && $sebelum['penandatangan_jabatan_id'] !== $baru['penandatangan_jabatan_id']) {
            Persetujuan::where('tahap', 'ttd')->where('status', 'menunggu')
                ->whereIn('pengajuan_id', $format->pengajuan()->whereIn('status', self::BERJALAN)->select('id'))
                ->update(['jabatan_id' => $baru['penandatangan_jabatan_id']]);
        }
        LogAktivitas::catat('alur_ubah', 'Mengubah alur surat: '.$format->nama, $format, ['sebelum' => $sebelum, 'sesudah' => $baru]);

        return redirect()->route('alur-surat.index')->with('sukses', "Alur \"{$format->nama}\" disimpan."
            .($mhs ? '' : ' Berlaku untuk surat yang dibuat setelah ini; draf yang sudah ada tetap memakai alur lamanya.'));
    }
}
