<?php

namespace App\Http\Controllers;

use App\Enums\Peran;
use App\Enums\StatusPengajuan as S;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Models\Surat;
use App\Services\AlurPengajuan;
use Illuminate\Http\Request;

class DasborController extends Controller
{
    public function __invoke(Request $request, AlurPengajuan $alur)
    {
        $user = $request->user();

        if ($user->hasRole(Peran::Mahasiswa->value) && $user->roles->count() === 1) {
            return view('dasbor.mahasiswa', [
                'jenis' => JenisSurat::untukMahasiswa()->where('aktif', true)->orderBy('urutan')->get(),
                'pengajuan' => Pengajuan::with('jenis.penandatanganJabatan', 'surat')->where('user_id', $user->id)->latest()->limit(3)->get(),
                'berjalan' => Pengajuan::where('user_id', $user->id)->whereNotIn('status', [S::Selesai->value, S::Ditolak->value])->count(),
            ]);
        }

        $tugasPengajuan = Pengajuan::with('jenis.penandatanganJabatan', 'pemohon.prodi')
            ->whereIn('status', [S::Diajukan->value, S::Diverifikasi->value, S::Disetujui->value])
            ->latest()->get()
            ->filter(fn (Pengajuan $p) => $alur->bolehVerifikasi($p, $user) || $alur->bolehParaf($p, $user) || $alur->bolehTandatangan($p, $user))
            ->map(fn (Pengajuan $p) => (object) [
                'kategori' => $p->jenis->kategori ?? 'Umum', 'judul' => $p->jenis->nama, 'ref' => $p->kode,
                'nama' => $p->pemohon->nama, 'sub' => 'NIM: '.$p->pemohon->nomor_induk.' • '.$p->pemohon->prodi?->nama,
                'waktu' => $p->created_at, 'status' => $p->status, 'filter' => $p->status->value,
                'url' => in_array($p->status->value, ['diverifikasi', 'disetujui']) ? route('persetujuan.show', $p) : route('pengajuan.show', $p),
            ]);

        $alurSurat = app(\App\Services\AlurSuratKeluar::class);
        $tugasSurat = Surat::with('jabatan', 'pembuat')->where('arah', 'keluar')->whereNull('pengajuan_id')->whereIn('status', ['menunggu_paraf', 'menunggu_ttd'])
            ->latest()->get()
            ->filter(fn (Surat $x) => $alurSurat->bolehParaf($x, $user) || $alurSurat->bolehTandatangan($x, $user))
            ->map(fn (Surat $x) => (object) [
                'kategori' => 'Surat Keluar', 'judul' => $x->perihal, 'ref' => 'Kepada: '.\Illuminate\Support\Str::limit((string) $x->asal_tujuan, 30),
                'nama' => $x->pembuat?->nama ?? '-', 'sub' => 'Pembuat surat',
                'waktu' => $x->created_at, 'status' => $x->status === 'menunggu_paraf' ? S::Diverifikasi : S::Disetujui,
                'filter' => $x->status === 'menunggu_paraf' ? 'diverifikasi' : 'disetujui', 'url' => route('surat-keluar.show', $x),
            ]);

        $perluTindakan = $tugasPengajuan->concat($tugasSurat)->sortByDesc('waktu')->values();

        $bulanIni = now()->startOfMonth();
        $stat = [
            'perlu' => $perluTindakan->count(),
            'menunggu_ttd' => Pengajuan::where('status', S::Disetujui->value)->count(),
            'terbit_bulan_ini' => Surat::where('arah', 'keluar')->where('status', 'ditandatangani')->where('ditandatangani_pada', '>=', $bulanIni)->count(),
            'selesai_bulan_ini' => Pengajuan::whereIn('status', [S::Ditandatangani->value, S::Selesai->value])->where('updated_at', '>=', $bulanIni)->count(),
            'terakhir' => Surat::whereNotNull('nomor')->latest('ditandatangani_pada')->value('nomor'),
            'masuk_bulan_ini' => Surat::where('arah', 'masuk')->where('created_at', '>=', $bulanIni)->count(),
            'agenda_terakhir' => Surat::where('arah', 'masuk')->latest('id')->value('no_agenda'),
        ];

        $tahun = now()->year;
        $perBulan = Surat::selectRaw("cast(strftime('%m', coalesce(ditandatangani_pada, tgl_surat)) as integer) as bulan, arah, count(*) as jml")
            ->whereRaw("strftime('%Y', coalesce(ditandatangani_pada, tgl_surat)) = ?", [(string) $tahun])
            ->where(fn ($q) => $q->where('arah', 'masuk')->orWhere('status', 'ditandatangani'))
            ->groupBy('bulan', 'arah')->get();
        $grafik = ['masuk' => array_fill(1, 12, 0), 'keluar' => array_fill(1, 12, 0)];
        foreach ($perBulan as $r) {
            if ($r->bulan) {
                $grafik[$r->arah][(int) $r->bulan] = (int) $r->jml;
            }
        }

        $admin = $user->adalahAdmin();

        return view('dasbor.staf', [
            'peringatanBackup' => $admin ? \App\Services\StatusBackup::peringatan() : [],
            'ujiPemulihan' => $admin && \App\Services\StatusBackup::perluUjiPemulihan(),
            'perluTindakan' => $perluTindakan,
            'stat' => $stat,
            'grafik' => array_map('array_values', $grafik),
            'tahun' => $tahun,
            'aktivitas' => LogAktivitas::with('user')->whereIn('aksi', ['pengajuan_dibuat', 'pengajuan_verifikasi', 'pengajuan_paraf', 'ttd', 'pengajuan_ditolak', 'pengajuan_selesai'])
                ->latest('created_at')->limit(5)->get(),
        ]);
    }
}
