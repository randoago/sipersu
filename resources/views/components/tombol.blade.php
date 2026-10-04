@props([
    'varian' => 'utama',   {{-- utama | sekunder | aksen | bahaya | lembut | hantu --}}
    'ukuran' => 'md',      {{-- md | sm | lg --}}
    'href' => null,
    'ikon' => null,
    'ikonKanan' => null,
    'type' => 'button',
])
@php
    $dasar = 'inline-flex items-center justify-center gap-space-xs rounded-lg font-label-lg text-label-lg font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap';
    $v = [
        'utama' => 'bg-primary text-on-primary hover:bg-primary-container shadow-sm',
        'sekunder' => 'bg-surface-container-lowest text-on-surface border border-outline-variant hover:bg-surface-container-low',
        'aksen' => 'bg-secondary-container text-on-secondary-container hover:bg-secondary-fixed shadow-sm',
        'bahaya' => 'bg-error-container/40 text-error border border-error/20 hover:bg-error-container',
        'lembut' => 'bg-surface-container text-on-surface hover:bg-surface-container-high',
        'hantu' => 'text-primary hover:bg-surface-container',
    ][$varian];
    $u = ['sm' => 'h-8 px-3 text-label-md', 'md' => 'h-10 px-4', 'lg' => 'h-12 px-6'][$ukuran];
    $kelas = "$dasar $v $u";
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$kelas]) }}>
        @if ($ikon)<x-ikon :name="$ikon" class="text-[20px]" />@endif
        {{ $slot }}
        @if ($ikonKanan)<x-ikon :name="$ikonKanan" class="text-[20px]" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$kelas]) }}>
        @if ($ikon)<x-ikon :name="$ikon" class="text-[20px]" />@endif
        {{ $slot }}
        @if ($ikonKanan)<x-ikon :name="$ikonKanan" class="text-[20px]" />@endif
    </button>
@endif
