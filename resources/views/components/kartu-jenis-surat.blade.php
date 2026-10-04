@props(['jenis', 'indeks' => 0])
@php
    $warna = [
        'bg-tertiary-fixed/40 text-primary', 'bg-secondary-fixed/50 text-secondary', 'bg-tertiary-fixed-dim/40 text-primary',
        'bg-secondary-container/30 text-on-secondary-container', 'bg-secondary-fixed/40 text-secondary',
    ][$indeks % 5];
    $sla = $jenis->sla_hari <= 1 ? '1 hari kerja' : '1–'.$jenis->sla_hari.' hari kerja';
@endphp
<div {{ $attributes->class(['flex flex-col justify-between rounded-xl bg-surface-container-lowest p-3 shadow-sm transition-all hover:shadow-md active:scale-[0.98] lg:p-4']) }}
     data-cari="{{ mb_strtolower($jenis->nama.' '.$jenis->deskripsi) }}">
    <div class="space-y-2.5">
        <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $warna }}"><x-ikon :name="$jenis->ikon" class="text-[20px]" /></div>
        <div>
            <h3 class="line-clamp-2 font-headline-sm text-headline-sm font-semibold leading-tight text-on-surface">{{ $jenis->nama }}</h3>
            <p class="mt-1 line-clamp-2 font-body-sm text-body-sm text-on-surface-variant">{{ $jenis->deskripsi }}</p>
        </div>
    </div>
    <div class="mt-2 flex flex-col gap-2 pt-3">
        <span class="inline-flex w-max items-center gap-1 rounded-full bg-surface-container-low px-2 py-0.5 text-[10px] font-medium text-on-surface-variant tabular"><x-ikon name="schedule" class="text-[12px]" />{{ $sla }}</span>
        <a href="{{ route('layanan.ajukan', $jenis) }}" class="flex h-8 w-full items-center justify-center gap-1 rounded-lg bg-primary font-label-sm text-label-sm font-semibold text-on-primary transition-colors hover:bg-primary-container">
            <span>Ajukan</span><x-ikon name="arrow_forward" class="text-[14px]" />
        </a>
    </div>
</div>
