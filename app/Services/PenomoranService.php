<?php

namespace App\Services;

use App\Models\Jabatan;
use App\Models\KlasifikasiSurat;
use App\Models\Pengaturan;
use App\Models\Penomoran;
use App\Models\Surat;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Nomor surat menurut Pedoman Tata Naskah Dinas UM Buton:
 * [urut]/[kekhususan]/II.3.AU/[unit kerja]/[pokok masalah]/[tahun], mis. 7/EDR/II.3.AU/UMB-06/C/2025
 * (kekhususan dihilangkan untuk surat biasa). Urutan berjalan per unit kerja per tahun takwim.
 * Dipanggil HANYA saat surat ditandatangani. Seluruh pembacaan+penambahan
 * counter berlangsung dalam transaksi (SQLite: BEGIN IMMEDIATE, lihat config/database.php)
 * sehingga dua penandatanganan bersamaan tidak pernah mendapat nomor yang sama.
 * Counter hanya bertambah; nomor surat yang dibatalkan tidak dipakai ulang.
 */
class PenomoranService
{
    private const ROMAWI = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

    /** Kode unit kerja fakultas (Pengaturan), dipakai bila penandatangan bukan program studi. */
    public static function unitFakultas(): string
    {
        return (string) (Pengaturan::ambil('kode_unit_fakultas') ?: 'UMB-06');
    }

    /** Unit kerja penerbit surat: program studi penandatangan (bila punya kode unit), selain itu fakultas. */
    public static function unitUntuk(?Jabatan $jabatan): string
    {
        return $jabatan?->prodi?->kode_unit ?: self::unitFakultas();
    }

    /** Unit kerja dan kode kekhususan dari sebuah surat. @return array{0: string, 1: ?string} */
    public static function bagianSurat(Surat $s): array
    {
        return [self::unitUntuk($s->jabatan), $s->jenis?->kekhususan ?: null];
    }

    public function terbitkan(KlasifikasiSurat $klasifikasi, CarbonInterface $tanggal, ?string $unit = null, ?string $kekhususan = null): string
    {
        $unit ??= self::unitFakultas();

        return DB::transaction(function () use ($klasifikasi, $tanggal, $unit, $kekhususan) {
            $baris = Penomoran::where(['unit' => $unit, 'tahun' => $tanggal->year])->lockForUpdate()->first()
                ?? Penomoran::create(['unit' => $unit, 'tahun' => $tanggal->year, 'nomor_terakhir' => 0]);

            $baris->increment('nomor_terakhir');

            return $this->format((int) $baris->fresh()->nomor_terakhir, $klasifikasi->kode, $tanggal, $unit, $kekhususan);
        });
    }

