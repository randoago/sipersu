<?php

namespace App\Services;

use App\Enums\Peran;
use App\Enums\StatusPengajuan as S;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Models\Persetujuan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Diajukan → Diverifikasi → Disetujui → Ditandatangani → Selesai (atau Ditolak).
 *  - Verifikasi : peran jenis_surat.verifikator_role (Kaprodi harus satu prodi dengan pemohon)
 *  - Paraf      : peran jenis_surat.paraf_role, hanya bila perlu_paraf (bila tidak, verifikasi langsung → Disetujui)
 *  - TTD        : pejabat aktif pada jabatan penandatangan (Super Admin TIDAK boleh menandatangani)
 */
class AlurPengajuan
{
    public function __construct(private PenyusunSurat $penyusun, private TandaTanganService $tte)
    {
    }

    public function ajukan(User $pemohon, JenisSurat $jenis, array $isian): Pengajuan
    {
        return DB::transaction(function () use ($pemohon, $jenis, $isian) {
            $p = Pengajuan::create([
                'kode' => Pengajuan::buatKode(),
                'user_id' => $pemohon->id,
                'jenis_surat_id' => $jenis->id,
                'data_isian' => $isian,
                'status' => S::Diajukan,
                'batas_waktu' => now()->addWeekdays($jenis->sla_hari),
            ]);

            $p->persetujuan()->create(['tahap' => 'verifikasi', 'urutan' => 1, 'peran' => $jenis->verifikator_role]);
            if (! $jenis->langsungTerbit()) {            // tanpa QR + tanpa persetujuan: hanya verifikasi TU
                if ($jenis->perlu_paraf) {
                    $p->persetujuan()->create(['tahap' => 'paraf', 'urutan' => 2, 'peran' => $jenis->paraf_role]);
                }
                $p->persetujuan()->create(['tahap' => 'ttd', 'urutan' => 3, 'peran' => 'penandatangan', 'jabatan_id' => $jenis->penandatangan_jabatan_id]);
            }

            LogAktivitas::catat('pengajuan_dibuat', "Mengajukan {$jenis->nama} ({$p->kode})", $p, [], $pemohon->id);
            Notifikator::kirim(
                $this->verifikator($p), 'Pengajuan baru: '.$jenis->nama,
                "{$pemohon->nama} ({$pemohon->nomor_induk}) mengajukan {$jenis->nama}. Mohon diverifikasi.",
                route('pengajuan.show', $p), 'tugas',
            );

            return $p;
        });
    }

    // ---- wewenang ---------------------------------------------------------------------------------------

    public function bolehVerifikasi(Pengajuan $p, User $u): bool
    {
        if ($p->status !== S::Diajukan) {
            return false;
        }
        $peran = $p->jenis->verifikator_role;
        if ($u->hasRole(Peran::SuperAdmin->value)) {
            return true;
        }
        if (! $u->hasRole($peran)) {
            return false;
        }

        return $peran !== Peran::Kaprodi->value || ($u->prodi_id && $u->prodi_id === $p->pemohon->prodi_id);
    }

    public function bolehParaf(Pengajuan $p, User $u): bool
    {
        return $p->status === S::Diverifikasi && $p->jenis->perlu_paraf
            && ($u->hasRole(Peran::SuperAdmin->value) || $u->hasRole($p->jenis->paraf_role));
    }

    public function bolehTandatangan(Pengajuan $p, User $u): bool
    {
        return $p->status === S::Disetujui && $p->jenis->penandatanganJabatan?->user_id === $u->id;
    }

    public function bolehSelesaikan(Pengajuan $p, User $u): bool
    {
        return $p->status === S::Ditandatangani && $u->adalahAdmin();
    }

    /** Pengguna yang berhak menolak/mengembalikan pada tahap berjalan. */
    public function bolehTolak(Pengajuan $p, User $u): bool
    {
        return $this->bolehVerifikasi($p, $u) || $this->bolehParaf($p, $u) || $this->bolehTandatangan($p, $u);
    }

