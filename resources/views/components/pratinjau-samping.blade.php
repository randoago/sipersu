{{-- Pratinjau surat langsung di kolom kanan formulir (diperbarui otomatis saat isian berubah). Letakkan DI DALAM <form>.
     Kertas selalu berukuran A4 asli (794 px); bila kolom lebih sempit, panel yang digulir — kertas tidak diperkecil. --}}
@props(['url', 'tambahan' => []])
<aside x-data="pratinjauSamping(@js($url), @js($tambahan))" class="min-w-0 lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)] lg:overflow-y-auto" aria-label="Pratinjau surat">
    <style>@include('pdf._gaya')</style>
    <div class="rounded-lg bg-surface-container-lowest shadow-soft">
        <div class="flex items-center justify-between gap-3 border-b border-surface-container px-space-md py-space-sm">
            <div><h3 class="font-headline-md text-headline-md">Pratinjau Surat</h3><p class="font-body-sm text-body-sm text-on-surface-variant" x-text="memuat ? 'Memperbarui…' : 'Diperbarui otomatis saat Anda mengisi.'">Diperbarui otomatis saat Anda mengisi.</p></div>
            <button type="button" @click="muat()" class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-surface-container px-3 py-1.5 font-label-md text-label-md hover:bg-surface-container-high"><x-ikon name="refresh" class="text-[18px]" />Segarkan</button>
        </div>
        <div class="overflow-x-auto bg-surface-container-high/60 p-2 sm:p-3">
            <div x-ref="kertas" class="relative mx-auto w-[794px] min-h-[1123px] shrink-0 bg-white px-[8%] pb-20 pt-12 shadow-md" x-html="html"></div>
        </div>
    </div>
</aside>
