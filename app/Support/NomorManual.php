<?php

namespace App\Support;

use App\Models\KlasifikasiSurat;
use App\Models\Pengaturan;
use App\Models\Surat;
use App\Services\PenomoranService;
use Carbon\CarbonInterface;

/**
 * Penomoran mandiri: TU mengetik NOMOR SURAT sebelum surat terbit — boleh angka urutnya saja (mis. 009; bagian lain
 * disusun otomatis menurut Pengaturan → Format Nomor) atau nomor lengkap yang bebas diedit
 * (mis. 9/KET/II.3.AU/UMB-06.2/F/2026). Nomor yang sudah terbit tidak dapat diubah.
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

    /** Karakter yang diizinkan pada nomor surat yang diketik. */
    public const POLA = '/^[\sA-Za-z0-9.\/\-_()]{1,100}$/';

    /** Isian nomor yang diketik: spasi dibuang; kosong menjadi null. */
    public static function bersihkan(?string $urut): ?string
    {
        $n = preg_replace('/\s+/u', '', (string) $urut);

        return $n === '' ? null : $n;
    }

    /**
     * Nomor lengkap dari isian TU: isian berupa angka diperluas menurut pola penomoran (mis. 009 → 009/II.3.AU/UMB-06/A/2026);
     * isian lain dianggap nomor lengkap dan dipakai apa adanya.
     */
    public static function lengkapUntuk(string $isian, ?KlasifikasiSurat $klasifikasi, ?CarbonInterface $tanggal = null, ?string $unit = null, ?string $kekhususan = null): ?string
    {
        if (! ctype_digit($isian)) {
            return $isian;
        }
        if (! $klasifikasi) {
            return null;
        }

        return app(PenomoranService::class)->format((int) $isian, $klasifikasi->kode, $tanggal ?? now(), $unit, $kekhususan);
    }

    /** Seperti lengkapUntuk, memakai unit kerja dan kekhususan dari surat. */
    public static function lengkapSurat(string $isian, Surat $s): ?string
    {
        [$unit, $kekhususan] = PenomoranService::bagianSurat($s);

        return self::lengkapUntuk($isian, $s->klasifikasi, $s->tgl_surat, $unit, $kekhususan);
    }

    /** Nomor lengkap dari isian nomor yang tersimpan pada surat (pratinjau sebelum terbit). */
    public static function lengkap(Surat $s): ?string
    {
        $isian = self::bersihkan($s->nomor_manual);

        return $isian === null ? null : self::lengkapSurat($isian, $s);
    }

    /** Nomor otomatis berikutnya untuk surat ini (usulan yang bisa diedit TU); null bila klasifikasi belum ada. */
    public static function usulan(Surat $s): ?string
    {
        if (! $s->klasifikasi) {
            return null;
        }
        [$unit, $kekhususan] = PenomoranService::bagianSurat($s);

        return app(PenomoranService::class)->usulan($s->klasifikasi, $s->tgl_surat ?? now(), $unit, $kekhususan);
    }

    /** Contoh nomor menurut pola yang berlaku, untuk petunjuk pada formulir. */
    public static function contoh(): string
    {
        return app(PenomoranService::class)->format(9, 'A', now(), null, 'KET');
    }

    /** Nomor lengkap sudah dipakai: surat yang sudah terbit, atau draf lain yang nomor urutnya menghasilkan nomor sama. */
    public static function bentrok(string $nomorLengkap, ?int $kecualiSuratId = null): bool
    {
        $k = mb_strtolower($nomorLengkap);
        if (Surat::query()->when($kecualiSuratId, fn ($q) => $q->where('id', '!=', $kecualiSuratId))->whereRaw('lower(nomor) = ?', [$k])->exists()) {
            return true;
        }
        if (\App\Models\Pembukuan::where('arah', 'keluar')->whereRaw('lower(nomor) = ?', [$k])->exists()) {   // sudah tercatat di pembukuan
            return true;
        }

        return Surat::query()->with('klasifikasi', 'jabatan.prodi', 'jenis')->whereNull('nomor')->whereNotNull('nomor_manual')
            ->when($kecualiSuratId, fn ($q) => $q->where('id', '!=', $kecualiSuratId))->get()
            ->contains(fn (Surat $s) => mb_strtolower((string) self::lengkap($s)) === $k);
    }

    /** @return array<int, mixed> aturan validasi isian nomor urut (angka saja, tidak boleh menghasilkan nomor kembar) */
    public static function aturan(?int $kecualiSuratId = null, ?int $klasifikasiId = null, ?string $tanggal = null, bool $wajib = false): array
    {
        return [$wajib ? 'required' : 'nullable', 'string', 'regex:'.self::POLA,
            function ($atribut, $nilai, $gagal) use ($kecualiSuratId, $klasifikasiId, $tanggal) {
                $urut = self::bersihkan($nilai);
                $lengkap = $urut === null ? null : self::lengkapUntuk($urut, $klasifikasiId ? KlasifikasiSurat::find($klasifikasiId) : null, $tanggal ? \Carbon\Carbon::parse($tanggal) : null);
                if ($lengkap && self::bentrok($lengkap, $kecualiSuratId)) {
                    $gagal("Nomor {$lengkap} sudah dipakai surat lain.");
                }
            }];
    }

    public const PESAN = [
        'nomor_manual.regex' => 'Isi angka urut (contoh: 009) atau nomor surat lengkap; hanya huruf, angka, dan . / - _ ( ).',
        'nomor_manual.required' => 'Nomor surat wajib diisi TU (penomoran manual).',
        'nomor_surat.regex' => 'Isi angka urut (contoh: 009) atau nomor surat lengkap; hanya huruf, angka, dan . / - _ ( ).',
    ];
}
