@php
    $arahLabel = \App\Services\PembukuanService::ARAH;
    $warnaArah = ['masuk' => 'bg-status-diverifikasi-bg text-status-diverifikasi-text', 'keluar' => 'bg-status-selesai-bg text-status-selesai-text', 'lain' => 'bg-surface-container text-on-surface-variant'];
@endphp
<x-layouts::app title="Pembukuan Surat" :cari="false">
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="font-headline-xl text-headline-xl">Pembukuan Surat</h1>
            <p class="mt-1 max-w-3xl font-body-md text-body-md text-on-surface-variant">Buku agenda surat masuk, keluar, dan lainnya berdasarkan <strong>nomor surat</strong>. Surat dari aplikasi tampil otomatis; surat lama dapat dicatat manual atau diimpor dari CSV.</p></div>
        <div class="flex flex-wrap gap-2">
            <x-tombol :href="route('pembukuan.ekspor', array_filter($f))" varian="sekunder" ikon="download">Ekspor CSV</x-tombol>
            @if ($admin)
                <x-tombol :href="route('pembukuan.impor')" varian="sekunder" ikon="upload_file">Impor CSV</x-tombol>
                <x-tombol :href="route('pembukuan.buat')" ikon="add" varian="aksen">Catat Surat</x-tombol>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
        @foreach ($arahLabel as $k => $l)
            <a href="{{ route('pembukuan.index', ['arah' => $k]) }}" class="rounded-lg bg-surface-container-lowest p-space-md shadow-soft transition hover:shadow-popover"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ $l }}</p><p class="mt-1 font-headline-xl text-headline-xl tabular">{{ $ringkas[$k] ?? 0 }}</p></a>
        @endforeach
    </div>

    <form method="get" class="grid gap-2 rounded-lg bg-surface-container-lowest p-space-md shadow-soft sm:grid-cols-2 lg:grid-cols-5">
        <div class="relative lg:col-span-2"><x-ikon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline" /><input name="q" value="{{ $f['q'] ?? '' }}" placeholder="Cari nomor, perihal, pihak, agenda…" class="h-10 w-full rounded-lg border-outline-variant bg-surface-container-lowest pl-10 font-body-md text-body-md focus:ring-2 focus:ring-primary"></div>
        <x-select name="arah" onchange="this.form.submit()"><option value="">Semua jenis buku</option>@foreach ($arahLabel as $k => $l)<option value="{{ $k }}" @selected(($f['arah'] ?? null) === $k)>{{ $l }}</option>@endforeach</x-select>
        <x-select name="tahun" onchange="this.form.submit()"><option value="">Semua tahun</option>@foreach ($tahun as $y)<option value="{{ $y }}" @selected(($f['tahun'] ?? null) == $y)>{{ $y }}</option>@endforeach</x-select>
        <x-select name="sumber" onchange="this.form.submit()"><option value="">Semua sumber</option><option value="aplikasi" @selected(($f['sumber'] ?? null) === 'aplikasi')>Dari aplikasi</option><option value="buku" @selected(($f['sumber'] ?? null) === 'buku')>Catatan pembukuan</option></x-select>
    </form>

    <x-tabel :jumlah="$daftar->count()" kosong="Belum ada catatan pembukuan yang cocok.">
        <x-slot:kepala><th class="px-4">Nomor Surat</th><th>Tanggal</th><th>Pihak & Perihal</th><th>Buku</th><th>Sumber</th><th class="px-4 text-right">Aksi</th></x-slot:kepala>
        @foreach ($daftar as $r)
            <tr>
                <td class="px-4 py-3"><p class="font-label-lg text-label-lg font-bold tabular text-primary">{{ $r->nomor ?: '—' }}</p>@if ($r->no_agenda)<p class="font-body-sm text-body-sm tabular text-on-surface-variant">Agenda: {{ $r->no_agenda }}</p>@endif</td>
                <td class="whitespace-nowrap font-body-sm text-body-sm tabular">{{ $r->tgl_surat ? \Illuminate\Support\Carbon::parse($r->tgl_surat)->format('d/m/Y') : '—' }}@if ($r->tgl_diterima)<p class="text-on-surface-variant">diterima {{ \Illuminate\Support\Carbon::parse($r->tgl_diterima)->format('d/m/Y') }}</p>@endif</td>
                <td class="max-w-md"><p class="font-label-lg text-label-lg">{{ ! $admin && $r->sifat === 'rahasia' ? '(rahasia)' : $r->perihal }}</p><p class="truncate font-body-sm text-body-sm text-on-surface-variant">{{ $r->arah === 'masuk' ? 'Dari' : 'Kepada' }}: {{ $r->pihak ?: '—' }}@if ($r->jenis) • {{ $r->jenis }}@endif</p></td>
                <td><span class="rounded-full px-2.5 py-1 font-label-md text-label-md whitespace-nowrap {{ $warnaArah[$r->arah] ?? $warnaArah['lain'] }}">{{ $arahLabel[$r->arah] ?? $r->arah }}</span></td>
                <td class="font-body-sm text-body-sm text-on-surface-variant">{{ $r->sumber === 'aplikasi' ? 'Aplikasi' : 'Pembukuan' }}</td>
                <td class="px-4 text-right"><div class="flex justify-end gap-1.5">
                    @if ($r->sumber === 'buku' && $admin)
                        <x-tombol :href="route('pembukuan.ubah', $r->id)" varian="sekunder" ukuran="sm" ikon="edit">Ubah</x-tombol>
                        <form method="post" action="{{ route('pembukuan.hapus', $r->id) }}" onsubmit="return confirm('Hapus catatan pembukuan ini?')">@csrf @method('DELETE')<x-tombol type="submit" varian="bahaya" ukuran="sm" ikon="delete">Hapus</x-tombol></form>
                    @elseif ($r->sumber === 'aplikasi' && $r->arah === 'masuk')
                        <x-tombol :href="route('surat-masuk.show', $r->id)" varian="sekunder" ukuran="sm">Buka</x-tombol>
                    @elseif ($r->sumber === 'aplikasi' && $r->file_pdf)
                        <x-tombol :href="route('surat.pdf', $r->id)" varian="sekunder" ukuran="sm" ikon="download">PDF</x-tombol>
                    @endif
                </div></td>
            </tr>
        @endforeach
        <x-slot:kaki>{{ $daftar->links() }}</x-slot:kaki>
    </x-tabel>
</div>
</x-layouts::app>
