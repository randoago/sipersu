{{-- Stepper horizontal. $langkah: [['judul'=>..., 'deskripsi'=>...], ...]; $aktif: 1-based. --}}
@props(['langkah', 'aktif' => 1])
<ol {{ $attributes->class(['flex items-start gap-space-sm']) }}>
    @foreach ($langkah as $i => $l)
        @php $no = $i + 1; $selesai = $no < $aktif; $sekarang = $no === $aktif; @endphp
        <li class="flex flex-1 items-center gap-space-sm min-w-0">
            <span @class([
                'flex h-9 w-9 shrink-0 items-center justify-center rounded-full font-label-lg text-label-lg',
                'bg-primary text-on-primary ring-4 ring-primary-fixed' => $sekarang,
                'bg-primary-container text-on-primary' => $selesai,
                'bg-surface-container-high text-on-surface-variant' => ! $sekarang && ! $selesai,
            ])>@if ($selesai)<x-ikon name="check" class="text-[20px]" />@else{{ $no }}@endif</span>
            <div class="min-w-0 hidden sm:block">
                <p class="font-label-sm text-label-sm uppercase tracking-wide {{ $sekarang ? 'text-primary' : 'text-on-surface-variant' }}">Langkah {{ $no }}</p>
                <p class="font-label-lg text-label-lg truncate {{ $sekarang || $selesai ? 'text-on-surface' : 'text-on-surface-variant' }}">{{ $l['judul'] }}</p>
                @isset($l['deskripsi'])<p class="font-body-sm text-body-sm text-on-surface-variant truncate">{{ $l['deskripsi'] }}</p>@endisset
            </div>
            @if (! $loop->last)<span class="mx-space-xs h-0.5 flex-1 rounded {{ $selesai ? 'bg-primary-container' : 'bg-surface-container-high' }}"></span>@endif
        </li>
    @endforeach
</ol>
