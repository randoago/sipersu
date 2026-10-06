{{-- Navigasi halaman ringkas (simplePaginate): hanya ikon panah. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-end gap-1">
        @if ($paginator->onFirstPage())
            <span class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg text-outline-variant" aria-label="Halaman sebelumnya"><x-ikon name="chevron_left" class="text-[22px]" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" title="Halaman sebelumnya" class="flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant transition hover:bg-surface-container-high hover:text-primary"><x-ikon name="chevron_left" class="text-[22px]" /></a>
        @endif
        <span class="px-2 font-label-lg text-label-lg tabular">{{ $paginator->currentPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" title="Halaman berikutnya" class="flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant transition hover:bg-surface-container-high hover:text-primary"><x-ikon name="chevron_right" class="text-[22px]" /></a>
        @else
            <span class="flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg text-outline-variant" aria-label="Halaman berikutnya"><x-ikon name="chevron_right" class="text-[22px]" /></span>
        @endif
    </nav>
@endif
