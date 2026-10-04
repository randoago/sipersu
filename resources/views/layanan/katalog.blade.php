<x-layouts::app title="Pengajuan Surat" :cari="false">
<div class="mx-auto max-w-5xl space-y-6" x-data="{ q: '' }">
    <div>
        <h1 class="font-headline-xl text-headline-xl text-on-surface">Pengajuan Surat</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Pilih jenis surat yang ingin Anda ajukan. Surat yang terbit memiliki tanda tangan elektronik dengan kode QR yang dapat diverifikasi.</p>
    </div>
    <div class="relative w-full lg:max-w-xl">
        <x-ikon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline" />
        <input x-model="q" type="search" placeholder="Cari jenis surat…" class="h-10 w-full rounded-lg border-0 bg-surface-container-lowest pl-9 pr-3 font-body-sm text-body-sm shadow-soft placeholder:text-outline focus:ring-2 focus:ring-primary">
    </div>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
        @foreach ($jenis as $i => $j)
            <x-kartu-jenis-surat :jenis="$j" :indeks="$i" x-show="q === '' || $el.dataset.cari.includes(q.toLowerCase())" />
        @endforeach
    </div>
</div>
</x-layouts::app>
