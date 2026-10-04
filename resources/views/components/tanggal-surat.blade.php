{{-- Pemilih tanggal surat. Kosong / hari ini = ikut hari penandatanganan. Tanggal Hijriah ditampilkan otomatis. --}}
@props(['nilai' => null])
@php
    $hariIni = now()->toDateString();
    $awal = $nilai ?: $hariIni;
@endphp
<div x-data="tanggalSurat(@js(old('tanggal_surat', $awal)), @js(route('tanggal.hijriah')), @js($hariIni))" {{ $attributes }}>
    <label for="tanggal_surat" class="mb-1.5 block font-label-lg text-label-lg text-on-surface">Tanggal surat</label>
    <div class="flex items-center gap-2">
        <div class="relative flex-1">
            <x-ikon name="calendar_today" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline" />
            <input type="date" id="tanggal_surat" name="tanggal_surat" value="{{ old('tanggal_surat', $awal) }}" x-model="tgl" @change="hitung()" min="{{ now()->subDays(30)->toDateString() }}" max="{{ now()->addDays(90)->toDateString() }}"
                   class="block h-10 w-full rounded-lg border border-outline-variant bg-surface-container-lowest pl-10 font-body-md text-body-md text-on-surface focus:border-primary-container focus:ring-2 focus:ring-primary-container/20 @error('tanggal_surat') border-[#e11d48] @enderror">
        </div>
        <button type="button" @click="hariIniKlik()" x-show="tgl !== hariIni" x-cloak class="h-10 shrink-0 rounded-lg bg-surface-container px-3 font-label-md text-label-md hover:bg-surface-container-high">Hari ini</button>
    </div>
    <p class="mt-1 flex flex-wrap items-center gap-x-1.5 font-body-sm text-body-sm text-on-surface-variant">
        <x-ikon name="event" class="text-[16px] text-primary" />
        <span x-show="!tgl">Kosong = hari ini (saat surat ditandatangani).</span>
        <span x-show="tgl"><span x-text="masehi"></span> • <strong class="text-on-surface" x-text="hijriah || '…'"></strong></span>
        <span x-show="tgl && tgl !== hariIni" x-cloak class="rounded bg-secondary-fixed/60 px-1.5 py-0.5 font-label-sm text-label-sm text-on-secondary-fixed-variant">tanggal dipilih</span>
    </p>
    @error('tanggal_surat')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
</div>
