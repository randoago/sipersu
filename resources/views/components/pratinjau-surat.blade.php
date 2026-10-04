{{-- Modal pratinjau tampilan surat. Dibuka oleh event window "tampil-pratinjau" ({ html }) —
     dari pratinjauSurat() (JS, layouts/app) atau Livewire $this->dispatch('tampil-pratinjau', html: ...). --}}
<div x-data="{ buka: false, html: '' }" x-on:tampil-pratinjau.window="html = $event.detail.html; buka = true"
     x-on:keydown.escape.window="buka = false" x-show="buka" x-cloak class="fixed inset-0 z-[70] flex items-start justify-center overflow-y-auto bg-[#0f172a]/55 p-3 sm:p-6" role="dialog" aria-modal="true" aria-label="Pratinjau surat">
    <div class="my-2 w-full max-w-4xl rounded-lg bg-surface-container-lowest shadow-modal" @click.outside="buka = false">
        <div class="sticky top-0 z-10 flex items-center justify-between gap-3 rounded-t-lg border-b border-surface-container bg-surface-container-lowest px-space-lg py-space-md">
            <div><h3 class="font-headline-md text-headline-md">Tampilan Surat</h3><p class="font-body-sm text-body-sm text-on-surface-variant">Pratinjau sebelum disimpan. Nomor surat dan QR terbit saat surat ditandatangani.</p></div>
            <button type="button" @click="buka = false" class="rounded-lg bg-surface-container px-3 py-1.5 font-label-md text-label-md hover:bg-surface-container-high">Tutup</button>
        </div>
        <style>@include('pdf._gaya')</style>
        <div class="bg-surface-container-high/60 p-3 sm:p-6">
            <div class="relative mx-auto min-h-[1123px] w-full max-w-[794px] bg-white px-[8%] pb-20 pt-12 shadow-md" x-html="html"></div>
        </div>
    </div>
</div>
