<?php

namespace App\Support;

use App\Models\Pengaturan;
use Carbon\CarbonInterface;
use IntlDateFormatter;

/** Tanggal Hijriah (Umm al-Qura via ext-intl). Koreksi ±1 hari dapat diatur (kalender Muhammadiyah kadang berbeda sehari). */
class TanggalHijriah
{
    public static function format(CarbonInterface $tanggal): string
    {
        if (! class_exists(IntlDateFormatter::class)) {
            return '';
        }
        $koreksi = max(-2, min(2, (int) Pengaturan::ambil('hijriah_koreksi', 0)));
        $f = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, $tanggal->getTimezone()->getName(), IntlDateFormatter::TRADITIONAL, 'd MMMM y');

        return trim((string) $f->format($tanggal->copy()->addDays($koreksi)->toDateTime())).' H';
    }
}
