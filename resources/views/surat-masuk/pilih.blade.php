<x-layouts::app title="Catat Surat Masuk" :cari="false">
<div class="mx-auto max-w-6xl space-y-6">
    <nav class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('surat-masuk.index') }}" class="hover:text-primary">Surat Masuk</a><x-ikon name="chevron_right" class="text-[14px]" /><span>Catat surat</span></nav>
    <div><h1 class="font-headline-xl text-headline-xl">Catat Surat Masuk — Pilih Jenis</h1>
        <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Pilih jenis surat yang diterima. Setiap jenis punya kolom tambahan sendiri (diatur di <a class="font-semibold text-primary underline" href="{{ route('format-surat.index', ['sasaran' => 'masuk']) }}">Format Surat</a>).</p></div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($formats as $f)
            <a href="{{ route('surat-masuk.isi', $f) }}" class="group flex flex-col justify-between rounded-xl bg-surface-container-lowest p-space-md shadow-soft transition hover:shadow-popover">
                <div class="space-y-2.5"><span class="flex h-10 w-10 items-center justify-center rounded-lg bg-secondary-fixed/50 text-secondary"><x-ikon :name="$f->ikon" class="text-[22px]" /></span>
                    <h2 class="font-headline-sm text-headline-sm font-semibold">{{ $f->nama }}</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $f->deskripsi ?: 'Catat surat dengan kolom standar.' }}</p></div>
                <div class="mt-4 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><span class="rounded-full bg-surface-container px-2 py-0.5">+{{ count($f->field_formulir) }} kolom khusus</span><span class="ml-auto flex items-center gap-0.5 font-semibold text-primary group-hover:underline">Catat<x-ikon name="arrow_forward" class="text-[14px]" /></span></div>
            </a>
        @endforeach
    </div>
    @if ($formats->isEmpty())<x-peringatan jenis="info">Belum ada format surat masuk aktif. <a class="font-semibold underline" href="{{ route('format-surat.buat') }}">Buat format</a> (pilih jenis "Surat masuk").</x-peringatan>@endif
</div>
</x-layouts::app>
