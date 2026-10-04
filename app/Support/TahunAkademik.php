<?php

namespace App\Support;

use App\Models\Pengaturan;

class TahunAkademik
{
    /** Contoh: "2025/2026 Ganjil". Dapat ditimpa lewat pengaturan "tahun_akademik". */
    public static function saatIni(): string
    {
        if ($atur = Pengaturan::ambil('tahun_akademik')) {
            return $atur;
        }
        $n = now();
        $mulai = $n->month >= 8 ? $n->year : $n->year - 1;
        $semester = ($n->month >= 8 || $n->month === 1) ? 'Ganjil' : 'Genap';

        return $mulai.'/'.($mulai + 1).' '.$semester;
    }
}
