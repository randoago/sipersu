{{-- Deretan kotak tahap yang bisa ditambah, digeser, dan dihapus. Dipakai di dalam x-data kartu alur (state: array peran bernama $model). --}}
<template x-for="(r, i) in {{ $model }}" :key="i">
    <div class="contents">
        <div class="flex items-center text-outline" aria-hidden="true"><x-ikon name="arrow_forward" class="text-[18px]" /></div>
        <div class="flex w-44 flex-col gap-2 rounded-lg border border-outline-variant bg-surface-container-low p-2.5">
            <div class="flex items-center gap-2"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-container-lowest text-primary"><x-ikon :name="$ikon" class="text-[16px]" /></span>
                <p class="font-label-md text-label-md">{{ $judul }} <span x-text="i + 1"></span></p></div>
            <select name="{{ $model }}[]" x-model="{{ $model }}[i]" :aria-label="'{{ $judul }} ke-' + (i + 1)" class="block h-9 w-full rounded-lg border border-outline-variant bg-surface-container-lowest pr-8 font-body-sm text-body-sm text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/20">
                @foreach ($opsi as $nilai => $nama)<option value="{{ $nilai }}">{{ $nama }}</option>@endforeach
            </select>
            <div class="flex items-center justify-between">
                <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container disabled:opacity-30" title="Geser ke kiri" aria-label="Geser ke kiri" :disabled="i === 0" x-on:click="{{ $model }}.splice(i - 1, 2, {{ $model }}[i], {{ $model }}[i - 1])"><x-ikon name="arrow_back" class="text-[18px]" /></button>
                <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-error hover:bg-error-container/40 disabled:opacity-30" title="Hapus tahap" aria-label="Hapus tahap" {!! ($minimal ?? 0) ? ':disabled="'.$model.'.length <= '.$minimal.'"' : '' !!} x-on:click="{{ $model }}.splice(i, 1)"><x-ikon name="delete" class="text-[18px]" /></button>
                <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant hover:bg-surface-container disabled:opacity-30" title="Geser ke kanan" aria-label="Geser ke kanan" :disabled="i === {{ $model }}.length - 1" x-on:click="{{ $model }}.splice(i, 2, {{ $model }}[i + 1], {{ $model }}[i])"><x-ikon name="arrow_forward" class="text-[18px]" /></button>
            </div>
        </div>
    </div>
</template>
<div class="flex items-center text-outline" aria-hidden="true" x-show="{{ $model }}.length < {{ \App\Models\JenisSurat::MAKS_TAHAP }}"><x-ikon name="arrow_forward" class="text-[18px]" /></div>
<button type="button" x-show="{{ $model }}.length < {{ \App\Models\JenisSurat::MAKS_TAHAP }}" x-on:click="{{ $model }}.push('{{ $bawaan }}')" class="flex w-32 flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-outline-variant p-2.5 font-label-md text-label-md text-primary hover:bg-primary-fixed/30">
    <x-ikon name="add" class="text-[22px]" />{{ $tambah }}
</button>
