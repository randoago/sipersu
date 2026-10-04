<?php

namespace App\Enums;

enum StatusPengajuan: string
{
    case Diajukan = 'diajukan';
    case Diverifikasi = 'diverifikasi';
    case Disetujui = 'disetujui';
    case Ditandatangani = 'ditandatangani';
    case Selesai = 'selesai';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Kelas Tailwind ditulis utuh (bukan dirangkai) agar terbaca oleh pemindai Tailwind. */
    public function kelas(): string
    {
        return match ($this) {
            self::Diajukan => 'bg-status-diajukan-bg text-status-diajukan-text border-status-diajukan-border',
            self::Diverifikasi => 'bg-status-diverifikasi-bg text-status-diverifikasi-text border-status-diverifikasi-border',
            self::Disetujui => 'bg-status-disetujui-bg text-status-disetujui-text border-status-disetujui-border',
            self::Ditandatangani => 'bg-status-ditandatangani-bg text-status-ditandatangani-text border-status-ditandatangani-border',
            self::Selesai => 'bg-status-selesai-bg text-status-selesai-text border-status-selesai-border',
            self::Ditolak => 'bg-status-ditolak-bg text-status-ditolak-text border-status-ditolak-border',
        };
    }

    /** Urutan tahap pada stepper/timeline (Ditolak di luar alur normal). */
    public static function alur(): array
    {
        return [self::Diajukan, self::Diverifikasi, self::Disetujui, self::Ditandatangani, self::Selesai];
    }
}
