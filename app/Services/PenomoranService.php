<?php

namespace App\Services;

use App\Models\KlasifikasiSurat;
use App\Models\Pengaturan;
use App\Models\Penomoran;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Nomor surat: [urut]/[klasifikasi]/FT-UMB/[bulan romawi]/[tahun].
 * Dipanggil HANYA saat surat ditandatangani. Seluruh pembacaan+penambahan
 * counter berlangsung dalam transaksi (SQLite: BEGIN IMMEDIATE, lihat config/database.php)
 * sehingga dua penandatanganan bersamaan tidak pernah mendapat nomor yang sama.
 * Counter hanya bertambah; nomor surat yang dibatalkan tidak dipakai ulang.
 */
class PenomoranService
{
    private const ROMAWI = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

    public function terbitkan(KlasifikasiSurat $klasifikasi, CarbonInterface $tanggal): string
    {
        return DB::transaction(function () use ($klasifikasi, $tanggal) {
            $baris = Penomoran::where(['klasifikasi_id' => $klasifikasi->id, 'tahun' => $tanggal->year])->lockForUpdate()->first()
                ?? Penomoran::create(['klasifikasi_id' => $klasifikasi->id, 'tahun' => $tanggal->year, 'nomor_terakhir' => 0]);

            $baris->increment('nomor_terakhir');

            return $this->format((int) $baris->fresh()->nomor_terakhir, $klasifikasi->kode, $tanggal);
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
    public function selaraskan(KlasifikasiSurat $klasifikasi, CarbonInterface $tanggal, int $urut): void
    {
        DB::transaction(function () use ($klasifikasi, $tanggal, $urut) {
            $baris = Penomoran::where(['klasifikasi_id' => $klasifikasi->id, 'tahun' => $tanggal->year])->lockForUpdate()->first()
                ?? Penomoran::create(['klasifikasi_id' => $klasifikasi->id, 'tahun' => $tanggal->year, 'nomor_terakhir' => 0]);
            if ($urut > $baris->nomor_terakhir) {
                $baris->update(['nomor_terakhir' => $urut]);
            }
        });
    }

    public function format(int $urut, string $kodeKlasifikasi, CarbonInterface $tanggal): string
    {
        $pola = Pengaturan::ambil('format_nomor', '{urut}/{klasifikasi}/FT-UMB/{bulan_romawi}/{tahun}');
        $panjang = (int) Pengaturan::ambil('panjang_urut', 3);

        return strtr($pola, [
            '{urut}' => str_pad((string) $urut, $panjang, '0', STR_PAD_LEFT),
            '{klasifikasi}' => $kodeKlasifikasi,
            '{bulan_romawi}' => self::ROMAWI[$tanggal->month],
            '{bulan}' => str_pad((string) $tanggal->month, 2, '0', STR_PAD_LEFT),
            '{tahun}' => (string) $tanggal->year,
        ]);
    }
}
