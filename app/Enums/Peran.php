<?php

namespace App\Enums;

enum Peran: string
{
    case SuperAdmin = 'super_admin';
    case AdminTu = 'admin_tu';
    case Dekan = 'dekan';
    case WakilDekan = 'wakil_dekan';
    case Kaprodi = 'kaprodi';
    case DosenTendik = 'dosen_tendik';
    case Mahasiswa = 'mahasiswa';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminTu => 'Admin Tata Usaha',
            self::Dekan => 'Dekan',
            self::WakilDekan => 'Wakil Dekan',
            self::Kaprodi => 'Kaprodi',
            self::DosenTendik => 'Dosen / Tendik',
            self::Mahasiswa => 'Mahasiswa',
        };
    }

    /** Peran yang berhak menandatangani/menyetujui surat. */
    public function pejabat(): bool
    {
        return in_array($this, [self::Dekan, self::WakilDekan, self::Kaprodi], true);
    }
}
