{{-- Garis waktu vertikal. Isi dengan <x-garis-waktu.butir>. --}}
<ol {{ $attributes->class(['relative space-y-space-lg before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-outline-variant/60']) }}>{{ $slot }}</ol>