    // ---- transisi ---------------------------------------------------------------------------------------

    public function verifikasi(Pengajuan $p, User $oleh, ?string $catatan = null): Pengajuan
    {
        $this->wajib($this->bolehVerifikasi($p, $oleh), 'Anda tidak berwenang memverifikasi pengajuan ini.');

        return DB::transaction(function () use ($p, $oleh, $catatan) {
            $this->putuskan($p, 'verifikasi', $oleh, 'disetujui', $catatan);
            $p->update(['diverifikasi_oleh' => $oleh->id, 'diverifikasi_pada' => now()]);
            $surat = $p->surat ?? $this->penyusun->buatSuratDariPengajuan($p);

            if ($p->jenis->langsungTerbit()) {
                // Tanpa QR + tanpa persetujuan: setelah verifikasi TU surat langsung terbit (nomor + PDF) untuk dicetak.
                $surat = $this->tte->terbitkanLangsung($surat->refresh(), $oleh);
                $p->update(['status' => S::Ditandatangani]);
                Notifikator::kirim($p->pemohon, 'Surat Anda telah terbit', "{$p->jenis->nama} bernomor {$surat->nomor} sudah terbit dan dapat diunduh untuk dicetak.", route('pengajuan.show', $p), 'sukses');
            } elseif ($p->jenis->perlu_paraf) {
                $p->update(['status' => S::Diverifikasi]);
                Notifikator::kirim(Notifikator::penggunaPeran([$p->jenis->paraf_role]), 'Menunggu paraf: '.$p->jenis->nama,
                    "Pengajuan {$p->kode} telah diverifikasi dan menunggu paraf Anda.", route('persetujuan.show', $p), 'tugas');
            } else {
                $this->siapTtd($p, $surat);
            }
            LogAktivitas::catat('pengajuan_verifikasi', "Memverifikasi {$p->kode}", $p, [], $oleh->id);
            Notifikator::kirim($p->pemohon, 'Pengajuan diverifikasi', "{$p->jenis->nama} ({$p->kode}) telah diverifikasi.", route('pengajuan.show', $p));

            return $p->refresh();
        });
    }

    public function paraf(Pengajuan $p, User $oleh, ?string $catatan = null): Pengajuan
    {
        $this->wajib($this->bolehParaf($p, $oleh), 'Anda tidak berwenang memaraf pengajuan ini.');

        return DB::transaction(function () use ($p, $oleh, $catatan) {
            $this->putuskan($p, 'paraf', $oleh, 'disetujui', $catatan);
            $this->siapTtd($p, $p->surat);
            LogAktivitas::catat('pengajuan_paraf', "Memaraf {$p->kode}", $p, [], $oleh->id);

            return $p->refresh();
        });
    }

    public function tandatangani(Pengajuan $p, User $oleh): Pengajuan
    {
        $this->wajib($this->bolehTandatangan($p, $oleh), 'Anda bukan penandatangan yang berwenang untuk pengajuan ini.');

        return DB::transaction(function () use ($p, $oleh) {
            $surat = $this->tte->tandatangani($p->surat, $oleh);
            $this->putuskan($p, 'ttd', $oleh, 'disetujui', null);
            $p->update(['status' => S::Ditandatangani]);
            Notifikator::kirim($p->pemohon, 'Surat Anda telah ditandatangani',
                "{$p->jenis->nama} bernomor {$surat->nomor} sudah terbit dan dapat diunduh.", route('pengajuan.show', $p), 'sukses');

            return $p->refresh();
        });
    }

    public function selesaikan(Pengajuan $p, User $oleh): Pengajuan
    {
        $this->wajib($this->bolehSelesaikan($p, $oleh), 'Pengajuan belum dapat diselesaikan.');
        $p->update(['status' => S::Selesai]);
        LogAktivitas::catat('pengajuan_selesai', "Menyelesaikan {$p->kode}", $p, [], $oleh->id);

        return $p->refresh();
    }

