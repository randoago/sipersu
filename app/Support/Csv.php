<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/** Pembaca CSV sederhana untuk impor: pemisah , ; Tab otomatis; UTF-8 (BOM atau tidak) atau Windows-1252. */
class Csv
{
    public static function normal(string $t): string
    {
        return trim(preg_replace('/_+/', '_', preg_replace('/[^a-z0-9]+/', '_', mb_strtolower(Str::ascii(trim($t))))), '_');
    }

    /**
     * @param array<string,string> $alias nama kolom ternormalisasi → nama baku
     * @param array<int,string> $wajib kolom baku yang harus ada
     * @return array{0: array<int,string>, 1: array<int, array>, 2: array<int,string>} header baku, baris data, kolom diabaikan
     */
    public static function baca(string $isi, array $alias, array $wajib, int $maksBaris): array
    {
        if (str_contains($isi, "\0")) {
            throw new RuntimeException('Berkas bukan teks CSV (tampaknya berkas biner/.xlsx). Simpan dari Excel sebagai "CSV UTF-8 (Comma delimited)".');
        }
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
        if (! mb_check_encoding($isi, 'UTF-8')) {
            $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
        }
        $isi = str_replace(["\r\n", "\r"], "\n", $isi);
        $barisPertama = strtok($isi, "\n") ?: '';
        $pemisah = collect([',' => substr_count($barisPertama, ','), ';' => substr_count($barisPertama, ';'), "\t" => substr_count($barisPertama, "\t")])->sortDesc()->keys()->first();

        $h = fopen('php://temp', 'r+');
        fwrite($h, $isi);
        rewind($h);
        $rows = [];
        while (($row = fgetcsv($h, 0, $pemisah, '"', '')) !== false) {
            if ($row === [null] || count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($h);

        if (count($rows) < 2) {
            throw new RuntimeException('Berkas kosong atau hanya berisi baris judul. Isi minimal satu baris data.');
        }
        $header = [];
        $diabaikan = [];
        foreach ($rows[0] as $k => $nama) {
            $baku = $alias[self::normal($nama)] ?? null;
            if (! $baku || in_array($baku, $header, true)) {
                $header[$k] = '_abaikan_'.$k;
                if (trim($nama) !== '') {
                    $diabaikan[] = trim($nama);
                }
            } else {
                $header[$k] = $baku;
            }
        }
        if ($hilang = array_diff($wajib, $header)) {
            throw new RuntimeException('Kolom wajib tidak ada pada baris judul: '.implode(', ', $hilang).'. Unduh templat CSV dan ikuti nama kolomnya.');
        }
        $data = array_slice($rows, 1);
        if (count($data) > $maksBaris) {
            throw new RuntimeException("Maksimal {$maksBaris} baris per berkas (berkas Anda ".count($data).' baris). Bagi menjadi beberapa berkas.');
        }

        return [$header, $data, $diabaikan];
    }

    public static function tanggal(string $t): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y'] as $f) {
            try {
                $c = Carbon::createFromFormat('!'.$f, $t);
                if ($c && $c->format($f) === $t) {
                    return $c->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /** CSV untuk Excel: BOM + CRLF. */
    public static function tulis(array $baris): string
    {
        $h = fopen('php://temp', 'r+');
        foreach ($baris as $b) {
            fputcsv($h, array_map(fn ($c) => self::amankan((string) $c), $b), ',', '"', '');
        }
        rewind($h);

        return "\xEF\xBB\xBF".str_replace("\n", "\r\n", stream_get_contents($h));
    }

    /** Cegah injeksi rumus spreadsheet pada sel yang diawali = + @ atau - diikuti huruf/simbol (angka negatif dan "-" tunggal aman). */
    private static function amankan(string $c): string
    {
        return preg_match('/^[=+@]|^-[^0-9\s.]/', $c) && ! is_numeric($c) ? "'".$c : $c;
    }
}
