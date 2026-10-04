<x-layouts::app title="Beranda" :cari="false">
@php $u = auth()->user(); @endphp
<div class="mx-auto max-w-5xl space-y-6" x-data="{ q: '' }">
    {{-- 1. Sapaan --}}
    <section class="space-y-3 rounded-xl bg-surface-container-low p-space-md shadow-sm lg:p-space-lg">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <h1 class="font-headline-lg-mobile text-headline-lg-mobile font-bold tracking-tight text-on-surface lg:font-headline-lg lg:text-headline-lg">Halo, {{ $u->nama }}</h1><span class="text-lg">👋</span>
                </div>
                <p class="font-body-sm text-body-sm font-medium text-on-surface-variant tabular">NIM: {{ $u->nomor_induk }} • {{ $u->prodi?->nama }} ({{ $u->prodi?->jenjang }})</p>
            </div>
            <span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full bg-secondary-container/20 px-2 py-1 font-label-sm text-label-sm text-on-secondary-fixed-variant">Semester {{ \App\Support\TahunAkademik::saatIni() }}</span>
        </div>
        <div class="relative w-full lg:max-w-xl">
            <x-ikon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline" />
            <input x-model="q" type="search" placeholder="Cari jenis surat atau nomor pengajuan…" class="h-10 w-full rounded-lg border-0 bg-surface-container-lowest pl-9 pr-3 font-body-sm text-body-sm text-on-surface placeholder:text-outline focus:ring-2 focus:ring-primary">
        </div>
    </section>

    {{-- 2. Banner --}}
    <section class="flex items-start gap-3 rounded-xl bg-gradient-to-r from-primary/10 via-secondary-container/15 to-primary-fixed/20 p-space-md shadow-sm">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary text-on-primary shadow-sm"><x-ikon name="verified" class="text-[22px]" /></div>
        <div class="min-w-0 space-y-1">
            <div class="flex items-center gap-2"><h2 class="font-headline-sm text-headline-sm font-bold text-primary">Layanan Persuratan Online Aktif</h2><span class="inline-block h-2 w-2 animate-pulse rounded-full bg-primary-container"></span></div>
            <p class="font-body-sm text-body-sm leading-relaxed text-on-surface-variant">Pengajuan surat dapat diproses secara elektronik dengan validasi QR-code resmi FT-UMB.</p>
        </div>
    </section>

    {{-- 3. Katalog --}}
    <section class="space-y-3">
        <div class="flex items-center justify-between">
            <div><h2 class="font-headline-md text-headline-md font-bold text-on-surface">Layanan Pengajuan Surat</h2><p class="font-body-sm text-body-sm text-on-surface-variant">Pilih jenis surat yang ingin diajukan</p></div>
            <a href="{{ route('layanan.katalog') }}" class="flex items-center gap-0.5 font-label-sm text-label-sm font-semibold text-primary hover:underline">Lihat Semua<x-ikon name="chevron_right" class="text-[16px]" /></a>
        </div>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
            @foreach ($jenis as $i => $j)
                <x-kartu-jenis-surat :jenis="$j" :indeks="$i" x-show="q === '' || $el.dataset.cari.includes(q.toLowerCase())" />
            @endforeach
        </div>
    </section>

    {{-- 4. Pengajuan saya --}}
    <section class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2"><h2 class="font-headline-md text-headline-md font-bold text-on-surface">Pengajuan Saya</h2>
                @if ($berjalan)<span class="rounded-full bg-secondary-fixed px-2 py-0.5 font-label-sm text-label-sm font-bold text-on-secondary-fixed-variant">{{ $berjalan }} Berjalan</span>@endif</div>
            <a href="{{ route('layanan.riwayat') }}" class="flex items-center gap-0.5 font-label-sm text-label-sm font-semibold text-primary hover:underline">Riwayat Lengkap<x-ikon name="chevron_right" class="text-[16px]" /></a>
        </div>
        <div class="grid gap-3 lg:grid-cols-2">
            @forelse ($pengajuan as $p)
                <x-kartu-pengajuan :p="$p" x-show="q === '' || '{{ mb_strtolower($p->kode.' '.$p->jenis->nama) }}'.includes(q.toLowerCase())" />
            @empty
                <div class="rounded-xl bg-surface-container-lowest p-6 text-center font-body-md text-on-surface-variant shadow-sm lg:col-span-2">Belum ada pengajuan. Pilih jenis surat di atas untuk memulai.</div>
            @endforelse
        </div>
    </section>

    {{-- 5. Bantuan --}}
    <section class="flex items-center justify-between gap-3 rounded-xl bg-surface-container-low p-space-md">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-secondary-container text-on-secondary-container"><x-ikon name="support_agent" class="text-[22px]" /></div>
            <div class="min-w-0"><h3 class="font-headline-sm text-headline-sm font-bold">Butuh Bantuan Persuratan?</h3><p class="truncate font-body-sm text-body-sm text-on-surface-variant">Hubungi Loket Akademik FT-UMB</p></div>
        </div>
        <x-tombol :href="route('bantuan')" ikon="open_in_new" ukuran="sm">Bantuan</x-tombol>
    </section>
</div>
</x-layouts::app>
