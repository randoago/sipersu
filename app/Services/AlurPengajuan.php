<?php

namespace App\Services;

use App\Enums\Peran;
use App\Enums\StatusPengajuan as S;
use App\Models\JenisSurat;
use App\Models\LogAktivitas;
use App\Models\Pengajuan;
use App\Models\Persetujuan;
use App\Models\User;
use App\Support\NomorManual;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Diajukan → Diverifikasi → Disetujui → Ditandatangani → Selesai (atau Ditolak).
 * Tahapan disalin dari alur format ke baris `persetujuan` saat pengajuan dibuat (jenis_surat.alur), sehingga mengubah alur
 * format tidak mengganggu pengajuan yang sedang berjalan. Tahap berjalan = baris pertama yang belum diputuskan.
 *  - Verifikasi : satu atau lebih, tiap tahap oleh perannya (Kaprodi harus satu prodi dengan pemohon)
 *  - Paraf      : nol atau lebih, tiap tahap oleh perannya
 *  - TTD        : pejabat aktif pada jabatan penandatangan, selalu terakhir (Super Admin TIDAK boleh menandatangani)
 * Status pengajuan mengikuti jenis tahap berjalan: verifikasi → Diajukan, paraf → Diverifikasi, ttd → Disetujui.
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

            $urutan = 0;
            foreach ($jenis->tahapAlur() as $t) {
                $p->persetujuan()->create(['tahap' => $t['tahap'], 'urutan' => ++$urutan, 'peran' => $t['peran']]);
            }
            if (! $jenis->langsungTerbit()) {            // tanpa QR + tanpa persetujuan: hanya verifikasi
                $p->persetujuan()->create(['tahap' => 'ttd', 'urutan' => ++$urutan, 'peran' => 'penandatangan', 'jabatan_id' => $jenis->penandatangan_jabatan_id]);
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

    /** Tahap yang sedang menunggu (baris persetujuan pertama yang belum diputuskan); selalu dibaca dari basis data. */
    public function berjalan(Pengajuan $p): ?Persetujuan
    {
        return $p->persetujuan()->whereIn('status', ['menunggu', 'revisi'])->first();
    }

    /** Tahap status → jenis tahap yang sedang menunggu pada status itu. */
    private const TAHAP_STATUS = ['diajukan' => 'verifikasi', 'diverifikasi' => 'paraf', 'disetujui' => 'ttd'];

    /**
     * Jenis tahap ('verifikasi' | 'paraf' | 'ttd') yang berhak diputuskan pengguna sekarang, atau null. Satu kueri tahap berjalan;
     * pakai ini (bukan beberapa boleh*) bila perlu mengetahui apakah pengguna punya tugas pada pengajuan.
     */
    public function wewenang(Pengajuan $p, User $u): ?string
    {
        $tahap = $this->berjalan($p);
        if (! $tahap || (self::TAHAP_STATUS[$p->status->value] ?? null) !== $tahap->tahap) {
            return null;
        }
        if ($tahap->tahap === 'ttd') {
            return ($tahap->jabatan ?? $p->jenis->penandatanganJabatan)?->user_id === $u->id ? 'ttd' : null;
        }
        $boleh = $u->hasRole(Peran::SuperAdmin->value)
            || ($u->hasRole($tahap->peran) && ($tahap->peran !== Peran::Kaprodi->value || ($u->prodi_id && $u->prodi_id === $p->pemohon->prodi_id)));

        return $boleh ? $tahap->tahap : null;
    }

    public function bolehVerifikasi(Pengajuan $p, User $u): bool
    {
        return $this->wewenang($p, $u) === 'verifikasi';
    }

    public function bolehParaf(Pengajuan $p, User $u): bool
    {
        return $this->wewenang($p, $u) === 'paraf';
    }

    public function bolehTandatangan(Pengajuan $p, User $u): bool
    {
        return $this->wewenang($p, $u) === 'ttd';
    }

    public function bolehSelesaikan(Pengajuan $p, User $u): bool
    {
        return $p->status === S::Ditandatangani && $u->adalahAdmin();
    }

    /** Pengguna yang berhak menolak pada tahap berjalan. */
    public function bolehTolak(Pengajuan $p, User $u): bool
    {
        return $this->wewenang($p, $u) !== null;
    }

    // ---- transisi ---------------------------------------------------------------------------------------

    /** TU mengetik nomor surat pengajuan (penomoran mandiri) sebelum surat terbit. */
    public function aturNomor(Pengajuan $p, User $oleh, ?string $nomor): Pengajuan
    {
        $this->wajib($oleh->adalahAdmin(), 'Hanya Admin TU yang dapat mengisi nomor surat.');
        $surat = $p->surat;
        if (! $surat || in_array($surat->status, ['ditandatangani', 'batal'], true)) {
            throw ValidationException::withMessages(['nomor_manual' => 'Surat belum disusun atau sudah terbit; nomor tidak dapat diubah.']);
        }
        $nomor = NomorManual::bersihkan($nomor);
        $lengkap = $nomor === null ? null : NomorManual::periksa('nomor_manual', $nomor, $surat);
        $surat->update(['nomor_manual' => $nomor]);
        LogAktivitas::catat('nomor_manual', $nomor ? "Mengisi nomor urut surat manual {$p->kode}: {$nomor} ({$lengkap})" : "Mengosongkan nomor urut manual {$p->kode}", $surat, [], $oleh->id);

        return $p->refresh();
    }

    public function verifikasi(Pengajuan $p, User $oleh, ?string $catatan = null, ?string $nomor = null): Pengajuan
    {
        $this->wajib($this->bolehVerifikasi($p, $oleh), 'Anda tidak berwenang memverifikasi pengajuan ini.');
        $nomor = NomorManual::bersihkan($nomor);
        if ($nomor === null && NomorManual::wajib() && NomorManual::bersihkan($p->surat?->nomor_manual) === null) {
            throw ValidationException::withMessages(['nomor_surat' => 'Penomoran manual: isi nomor urut surat sebelum memverifikasi.']);
        }
        if ($nomor !== null) {
            NomorManual::periksa('nomor_surat', $nomor, $p->surat, $p->jenis);
        }

        return DB::transaction(function () use ($p, $oleh, $catatan, $nomor) {
            $this->putuskan($this->berjalan($p), $oleh, 'disetujui', $catatan);
            $p->update(['diverifikasi_oleh' => $oleh->id, 'diverifikasi_pada' => now()]);
            $surat = $p->surat ?? $this->penyusun->buatSuratDariPengajuan($p);
            if ($nomor !== null) {
                $surat->update(['nomor_manual' => $nomor]);
            }

            $this->teruskan($p, $surat, $oleh);
            LogAktivitas::catat('pengajuan_verifikasi', "Memverifikasi {$p->kode}", $p, [], $oleh->id);
            Notifikator::kirim($p->pemohon, 'Pengajuan diverifikasi', "{$p->jenis->nama} ({$p->kode}) telah diverifikasi.", route('pengajuan.show', $p));

            return $p->refresh();
        });
    }

    public function paraf(Pengajuan $p, User $oleh, ?string $catatan = null): Pengajuan
    {
        $this->wajib($this->bolehParaf($p, $oleh), 'Anda tidak berwenang memaraf pengajuan ini.');

        return DB::transaction(function () use ($p, $oleh, $catatan) {
            $this->putuskan($this->berjalan($p), $oleh, 'disetujui', $catatan);
            $this->teruskan($p, $p->surat, $oleh);
            LogAktivitas::catat('pengajuan_paraf', "Memaraf {$p->kode}", $p, [], $oleh->id);

            return $p->refresh();
        });
    }

    public function tandatangani(Pengajuan $p, User $oleh): Pengajuan
    {
        $this->wajib($this->bolehTandatangan($p, $oleh), 'Anda bukan penandatangan yang berwenang untuk pengajuan ini.');
        if (NomorManual::wajib() && NomorManual::bersihkan($p->surat?->nomor_manual) === null) {
            throw ValidationException::withMessages(['nomor_manual' => 'Penomoran manual: nomor urut surat belum diisi oleh TU.']);
        }

        return DB::transaction(function () use ($p, $oleh) {
            $surat = $this->tte->tandatangani($p->surat, $oleh);
            $this->putuskan($this->berjalan($p), $oleh, 'disetujui', null);
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
            $this->putuskan($this->berjalan($p), $oleh, 'ditolak', $alasan);
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
        $this->wajib(in_array($this->wewenang($p, $oleh), ['paraf', 'ttd'], true), 'Anda tidak berwenang mengembalikan pengajuan ini.');
        if (trim($catatan) === '') {
            throw ValidationException::withMessages(['catatan' => 'Catatan revisi wajib diisi.']);
        }

        return DB::transaction(function () use ($p, $oleh, $catatan) {
            $p->persetujuan()->where('tahap', '!=', 'verifikasi')->update(['status' => 'menunggu', 'user_id' => null, 'diputuskan_pada' => null]);
            $p->persetujuan()->where('tahap', 'verifikasi')->update(['status' => 'menunggu', 'diputuskan_pada' => null]);
            $p->persetujuan()->where('tahap', 'verifikasi')->first()?->update(['status' => 'revisi', 'catatan' => $oleh->nama.': '.$catatan]);
            $p->update(['status' => S::Diajukan, 'catatan' => $catatan]);
            $p->surat?->update(['status' => 'menunggu_paraf']);
            Notifikator::kirim($this->verifikator($p), 'Pengajuan dikembalikan untuk revisi', "{$p->kode}: {$catatan}", route('pengajuan.show', $p), 'peringatan');

            return $p->refresh();
        });
    }

    // ---- util -------------------------------------------------------------------------------------------

    /** Setelah satu tahap selesai: terbitkan langsung (tanpa tahap tersisa), atau teruskan ke tahap berikutnya sambil menyesuaikan status. */
    private function teruskan(Pengajuan $p, $surat, User $oleh): void
    {
        $berikut = $this->berjalan($p);
        if (! $berikut) {
            // Tanpa QR + tanpa persetujuan: setelah verifikasi surat langsung terbit (nomor + PDF) untuk dicetak.
            $surat = $this->tte->terbitkanLangsung($surat->refresh(), $oleh);
            $p->update(['status' => S::Ditandatangani]);
            Notifikator::kirim($p->pemohon, 'Surat Anda telah terbit', "{$p->jenis->nama} bernomor {$surat->nomor} sudah terbit dan dapat diunduh untuk dicetak.", route('pengajuan.show', $p), 'sukses');
        } elseif ($berikut->tahap === 'ttd') {
            $this->siapTtd($p, $surat, $berikut);
        } elseif ($berikut->tahap === 'paraf') {
            $p->update(['status' => S::Diverifikasi]);
            Notifikator::kirim($this->penerima($berikut->peran, $p), 'Menunggu paraf: '.$p->jenis->nama,
                "Pengajuan {$p->kode} menunggu paraf Anda.", route('persetujuan.show', $p), 'tugas');
        } else {
            $p->update(['status' => S::Diajukan]);
            Notifikator::kirim($this->penerima($berikut->peran, $p), 'Pengajuan menunggu verifikasi: '.$p->jenis->nama,
                "Pengajuan {$p->kode} menunggu verifikasi Anda.", route('pengajuan.show', $p), 'tugas');
        }
    }

    private function siapTtd(Pengajuan $p, $surat, Persetujuan $tahap): void
    {
        $p->update(['status' => S::Disetujui]);
        $surat->update(['status' => 'menunggu_ttd']);
        $pejabat = ($tahap->jabatan ?? $p->jenis->penandatanganJabatan)?->pejabat;
        if ($pejabat) {
            Notifikator::kirim($pejabat, 'Menunggu tanda tangan: '.$p->jenis->nama,
                "Surat {$p->kode} siap ditandatangani.", route('persetujuan.show', $p), 'tugas');
        }
    }

    private function putuskan(?Persetujuan $tahap, User $oleh, string $status, ?string $catatan): void
    {
        $tahap?->update(['user_id' => $oleh->id, 'status' => $status, 'catatan' => $catatan, 'diputuskan_pada' => now()]);
    }

    private function penerima(?string $peran, Pengajuan $p)
    {
        return Notifikator::penggunaPeran([$peran ?: 'admin_tu'], $peran === Peran::Kaprodi->value ? $p->pemohon->prodi_id : null);
    }

    /** Penerima pemberitahuan pengajuan baru: pemverifikasi tahap pertama. */
    private function verifikator(Pengajuan $p)
    {
        return $this->penerima($this->berjalan($p)?->peran, $p);
    }

    private function wajib(bool $syarat, string $pesan): void
    {
        if (! $syarat) {
            throw new \Illuminate\Auth\Access\AuthorizationException($pesan);
        }
    }
}
