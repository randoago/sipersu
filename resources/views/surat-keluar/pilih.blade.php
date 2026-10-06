<x-layouts::app title="Buat Surat" :cari="false">
<div class="mx-auto max-w-6xl space-y-6">
    <nav class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('surat-keluar.index') }}" class="hover:text-primary">Surat Keluar</a><x-ikon name="chevron_right" class="text-[14px]" /><span>Buat surat</span>
        @if ($bentuk)<x-ikon name="chevron_right" class="text-[14px]" /><span>{{ $bentuk === 'basah' ? 'Tanpa QR' : 'Ber-QR' }}</span>@endif</nav>

    @if (! $bentuk)
        {{-- Langkah 1: pilih bentuk surat --}}
        <div><h1 class="font-headline-xl text-headline-xl">Buat Surat — Pilih Bentuk</h1>
            <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Pilih cara surat disahkan. Setelah itu semua jenis surat ditampilkan untuk Anda pilih.</p></div>
        <div class="grid gap-4 md:grid-cols-2">
            @foreach ([
                'qr' => ['Surat Ber-QR', 'qr_code_2', 'Tanda tangan elektronik + kode QR yang dapat diverifikasi.', ['Melalui persetujuan: paraf (bila ada) lalu tanda tangan elektronik pejabat', 'PDF memuat QR, spesimen tanda tangan bercap, dan lembar riwayat', 'Nomor surat terbit saat ditandatangani']],
                'basah' => ['Surat Tanpa QR', 'print', 'Dicetak, ditandatangani basah, dan dicap.', ['Tidak ada persetujuan: surat langsung terbit saat tombol Terbitkan ditekan', 'PDF disiapkan kosong (tanpa QR, tanda tangan, maupun stempel)', 'Nomor surat tetap otomatis']],
            ] as $k => [$judul, $ikon, $ket, $butir])
                <a href="{{ route('surat-keluar.buat', ['bentuk' => $k]) }}" class="group flex flex-col justify-between rounded-xl border-2 border-transparent bg-surface-container-lowest p-space-lg shadow-soft transition hover:border-primary-container hover:shadow-popover">
                    <div class="space-y-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-fixed/50 text-primary"><x-ikon :name="$ikon" class="text-[28px]" /></span>
                        <h2 class="font-headline-md text-headline-md font-semibold">{{ $judul }}</h2>
                        <p class="font-body-md text-body-md text-on-surface-variant">{{ $ket }}</p>
                        <ul class="space-y-1.5 font-body-sm text-body-sm text-on-surface-variant">@foreach ($butir as $b)<li class="flex gap-2"><x-ikon name="check_circle" class="mt-0.5 shrink-0 text-[16px] text-primary" /><span>{{ $b }}</span></li>@endforeach</ul>
                    </div>
                    <span class="mt-5 flex items-center gap-0.5 font-label-lg text-label-lg font-semibold text-primary group-hover:underline">Pilih {{ $judul }}<x-ikon name="arrow_forward" class="text-[16px]" /></span>
                </a>
            @endforeach
        </div>
    @else
        {{-- Langkah 2: semua jenis surat untuk bentuk yang dipilih --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><h1 class="font-headline-xl text-headline-xl">Buat Surat — Pilih Jenis</h1>
                <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Pilih jenis surat yang akan dibuat. Anda akan mengisi formulir isian, lalu surat disusun otomatis pada kop resmi.</p></div>
            <div class="inline-flex items-center gap-2 rounded-lg bg-surface-container px-3 py-2 font-label-lg text-label-lg">
                <x-ikon :name="$bentuk === 'basah' ? 'print' : 'qr_code_2'" class="text-[20px] text-primary" />{{ $bentuk === 'basah' ? 'Surat Tanpa QR (langsung terbit)' : 'Surat Ber-QR (melalui persetujuan)' }}
                <a href="{{ route('surat-keluar.buat') }}" class="ml-1 font-normal text-primary underline">ganti bentuk</a>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($formats as $f)
                <a href="{{ route('surat-keluar.isi', [$f, 'bentuk' => $bentuk]) }}" class="group flex flex-col justify-between rounded-xl bg-surface-container-lowest p-space-md shadow-soft transition hover:shadow-popover">
                    <div class="space-y-2.5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-fixed/50 text-primary"><x-ikon :name="$f->ikon" class="text-[22px]" /></span>
                        <h2 class="font-headline-sm text-headline-sm font-semibold">{{ $f->nama }}</h2>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $f->deskripsi ?: 'Isi '.count($f->field_formulir).' isian untuk membuat surat ini.' }}</p>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant">
                        <span class="rounded-full bg-surface-container px-2 py-0.5">{{ $f->kategori ?: 'Umum' }}</span>
                        <span class="rounded-full bg-surface-container px-2 py-0.5">{{ count($f->field_formulir) }} isian</span>
                        <span class="ml-auto flex items-center gap-0.5 font-semibold text-primary group-hover:underline">Isi surat<x-ikon name="arrow_forward" class="text-[14px]" /></span>
                    </div>
                </a>
            @endforeach
            <a href="{{ route('surat-keluar.bebas', ['bentuk' => $bentuk]) }}" class="flex flex-col justify-between rounded-xl border-2 border-dashed border-outline-variant p-space-md transition hover:bg-surface-container-low">
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
    @endif
</div>
</x-layouts::app>