    /**
     * Nomor agenda surat masuk: AGD-{tahun}/{bulan romawi}/{urut}. Counter per tahun, dalam transaksi
     * (IMMEDIATE pada SQLite) sehingga tidak pernah ganda.
     */
    public function agenda(CarbonInterface $tanggal): string
    {
        return DB::transaction(function () use ($tanggal) {
            $baris = DB::table('nomor_agenda')->where('tahun', $tanggal->year)->lockForUpdate()->first();
            if (! $baris) {
                DB::table('nomor_agenda')->insert(['tahun' => $tanggal->year, 'nomor_terakhir' => 0, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('nomor_agenda')->where('tahun', $tanggal->year)->increment('nomor_terakhir', 1, ['updated_at' => now()]);
            $urut = (int) DB::table('nomor_agenda')->where('tahun', $tanggal->year)->value('nomor_terakhir');

            return strtr(Pengaturan::ambil('format_agenda', 'AGD-{tahun}/{bulan_romawi}/{urut}'), [
                '{urut}' => str_pad((string) $urut, (int) Pengaturan::ambil('panjang_agenda', 4), '0', STR_PAD_LEFT),
                '{bulan_romawi}' => self::ROMAWI[$tanggal->month],
                '{bulan}' => str_pad((string) $tanggal->month, 2, '0', STR_PAD_LEFT),
                '{tahun}' => (string) $tanggal->year,
            ]);
        });
    }

    /** Nomor urut yang diketik TU menjadi acuan: penghitung otomatis tidak pernah lebih kecil dari nomor manual yang sudah terbit. */
    public function selaraskan(?string $unit, CarbonInterface $tanggal, int $urut): void
    {
        $unit ??= self::unitFakultas();
        DB::transaction(function () use ($unit, $tanggal, $urut) {
            $baris = Penomoran::where(['unit' => $unit, 'tahun' => $tanggal->year])->lockForUpdate()->first()
                ?? Penomoran::create(['unit' => $unit, 'tahun' => $tanggal->year, 'nomor_terakhir' => 0]);
            if ($urut > $baris->nomor_terakhir) {
                $baris->update(['nomor_terakhir' => $urut]);
            }
        });
    }

    /** Nomor yang akan terbit berikutnya untuk unit/tahun tersebut (tanpa menaikkan penghitung); dasar usulan pada formulir TU. */
    public function usulan(KlasifikasiSurat $klasifikasi, CarbonInterface $tanggal, ?string $unit = null, ?string $kekhususan = null): string
    {
        $unit ??= self::unitFakultas();
        $terakhir = (int) Penomoran::where(['unit' => $unit, 'tahun' => $tanggal->year])->value('nomor_terakhir');

        return $this->format($terakhir + 1, $klasifikasi->kode, $tanggal, $unit, $kekhususan);
    }

    /**
     * Menguraikan nomor menurut pola penomoran (Pengaturan → Format Nomor); null bila tidak cocok.
     *
     * @return array{urut: int, klasifikasi: ?string, unit: ?string, kekhususan: ?string, tahun: ?int}|null
     */
    public function uraikan(string $nomor): ?array
    {
        $pola = (string) Pengaturan::ambil('format_nomor', '{urut}/{kekhususan}/II.3.AU/{unit}/{klasifikasi}/{tahun}');
        $re = strtr(preg_quote($pola, '#'), [
            '\{urut\}' => '(?P<urut>\d+)', '\{kekhususan\}/' => '(?:(?P<kekhususan>[A-Za-z]{2,8})/)?', '\{kekhususan\}' => '(?P<kekhususan>[A-Za-z]*)',
            '\{unit\}' => '(?P<unit>[A-Za-z0-9.\-]+)', '\{klasifikasi\}' => '(?P<klasifikasi>.+?)', '\{bulan_romawi\}' => '(?P<romawi>[IVXivx]+)',
            '\{bulan\}' => '(?P<bulan>\d{1,2})', '\{tahun\}' => '(?P<tahun>\d{4})',
        ]);
        if (! @preg_match('#^'.$re.'$#u', trim($nomor), $m) || ! isset($m['urut'])) {
            return null;
        }

        return [
            'urut' => (int) $m['urut'], 'klasifikasi' => $m['klasifikasi'] ?? null, 'unit' => ($m['unit'] ?? '') !== '' ? $m['unit'] : null,
            'kekhususan' => ($m['kekhususan'] ?? '') !== '' ? $m['kekhususan'] : null, 'tahun' => isset($m['tahun']) ? (int) $m['tahun'] : null,
        ];
    }

    public function format(int $urut, string $kodeKlasifikasi, CarbonInterface $tanggal, ?string $unit = null, ?string $kekhususan = null): string
    {
        $pola = Pengaturan::ambil('format_nomor', '{urut}/{kekhususan}/II.3.AU/{unit}/{klasifikasi}/{tahun}');
        $panjang = (int) Pengaturan::ambil('panjang_urut', 3);

        $nomor = strtr($pola, [
            '{urut}' => str_pad((string) $urut, $panjang, '0', STR_PAD_LEFT),
            '{kekhususan}' => (string) $kekhususan,
            '{klasifikasi}' => $kodeKlasifikasi,
            '{unit}' => $unit ?? self::unitFakultas(),
            '{bulan_romawi}' => self::ROMAWI[$tanggal->month],
            '{bulan}' => str_pad((string) $tanggal->month, 2, '0', STR_PAD_LEFT),
            '{tahun}' => (string) $tanggal->year,
        ]);

        return preg_replace('#/{2,}#', '/', $nomor);   // tanpa kekhususan: bagian itu hilang, bukan "//"
    }
}
