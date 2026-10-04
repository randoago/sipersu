<x-layouts::app title="Lacak Status" :cari="false">
<div class="mx-auto max-w-4xl space-y-6">
    <section class="rounded-xl bg-gradient-to-br from-primary-fixed/30 via-surface-container-lowest to-surface-container-lowest p-space-lg shadow-soft">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-container/10 px-3 py-1 font-label-sm text-label-sm text-primary"><x-ikon name="verified_user" class="text-[14px]" />Sistem Pelacakan Resmi FT-UMB</span>
        <h1 class="mt-3 font-headline-xl text-headline-xl text-on-surface">Lacak Status Permohonan Surat</h1>
        <p class="mt-1 max-w-xl font-body-md text-body-md text-on-surface-variant">Pantau alur verifikasi berkas permohonan surat akademik secara transparan dan berjenjang dari Tata Usaha hingga Dekanat.</p>
        <form method="get" action="{{ route('layanan.lacak') }}" class="mt-5 flex flex-col gap-2 rounded-xl bg-surface-container-lowest p-2 shadow-soft sm:flex-row">
            <div class="relative flex-1"><x-ikon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline" />
                <input name="kode" value="{{ $kode }}" placeholder="Contoh: REG-2026-000001" class="h-11 w-full rounded-lg border-0 bg-transparent pl-10 font-body-md text-body-md focus:ring-2 focus:ring-primary"></div>
            <x-tombol type="submit" ukuran="lg" ikon="manage_search">Cari Berkas</x-tombol>
        </form>
        <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">Format kode: <span class="font-semibold tabular">REG-TAHUN-NOMOR</span> • Terakhir diperbarui: {{ now()->translatedFormat('j M Y, H:i') }} WITA</p>
    </section>
    <section class="space-y-3">
        <h2 class="font-headline-md text-headline-md">Pengajuan Terbaru Anda</h2>
        <div class="grid gap-3 lg:grid-cols-2">@forelse ($terbaru as $p)<x-kartu-pengajuan :p="$p" />@empty<p class="text-on-surface-variant">Belum ada pengajuan.</p>@endforelse</div>
    </section>
</div>
</x-layouts::app>
