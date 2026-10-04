{{-- Modal Alpine. Buka: $dispatch('buka-modal', 'nama') atau x-on:click="$dispatch('buka-modal','nama')". --}}
@props(['nama', 'judul' => null, 'lebar' => 'max-w-lg'])
<div x-data="{ buka: false }" x-on:buka-modal.window="if ($event.detail === '{{ $nama }}') buka = true" x-on:tutup-modal.window="buka = false" x-on:keydown.escape.window="buka = false"
     x-show="buka" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-space-md" role="dialog" aria-modal="true">
    <div x-show="buka" x-transition.opacity class="absolute inset-0 bg-[#0f172a]/45" @click="buka = false"></div>
    <div x-show="buka" x-transition class="relative w-full {{ $lebar }} rounded-lg bg-surface-container-lowest shadow-modal">
        @if ($judul)
            <div class="flex items-center justify-between border-b border-surface-container px-space-lg py-space-md">
                <h3 class="font-headline-md text-headline-md">{{ $judul }}</h3>
                <button type="button" @click="buka = false" class="rounded p-1 text-on-surface-variant hover:bg-surface-container" aria-label="Tutup"><x-ikon name="close" class="text-[20px]" /></button>
            </div>
        @endif
        <div class="p-space-lg">{{ $slot }}</div>
        @isset($kaki)<div class="flex justify-end gap-space-sm border-t border-surface-container px-space-lg py-space-md">{{ $kaki }}</div>@endisset
    </div>
</div>
