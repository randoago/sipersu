{{-- Navigasi halaman: ikon panah (tanpa teks "Previous/Next"), keterangan berbahasa Indonesia. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="font-body-sm text-body-sm text-on-surface-variant">Menampilkan <span class="font-semibold text-on-surface tabular">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span> dari <span class="font-semibold text-on-surface tabular">{{ $paginator->total() }}</span> data</p>
        <ul class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <li aria-disabled="true"><span class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg text-outline-variant" aria-label="Halaman sebelumnya"><x-ikon name="chevron_left" class="text-[22px]" /></span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" title="Halaman sebelumnya" class="flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant transition hover:bg-surface-container-high hover:text-primary"><x-ikon name="chevron_left" class="text-[22px]" /></a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li aria-disabled="true"><span class="flex h-9 min-w-9 items-center justify-center px-1 font-label-md text-label-md text-on-surface-variant">{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page"><span class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-primary px-2 font-label-lg text-label-lg text-on-primary tabular">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="Halaman {{ $page }}" class="flex h-9 min-w-9 items-center justify-center rounded-lg px-2 font-label-lg text-label-lg text-on-surface-variant tabular transition hover:bg-surface-container-high hover:text-primary">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" title="Halaman berikutnya" class="flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant transition hover:bg-surface-container-high hover:text-primary"><x-ikon name="chevron_right" class="text-[22px]" /></a></li>
            @else
                <li aria-disabled="true"><span class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg text-outline-variant" aria-label="Halaman berikutnya"><x-ikon name="chevron_right" class="text-[22px]" /></span></li>
            @endif
        </ul>
    </nav>
@endif
