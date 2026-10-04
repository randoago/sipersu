<nav class="mb-6 flex flex-wrap gap-1 rounded-lg bg-surface-container p-1" aria-label="Pengaturan">
    @foreach ([['pengaturan.nomor', 'Format Nomor', 'numbers'], ['pengaturan.backup', 'Backup', 'backup'], ['pengaturan.log', 'Log Aktivitas', 'history']] as [$r, $l, $i])
        @if (Route::has($r))
            <a href="{{ route($r) }}" @class(['flex items-center gap-1.5 rounded-md px-3.5 py-2 font-label-md text-label-md transition', 'bg-surface-container-lowest font-semibold text-primary shadow-sm' => request()->routeIs($r), 'text-on-surface-variant hover:text-on-surface' => ! request()->routeIs($r)])><x-ikon :name="$i" class="text-[18px]" />{{ $l }}</a>
        @endif
    @endforeach
</nav>
