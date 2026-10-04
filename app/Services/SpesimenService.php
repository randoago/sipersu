<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Spesimen tanda tangan pejabat: "ttd" = tanda tangan saja; "stempel" = tanda tangan + stempel (dipakai surat ber-QR). */
class SpesimenService
{
    public const ATURAN = ['required', 'file', 'mimes:png,jpg,jpeg', 'max:2048', 'dimensions:min_width=100,min_height=50'];

    public const PESAN = [
        'spesimen.required' => 'Pilih berkas gambar spesimen.',
        'spesimen.mimes' => 'Spesimen harus berformat PNG atau JPG.',
        'spesimen.max' => 'Ukuran spesimen maksimal 2 MB.',
        'spesimen.dimensions' => 'Gambar terlalu kecil (minimal 100×50 piksel).',
    ];

    public static function kolom(?string $jenis): string
    {
        return $jenis === 'stempel' ? 'spesimen_stempel' : 'spesimen_ttd';
    }

    public static function label(?string $jenis): string
    {
        return $jenis === 'stempel' ? 'tanda tangan + stempel' : 'tanda tangan (tanpa stempel)';
    }

    /** Pemegang jabatan aktif atau berperan Dekan/Wakil Dekan/Kaprodi. */
    public static function adalahPejabat(User $u): bool
    {
        return $u->hasAnyRole(['dekan', 'wakil_dekan', 'kaprodi']) || $u->jabatanAktif()->exists();
    }

    public function simpan(User $target, ?string $jenis, UploadedFile $berkas, ?User $oleh = null): void
    {
        $kolom = self::kolom($jenis);
        if ($target->{$kolom}) {
            Storage::disk('local')->delete($target->{$kolom});
        }
        $ext = strtolower($berkas->guessExtension() ?: 'png');
        $path = $berkas->storeAs('spesimen', 'user-'.$target->id.'-'.($kolom === 'spesimen_stempel' ? 'stempel-' : '').bin2hex(random_bytes(4)).'.'.$ext, 'local');
        $target->update([$kolom => $path]);

        LogAktivitas::catat('spesimen_ttd', ($oleh && $oleh->id !== $target->id ? "Mengunggah spesimen ".self::label($jenis)." untuk {$target->namaLengkap()}" : 'Mengunggah spesimen '.self::label($jenis)), $target, ['jenis' => $jenis ?: 'ttd']);
    }

    public function hapus(User $target, ?string $jenis, ?User $oleh = null): void
    {
        $kolom = self::kolom($jenis);
        if ($target->{$kolom}) {
            Storage::disk('local')->delete($target->{$kolom});
            $target->update([$kolom => null]);
            LogAktivitas::catat('spesimen_hapus', "Menghapus spesimen ".self::label($jenis)." {$target->namaLengkap()}", $target, ['jenis' => $jenis ?: 'ttd']);
        }
    }
}
