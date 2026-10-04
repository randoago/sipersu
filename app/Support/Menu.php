<?php

namespace App\Support;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Menu sidebar menurut peran. Butir hanya tampil bila route-nya sudah ada,
 * jadi menu fase berikutnya (surat masuk, disposisi, arsip, ...) muncul otomatis.
 */
class Menu
{
    /** [route, label, ikon, peran yang boleh] */
    private const STAF = [
        ['dasbor', 'Dasbor', 'space_dashboard', null],
        ['pengajuan.index', 'Layanan Mahasiswa', 'school', [Peran::SuperAdmin, Peran::AdminTu, Peran::Kaprodi]],
        ['persetujuan.index', 'Persetujuan & TTD', 'draw', [Peran::SuperAdmin, Peran::Dekan, Peran::WakilDekan, Peran::Kaprodi]],
        ['surat-masuk.index', 'Surat Masuk', 'move_to_inbox', [Peran::SuperAdmin, Peran::AdminTu, Peran::Dekan, Peran::WakilDekan, Peran::Kaprodi]],
        ['surat-keluar.index', 'Surat Keluar', 'outbox', null],
        ['disposisi.index', 'Disposisi', 'assignment_turned_in', null],
        ['arsip.index', 'Arsip', 'folder_zip', null],
        ['laporan.index', 'Laporan', 'summarize', [Peran::SuperAdmin, Peran::AdminTu, Peran::Dekan, Peran::WakilDekan]],
        ['format-surat.index', 'Format Surat', 'edit_document', [Peran::SuperAdmin, Peran::AdminTu]],
        ['master.index', 'Master Data', 'database', [Peran::SuperAdmin, Peran::AdminTu]],
        ['pengaturan.index', 'Pengaturan', 'settings', [Peran::SuperAdmin, Peran::AdminTu]],
    ];

    private const MAHASISWA = [
        'MENU PERSURATAN' => [
            ['dasbor', 'Beranda / Dasbor', 'space_dashboard'],
            ['layanan.katalog', 'Pengajuan Surat', 'post_add'],
            ['layanan.lacak', 'Lacak Status', 'query_stats'],
            ['layanan.riwayat', 'Riwayat Surat', 'history_edu'],
        ],
        'PUSAT LAYANAN' => [
            ['bantuan', 'Bantuan TU', 'support_agent'],
        ],
    ];

    /** @return array<int, array{judul: ?string, butir: array<int, array{url: string, label: string, ikon: string, aktif: bool}>}> */
    public static function untuk(User $user): array
    {
        $hanyaMahasiswa = $user->hasRole(Peran::Mahasiswa->value) && $user->roles->count() === 1;

        if ($hanyaMahasiswa) {
            $bagian = [];
            foreach (self::MAHASISWA as $judul => $daftar) {
                $butir = array_values(array_filter(array_map(fn ($b) => self::butir($b[0], $b[1], $b[2]), $daftar)));
                if ($butir) {
                    $bagian[] = ['judul' => $judul, 'butir' => $butir];
                }
            }

            return $bagian;
        }

        $butir = [];
        foreach (self::STAF as [$route, $label, $ikon, $peran]) {
            if ($peran && ! $user->hasAnyRole(array_map(fn ($p) => $p->value, $peran))) {
                continue;
            }
            if ($b = self::butir($route, $label, $ikon)) {
                $butir[] = $b;
            }
        }
        // Dosen/tendik yang juga mahasiswa tidak dibahas; staf selalu memakai menu ini.
        return [['judul' => null, 'butir' => $butir]];
    }

    private static function butir(string $route, string $label, string $ikon): ?array
    {
        if (! Route::has($route)) {
            return null;
        }
        $awal = explode('.', $route)[0];
        // Daftar (".index") aktif juga di halaman turunannya (show, buat, ...); selain itu cocok persis.
        $pola = str_ends_with($route, '.index') ? [$route, $awal.'.*'] : [$route];
        if ($route === 'layanan.katalog') {
            $pola[] = 'layanan.ajukan';
        }

        return [
            'url' => route($route),
            'label' => $label,
            'ikon' => $ikon,
            'aktif' => request()->routeIs(...$pola),
        ];
    }
}
