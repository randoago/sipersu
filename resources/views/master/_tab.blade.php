<nav class="mb-5 flex flex-wrap gap-1 rounded-lg bg-surface-container p-1" aria-label="Master data">
    @foreach ($semua as $k => $x)
        <a href="{{ route('master.daftar', $k) }}" @class(['flex items-center gap-1.5 rounded-md px-3.5 py-2 font-label-md text-label-md transition', 'bg-surface-container-lowest font-semibold text-primary shadow-sm' => $entitas === $k, 'text-on-surface-variant hover:text-on-surface' => $entitas !== $k])><x-ikon :name="$x['ikon']" class="text-[18px]" />{{ $x['judul'] }}</a>
    @endforeach
</nav>
