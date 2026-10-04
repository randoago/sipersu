<?php

namespace App\View\Components;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

/**
 * Ikon Material Symbols sebagai SVG inline dari resources/icons (tanpa font/CDN).
 * Pemakaian: <x-ikon name="verified" class="text-[20px]" /> atau <x-ikon name="check_circle" filled />
 */
class Ikon extends Component
{
    /** Nama dari desain Stitch yang tidak ada di paket SVG → padanannya. */
    private const ALIAS = [
        'expand_more' => 'keyboard_arrow_down',
        'insights' => 'query_stats',
        'help_outline' => 'help',
        'report_problem' => 'warning',
        'restore' => 'history',
    ];

    private static array $memori = [];

    public function __construct(public string $name, public bool $filled = false)
    {
    }

    public function path(): string
    {
        $nama = self::ALIAS[$this->name] ?? $this->name;
        $kunci = $nama.($this->filled ? '-fill' : '');

        return self::$memori[$kunci] ??= $this->muat($nama, $this->filled);
    }

    private function muat(string $nama, bool $filled): string
    {
        $berkas = resource_path('icons/'.$nama.($filled ? '-fill' : '').'.svg');
        if (! is_file($berkas)) {
            $berkas = resource_path('icons/'.$nama.'.svg');
        }
        if (! is_file($berkas)) {
            return '<circle cx="480" cy="-480" r="200"/>';
        }
        $svg = file_get_contents($berkas);

        return preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svg, $m) ? $m[1] : '';
    }

    public function render()
    {
        return fn () => '<svg {{ $attributes->class(["ikon"]) }} viewBox="0 -960 960 960" aria-hidden="true">{!! $path() !!}</svg>';
    }
}