    public function tolak(Pengajuan $p, User $oleh, string $alasan): Pengajuan
    {
        $this->wajib($this->bolehTolak($p, $oleh), 'Anda tidak berwenang menolak pengajuan ini.');
        if (trim($alasan) === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan penolakan wajib diisi.']);
        }

        return DB::transaction(function () use ($p, $oleh, $alasan) {
            $tahap = $p->status === S::Diajukan ? 'verifikasi' : ($p->status === S::Diverifikasi ? 'paraf' : 'ttd');
            $this->putuskan($p, $tahap, $oleh, 'ditolak', $alasan);
            $p->update(['status' => S::Ditolak, 'alasan_tolak' => $alasan]);
            if ($p->surat && $p->surat->status !== 'ditandatangani') {
                $p->surat->update(['status' => 'batal', 'dibatalkan_pada' => now(), 'alasan_batal' => $alasan]);
            }
            LogAktivitas::catat('pengajuan_ditolak', "Menolak {$p->kode}", $p, ['alasan' => $alasan], $oleh->id);
            Notifikator::kirim($p->pemohon, 'Pengajuan ditolak', "{$p->jenis->nama} ({$p->kode}) ditolak: {$alasan}", route('pengajuan.show', $p), 'bahaya');

            return $p->refresh();
        });
    }

    /** Kembalikan ke tahap verifikasi (mis. data perlu dikoreksi Admin TU). */
    public function kembalikan(Pengajuan $p, User $oleh, string $catatan): Pengajuan
    {
        $this->wajib($this->bolehParaf($p, $oleh) || $this->bolehTandatangan($p, $oleh), 'Anda tidak berwenang mengembalikan pengajuan ini.');
        if (trim($catatan) === '') {
            throw ValidationException::withMessages(['catatan' => 'Catatan revisi wajib diisi.']);
        }

        return DB::transaction(function () use ($p, $oleh, $catatan) {
            $p->persetujuan()->where('tahap', '!=', 'verifikasi')->update(['status' => 'menunggu', 'user_id' => null, 'diputuskan_pada' => null]);
            $p->persetujuan()->where('tahap', 'verifikasi')->update(['status' => 'revisi', 'catatan' => $oleh->nama.': '.$catatan]);
            $p->update(['status' => S::Diajukan, 'catatan' => $catatan]);
            $p->surat?->update(['status' => 'menunggu_paraf']);
            Notifikator::kirim($this->verifikator($p), 'Pengajuan dikembalikan untuk revisi', "{$p->kode}: {$catatan}", route('pengajuan.show', $p), 'peringatan');

            return $p->refresh();
        });
    }

    // ---- util -------------------------------------------------------------------------------------------

    private function siapTtd(Pengajuan $p, $surat): void
    {
        $p->update(['status' => S::Disetujui]);
        $surat->update(['status' => 'menunggu_ttd']);
        $pejabat = $p->jenis->penandatanganJabatan?->pejabat;
        if ($pejabat) {
            Notifikator::kirim($pejabat, 'Menunggu tanda tangan: '.$p->jenis->nama,
                "Surat {$p->kode} siap ditandatangani.", route('persetujuan.show', $p), 'tugas');
        }
    }

    private function putuskan(Pengajuan $p, string $tahap, User $oleh, string $status, ?string $catatan): void
    {
        $p->persetujuan()->where('tahap', $tahap)->update([
            'user_id' => $oleh->id, 'status' => $status, 'catatan' => $catatan, 'diputuskan_pada' => now(),
        ]);
    }

    private function verifikator(Pengajuan $p)
    {
        $peran = $p->jenis->verifikator_role;

        return Notifikator::penggunaPeran([$peran], $peran === Peran::Kaprodi->value ? $p->pemohon->prodi_id : null);
    }

    private function wajib(bool $syarat, string $pesan): void
    {
        if (! $syarat) {
            throw new \Illuminate\Auth\Access\AuthorizationException($pesan);
        }
    }
}
