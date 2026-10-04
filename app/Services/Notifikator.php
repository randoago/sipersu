<?php

namespace App\Services;

use App\Mail\NotifikasiMail;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/** Notifikasi dalam aplikasi + email (antrean "database", diproses oleh scheduler). */
class Notifikator
{
    public static function kirim(User|Collection|iterable $penerima, string $judul, ?string $isi = null, ?string $url = null, string $jenis = 'info'): void
    {
        $daftar = $penerima instanceof User ? collect([$penerima]) : collect($penerima);
        foreach ($daftar->unique('id') as $u) {
            Notifikasi::create(['user_id' => $u->id, 'judul' => $judul, 'isi' => $isi, 'url' => $url, 'jenis' => $jenis]);
            if ($u->email) {
                try {
                    Mail::to($u->email)->queue(new NotifikasiMail($judul, $isi, $url));
                } catch (\Throwable $e) {
                    report($e);   // gagal email tidak boleh menggagalkan alur surat
                }
            }
        }
    }

    /** Semua pengguna aktif dengan salah satu peran. */
    public static function penggunaPeran(array $peran, ?int $prodiId = null): Collection
    {
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', $peran))->where('aktif', true)
            ->when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))->get();
    }
}
