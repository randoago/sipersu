@props(['judul' => null, 'deskripsi' => null, 'ikon' => null, 'padding' => 'p-space-lg'])
<section {{ $attributes->class(['bg-surface-container-lowest rounded-lg shadow-soft']) }}>
    @if ($judul || isset($aksi))
        <header class="flex items-start justify-between gap-space-md px-space-lg pt-space-lg">
            <div class="flex items-start gap-space-sm min-w-0">
                @if ($ikon)
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-fixed/50 text-primary"><x-ikon :name="$ikon" class="text-[20px]" /></span>
                @endif
                <div class="min-w-0">
                    <h2 class="font-headline-md text-headline-md text-on-surface">{{ $judul }}</h2>
                    @if ($deskripsi)<p class="font-body-sm text-body-sm text-on-surface-variant">{{ $deskripsi }}</p>@endif
                </div>
            </div>
            @isset($aksi)<div class="shrink-0">{{ $aksi }}</div>@endisset
        </header>
    @endif
    <div class="{{ $padding }}">{{ $slot }}</div>
</section>
