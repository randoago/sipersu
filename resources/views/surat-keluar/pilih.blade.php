<x-layouts::app title="Buat Surat" :cari="false">
<div class="mx-auto max-w-6xl space-y-6">
    <nav class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('surat-keluar.index') }}" class="hover:text-primary">Surat Keluar</a><x-ikon name="chevron_right" class="text-[14px]" /><span>Buat surat</span></nav>
    <div><h1 class="font-headline-xl text-headline-xl">Buat Surat — Pilih Format</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Pilih jenis surat yang akan dibuat. Anda akan mengisi formulir isian, lalu surat disusun otomatis pada kop resmi.</p></div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($formats as $i => $f)
            <a href="{{ route('surat-keluar.isi', $f) }}" class="group flex flex-col justify-between rounded-xl bg-surface-container-lowest p-space-md shadow-soft transition hover:shadow-popover">
                <div class="space-y-2.5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-fixed/50 text-primary"><x-ikon :name="$f->ikon" class="text-[22px]" /></span>
                    <h2 class="font-headline-sm text-headline-sm font-semibold">{{ $f->nama }}</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $f->deskripsi ?: 'Isi '.count($f->field_formulir).' isian untuk membuat surat ini.' }}</p>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant">
                    <span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2 py-0.5"><x-ikon :name="$f->mode_ttd === 'basah' ? 'print' : 'qr_code_2'" class="text-[12px]" />{{ $f->mode_ttd === 'basah' ? 'Tanpa QR' : 'Ber-QR' }}</span>
                    <span class="rounded-full bg-surface-container px-2 py-0.5">{{ count($f->field_formulir) }} isian</span>
                    <span class="ml-auto flex items-center gap-0.5 font-semibold text-primary group-hover:underline">Isi surat<x-ikon name="arrow_forward" class="text-[14px]" /></span>
                </div>
            </a>
        @endforeach
        <a href="{{ route('surat-keluar.bebas') }}" class="flex flex-col justify-between rounded-xl border-2 border-dashed border-outline-variant p-space-md transition hover:bg-surface-container-low">
            <div class="space-y-2.5"><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-container text-on-surface-variant"><x-ikon name="edit_document" class="text-[22px]" /></span>
                <h2 class="font-headline-sm text-headline-sm font-semibold">Surat Bebas</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant">Tulis surat sendiri tanpa format (tujuan, perihal, isi).</p></div>
            <span class="mt-4 flex items-center gap-0.5 font-label-sm text-label-sm font-semibold text-primary">Tulis surat<x-ikon name="arrow_forward" class="text-[14px]" /></span>
        </a>
    </div>
    @if ($formats->isEmpty() && auth()->user()->adalahAdmin())
        <x-peringatan jenis="info">Belum ada format untuk staf. <a class="font-semibold underline" href="{{ route('format-surat.buat') }}">Buat format surat</a> agar petugas bisa mengisi surat dari formulir.</x-peringatan>
    @elseif (auth()->user()->adalahAdmin())
        <p class="font-body-sm text-body-sm text-on-surface-variant">Ingin menambah jenis surat? <a class="font-semibold text-primary underline" href="{{ route('format-surat.index') }}">Atur Format Surat</a>.</p>
    @endif
</div>
</x-layouts::app>
