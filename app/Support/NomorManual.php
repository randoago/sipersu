<?php

namespace App\Support;

use App\Models\KlasifikasiSurat;
use App\Models\Pengaturan;
use App\Models\Surat;
use App\Services\PenomoranService;
use Carbon\CarbonInterface;

/**
 * Penomoran mandiri: TU hanya mengetik NOMOR URUT depan (mis. 009). Bagian lain nomor surat
 * (klasifikasi, FT-UMB, bulan romawi, tahun) tetap mengikuti pola yang diatur di Pengaturan → Format Nomor.
 */
class NomorManual
{
    /** Mode penomoran: "otomatis" (bawaan) atau "manual" (TU wajib mengisi nomor urut sebelum surat diajukan/diterbitkan). */
    public static function mode(): string
    {
        return Pengaturan::ambil('penomoran_mode', 'otomatis') === 'manual' ? 'manual' : 'otomatis';
    }

    public static function wajib(): bool
    {
        return self::mode() === 'manual';
    }

    /** Nomor urut yang diketik: spasi dibuang; kosong menjadi null. */
    public static function bersihkan(?string $urut): ?string
    {
        $n = preg_replace('/\s+/u', '', (string) $urut);

        return $n === '' ? null : $n;
    }

    /** Nomor lengkap menurut pola penomoran, mis. 009 → 009/II.3.AU/FT-UMB/X/2026. */
    public static function lengkapUntuk(string $urut, ?KlasifikasiSurat $klasifikasi, ?CarbonInterface $tanggal = null): ?string
    {
        if (! $klasifikasi || ! ctype_digit($urut)) {
            return null;
        }

        return app(PenomoranService::class)->format((int) $urut, $klasifikasi->kode, $tanggal ?? now());
    }

    /** Nomor lengkap dari nomor urut yang tersimpan pada surat (pratinjau sebelum terbit). */
    public static function lengkap(Surat $s): ?string
    {
        $urut = self::bersihkan($s->nomor_manual);

        return $urut === null ? null : self::lengkapUntuk($urut, $s->klasifikasi, $s->tgl_surat);
    }

    /** Contoh nomor menurut pola yang berlaku, untuk petunjuk pada formulir. */
    public static function contoh(): string
    {
        return app(PenomoranService::class)->format(9, 'II.3.AU', now());
    }

    /** Nomor lengkap sudah dipakai: surat yang sudah terbit, atau draf lain yang nomor urutnya menghasilkan nomor sama. */
    public static function bentrok(string $nomorLengkap, ?int $kecualiSuratId = null): bool
    {
        $k = mb_strtolower($nomorLengkap);
        if (Surat::query()->when($kecualiSuratId, fn ($q) => $q->where('id', '!=', $kecualiSuratId))->whereRaw('lower(nomor) = ?', [$k])->exists()) {
            return true;
        }

        return Surat::query()->with('klasifikasi')->whereNull('nomor')->whereNotNull('nomor_manual')
            ->when($kecualiSuratId, fn ($q) => $q->where('id', '!=', $kecualiSuratId))->get()
            ->contains(fn (Surat $s) => mb_strtolower((string) self::lengkap($s)) === $k);
    }

    /** @return array<int, mixed> aturan validasi isian nomor urut (angka saja, tidak boleh menghasilkan nomor kembar) */
    public static function aturan(?int $kecualiSuratId = null, ?int $klasifikasiId = null, ?string $tanggal = null, bool $wajib = false): array
    {
        return [$wajib ? 'required' : 'nullable', 'string', 'regex:/^\s*\d{1,6}\s*$/',
            function ($atribut, $nilai, $gagal) use ($kecualiSuratId, $klasifikasiId, $tanggal) {
                $urut = self::bersihkan($nilai);
                $lengkap = $urut === null ? null : self::lengkapUntuk($urut, $klasifikasiId ? KlasifikasiSurat::find($klasifikasiId) : null, $tanggal ? \Carbon\Carbon::parse($tanggal) : null);
                if ($lengkap && self::bentrok($lengkap, $kecualiSuratId)) {
                    $gagal("Nomor {$lengkap} sudah dipakai surat lain.");
                }
            }];
    }

    public const PESAN = [
        'nomor_manual.regex' => 'Isi nomor urut saja, berupa angka (contoh: 009). Bagian lain nomor surat mengikuti aturan penomoran.',
        'nomor_manual.required' => 'Nomor urut surat wajib diisi TU (penomoran manual).',
        'nomor_surat.regex' => 'Isi nomor urut saja, berupa angka (contoh: 009). Bagian lain nomor surat mengikuti aturan penomoran.',
    ];
}
