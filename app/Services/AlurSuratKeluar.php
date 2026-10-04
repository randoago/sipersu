<?php

namespace App\Services;

use App\Enums\Peran;
use App\Models\Jabatan;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Draf → menunggu paraf (opsional) → menunggu TTD → ditandatangani (atau batal). Nomor terbit hanya saat TTD. */
class AlurSuratKeluar
{
    public function __construct(private PenyusunSurat $penyusun, private TandaTanganService $tte)
    {
    }

    public function bolehMembuat(User $u): bool
    {
        return $u->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminTu->value, Peran::Dekan->value, Peran::WakilDekan->value, Peran::Kaprodi->value, Peran::DosenTendik->value]);
    }

    public function bolehMelihat(Surat $s, User $u): bool
    {
        return $s->dibuat_oleh === $u->id || $u->adalahAdmin() || $u->hasAnyRole([Peran::Dekan->value, Peran::WakilDekan->value])
            || $s->jabatan?->user_id === $u->id;
    }

    public function bolehMengubah(Surat $s, User $u): bool
    {
        return $s->status === 'draf' && ($s->dibuat_oleh === $u->id || $u->adalahAdmin());
    }

    public function bolehAjukan(Surat $s, User $u): bool
    {
        return $this->bolehMengubah($s, $u);
    }

    public function bolehParaf(Surat $s, User $u): bool
    {
        $peran = $s->data['paraf_role'] ?? null;

        return $s->status === 'menunggu_paraf' && $peran && ($u->hasRole($peran) || $u->hasRole(Peran::SuperAdmin->value));
    }

    public function bolehTandatangan(Surat $s, User $u): bool
    {
        return $s->status === 'menunggu_ttd' && $s->jabatan?->user_id === $u->id;
    }

    public function bolehBatal(Surat $s, User $u): bool
    {
        return $s->status === 'ditandatangani' && ($u->adalahAdmin() || $s->penandatangan_id === $u->id);
    }

    /** Tanggal yang dipilih = tanggal surat; kosong atau sama dengan hari ini = ikut hari penandatanganan (disimpan null). */
    public static function tanggalPilihan(?string $tgl): ?string
    {
        if (! $tgl || $tgl === now()->toDateString()) {
            return null;
        }

        return $tgl;
    }

    public function simpan(User $pembuat, array $d, ?Surat $surat = null): Surat
    {
        $isi = $this->penyusun->suratUmum($d);
        $atribut = [
            'arah' => 'keluar', 'klasifikasi_id' => $d['klasifikasi_id'], 'perihal' => $d['perihal'], 'sifat' => $d['sifat'],
            'asal_tujuan' => preg_split('/\R/', trim($d['tujuan']))[0], 'isi_html' => $isi, 'jabatan_id' => $d['jabatan_id'], 'mode_ttd' => $d['mode_ttd'] ?? 'qr',
            'tgl_surat' => self::tanggalPilihan($d['tanggal_surat'] ?? null),
            'data' => ['umum' => true, 'tujuan' => $d['tujuan'], 'lampiran' => $d['lampiran'] ?? '', 'isi' => $d['isi'], 'salam' => ! empty($d['salam']), 'paraf_role' => $d['paraf_role'] ?: null],
        ];
        if ($surat) {
            $surat->update($atribut);
        } else {
            $surat = Surat::create($atribut + ['status' => 'draf', 'dibuat_oleh' => $pembuat->id]);
        }
        LogAktivitas::catat('surat_keluar_simpan', 'Menyimpan draf surat keluar: '.$d['perihal'], $surat, [], $pembuat->id);

        return $surat;
    }

    /** Surat dari format TU: isian dirender ke templat; klasifikasi, penandatangan, paraf, dan bentuk QR mengikuti format. */
    public function simpanDariFormat(User $pembuat, \App\Models\JenisSurat $jenis, array $isian, ?Surat $surat = null, ?string $tanggalSurat = null): Surat
    {
        $h = $this->penyusun->dariFormat($jenis, $isian, $pembuat);
        $atribut = [
            'arah' => 'keluar', 'klasifikasi_id' => $jenis->klasifikasi_id, 'perihal' => $h['perihal'], 'sifat' => 'biasa',
            'asal_tujuan' => preg_split('/\R/', trim(strip_tags($h['tujuan'])))[0] ?: '-', 'isi_html' => $h['isi'], 'data' => $h['data'],
            'jabatan_id' => $jenis->penandatangan_jabatan_id, 'mode_ttd' => $jenis->mode_ttd ?? 'qr', 'jenis_surat_id' => $jenis->id, 'gaya_tanggal' => $jenis->gaya_tanggal ?? 'dikeluarkan', 'tgl_surat' => self::tanggalPilihan($tanggalSurat),
        ];
        if ($surat) {
            $surat->update($atribut);
        } else {
            $surat = Surat::create($atribut + ['status' => 'draf', 'dibuat_oleh' => $pembuat->id]);
        }
        LogAktivitas::catat('surat_keluar_simpan', "Menyimpan draf {$jenis->nama}: {$h['perihal']}", $surat, [], $pembuat->id);

        return $surat;
    }

    public function ajukan(Surat $s, User $oleh): Surat
    {
        $this->wajib($this->bolehAjukan($s, $oleh), 'Anda tidak berwenang mengajukan surat ini.');

        return DB::transaction(function () use ($s, $oleh) {
            $s->persetujuan()->delete();
            $paraf = $s->data['paraf_role'] ?? null;
            if ($paraf) {
                $s->persetujuan()->create(['tahap' => 'paraf', 'urutan' => 1, 'peran' => $paraf]);
            }
            $s->persetujuan()->create(['tahap' => 'ttd', 'urutan' => 2, 'peran' => 'penandatangan', 'jabatan_id' => $s->jabatan_id]);
            $s->update(['status' => $paraf ? 'menunggu_paraf' : 'menunggu_ttd']);
            LogAktivitas::catat('surat_keluar_ajukan', "Mengajukan surat keluar: {$s->perihal}", $s, [], $oleh->id);
            $this->beritahuBerikut($s);

            return $s->refresh();
        });
    }

    public function paraf(Surat $s, User $oleh, ?string $catatan): Surat
    {
        $this->wajib($this->bolehParaf($s, $oleh), 'Anda tidak berwenang memaraf surat ini.');

        return DB::transaction(function () use ($s, $oleh, $catatan) {
            $s->persetujuan()->where('tahap', 'paraf')->update(['user_id' => $oleh->id, 'status' => 'disetujui', 'catatan' => $catatan, 'diputuskan_pada' => now()]);
            $s->update(['status' => 'menunggu_ttd']);
            LogAktivitas::catat('surat_keluar_paraf', "Memaraf surat: {$s->perihal}", $s, [], $oleh->id);
            $this->beritahuBerikut($s);

            return $s->refresh();
        });
    }

    public function tandatangani(Surat $s, User $oleh): Surat
    {
        $this->wajib($this->bolehTandatangan($s, $oleh), 'Anda bukan penandatangan yang berwenang untuk surat ini.');

        return DB::transaction(function () use ($s, $oleh) {
            $surat = $this->tte->tandatangani($s, $oleh);
            $surat->persetujuan()->where('tahap', 'ttd')->update(['user_id' => $oleh->id, 'status' => 'disetujui', 'diputuskan_pada' => now()]);
            if ($surat->pembuat) {
                Notifikator::kirim($surat->pembuat, 'Surat telah ditandatangani', "Surat \"{$surat->perihal}\" bernomor {$surat->nomor} sudah terbit.", route('surat-keluar.show', $surat), 'sukses');
            }

            return $surat;
        });
    }

    /** Menolak / mengembalikan ke draf dengan catatan. */
    public function kembalikan(Surat $s, User $oleh, string $catatan): Surat
    {
        $this->wajib($this->bolehParaf($s, $oleh) || $this->bolehTandatangan($s, $oleh), 'Anda tidak berwenang mengembalikan surat ini.');
        if (trim($catatan) === '') {
            throw ValidationException::withMessages(['catatan' => 'Catatan wajib diisi.']);
        }
        $s->persetujuan()->update(['status' => 'menunggu', 'user_id' => null, 'diputuskan_pada' => null]);
        $s->update(['status' => 'draf']);
        $s->persetujuan()->delete();
        LogAktivitas::catat('surat_keluar_kembali', "Mengembalikan surat: {$s->perihal}", $s, ['catatan' => $catatan], $oleh->id);
        if ($s->pembuat) {
            Notifikator::kirim($s->pembuat, 'Surat dikembalikan untuk revisi', $catatan, route('surat-keluar.show', $s), 'peringatan');
        }

        return $s->refresh();
    }

    /** Surat yang sudah terbit dibatalkan: nomor TIDAK dipakai ulang; QR menampilkan "TIDAK BERLAKU". */
    public function batalkan(Surat $s, User $oleh, string $alasan): Surat
    {
        $this->wajib($this->bolehBatal($s, $oleh), 'Anda tidak berwenang membatalkan surat ini.');
        if (trim($alasan) === '') {
            throw ValidationException::withMessages(['alasan' => 'Alasan pembatalan wajib diisi.']);
        }
        $s->update(['status' => 'batal', 'dibatalkan_pada' => now(), 'alasan_batal' => $alasan]);
        LogAktivitas::catat('surat_batal', "Membatalkan surat {$s->nomor}", $s, ['alasan' => $alasan], $oleh->id);

        return $s->refresh();
    }

    private function beritahuBerikut(Surat $s): void
    {
        if ($s->status === 'menunggu_paraf') {
            Notifikator::kirim(Notifikator::penggunaPeran([$s->data['paraf_role']]), 'Menunggu paraf: '.$s->perihal, 'Surat keluar menunggu paraf Anda.', route('surat-keluar.show', $s), 'tugas');
        } elseif ($s->status === 'menunggu_ttd' && $s->jabatan?->pejabat) {
            Notifikator::kirim($s->jabatan->pejabat, 'Menunggu tanda tangan: '.$s->perihal, 'Surat keluar siap ditandatangani.', route('surat-keluar.show', $s), 'tugas');
        }
    }

    private function wajib(bool $syarat, string $pesan): void
    {
        if (! $syarat) {
            throw new AuthorizationException($pesan);
        }
    }
}
