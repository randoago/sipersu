@props([
    'label', 'nilai', 'ikon', 'satuan' => null, 'catatan' => null, 'catatanIkon' => null,
    'warna' => 'hijau',   {{-- hijau | biru | emas | ungu | merah --}}
    'href' => null,
])
@php
    $w = [
        'hijau' => ['bg-primary-fixed text-primary', 'text-primary'],
        'biru' => ['bg-[#dbe4ff] text-[#3b4ba0]', 'text-[#3b4ba0]'],
        'emas' => ['bg-secondary-fixed text-secondary', 'text-secondary'],
        'ungu' => ['bg-[#ede0ff] text-[#6b21a8]', 'text-[#6b21a8]'],
        'merah' => ['bg-error-container text-on-error-container', 'text-error'],
    ][$warna];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['flex flex-col justify-between gap-space-md rounded-lg bg-surface-container-lowest p-space-lg shadow-soft', 'hover:shadow-popover transition-shadow' => $href]) }}>
    <div class="flex items-start justify-between gap-space-sm">
        <span class="font-label-md text-label-md uppercase tracking-wide text-on-surface-variant">{{ $label }}</span>
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $w[0] }}"><x-ikon :name="$ikon" class="text-[22px]" /></span>
    </div>
    <div class="flex items-baseline gap-space-xs">
        <span class="font-headline-xl text-headline-xl tabular text-on-surface">{{ $nilai }}</span>
        @if ($satuan)<span class="font-label-md text-label-md text-on-surface-variant">{{ $satuan }}</span>@endif
    </div>
    @if ($catatan)
        <div class="flex items-center gap-space-xs rounded-lg bg-surface-container-low px-space-sm py-1.5 font-label-sm text-label-sm {{ $w[1] }}">
            @if ($catatanIkon)<x-ikon :name="$catatanIkon" class="text-[16px]" />@endif
            <span class="truncate">{{ $catatan }}</span>
        </div>
    @endif
</{{ $tag }}>
