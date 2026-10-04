{{-- Bidang input berlabel. Mendukung wire:model / name, pesan galat Laravel otomatis. --}}
@props(['kunci' => null, 'label' => null, 'name' => null, 'wajib' => false, 'bantuan' => null, 'ikon' => null, 'type' => 'text'])
@php $galat = ($kunci ?? $name) ? $errors->first($kunci ?? $name) : null; @endphp
<div {{ $attributes->only('class')->class(['w-full']) }}>
    @if ($label)
        <label for="{{ $attributes->get('id', $name) }}" class="mb-1.5 block font-label-lg text-label-lg text-on-surface">{{ $label }}@if ($wajib)<span class="text-error"> *</span>@endif</label>
    @endif
    <div class="relative">
        @if ($ikon)<x-ikon :name="$ikon" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline" />@endif
        <input type="{{ $type }}" @if ($name) name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" @endif
            {{ $attributes->except('class')->class(['block h-10 w-full rounded-lg border bg-surface-container-lowest font-body-md text-body-md text-on-surface placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/20', 'pl-10' => $ikon, 'border-outline-variant' => ! $galat, 'border-[#e11d48]' => $galat]) }}>
        {{ $slot }}
    </div>
    @if ($galat)<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $galat }}</p>
    @elseif ($bantuan)<p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $bantuan }}</p>@endif
</div>
