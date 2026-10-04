<x-layouts::app title="Surat Keluar" :cari="false">
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="font-headline-xl text-headline-xl">Surat Keluar</h1><p class="mt-1 font-body-md text-body-md text-on-surface-variant">Draf, paraf, tanda tangan elektronik, dan nomor otomatis.</p></div>
        <x-tombol :href="route('surat-keluar.buat')" ikon="add" varian="aksen">Buat Surat</x-tombol>
    </div>
    <form method="get" class="flex flex-col gap-2 sm:flex-row">
        <x-input name="q" :value="$f['q'] ?? ''" placeholder="Cari perihal, nomor, atau tujuan…" ikon="search" class="flex-1" />
        <x-select name="status" class="sm:w-52" onchange="this.form.submit()"><option value="">Semua status</option>@foreach ($statusList as $k => $l)<option value="{{ $k }}" @selected(($f['status'] ?? null) === $k)>{{ $l }}</option>@endforeach</x-select>
        <x-tombol type="submit" varian="sekunder" ikon="filter_list">Terapkan</x-tombol>
    </form>
    <x-tabel :jumlah="$daftar->count()" kosong="Belum ada surat keluar.">
        <x-slot:kepala><th class="px-4">Nomor / Klasifikasi</th><th>Perihal & Tujuan</th><th>Penandatangan</th><th>Status</th><th class="px-4 text-right">Aksi</th></x-slot:kepala>
        @foreach ($daftar as $s)
            <tr>
                <td class="px-4 py-3"><p class="font-label-lg text-label-lg tabular {{ $s->nomor ? 'text-primary' : 'text-on-surface-variant' }}">{{ $s->nomor ?? '— belum terbit' }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">Klas: {{ $s->klasifikasi?->kode }}</p></td>
                <td class="max-w-md"><p class="font-label-lg text-label-lg">{{ $s->perihal }}</p><p class="truncate font-body-sm text-body-sm text-on-surface-variant">Kepada: {{ $s->asal_tujuan }}</p></td>
                <td class="font-body-sm text-body-sm">{{ $s->jabatan?->nama }}</td>
                <td><span @class(['rounded-full border px-2.5 py-1 font-label-md text-label-md whitespace-nowrap', 'bg-status-diajukan-bg text-status-diajukan-text border-status-diajukan-border' => $s->status === 'draf', 'bg-status-diverifikasi-bg text-status-diverifikasi-text border-status-diverifikasi-border' => $s->status === 'menunggu_paraf', 'bg-status-disetujui-bg text-status-disetujui-text border-status-disetujui-border' => $s->status === 'menunggu_ttd', 'bg-status-ditandatangani-bg text-status-ditandatangani-text border-status-ditandatangani-border' => $s->status === 'ditandatangani', 'bg-status-ditolak-bg text-status-ditolak-text border-status-ditolak-border' => $s->status === 'batal'])>{{ $statusList[$s->status] ?? $s->status }}</span></td>
                <td class="px-4 text-right"><x-tombol :href="route('surat-keluar.show', $s)" varian="sekunder" ukuran="sm">Buka</x-tombol></td>
            </tr>
        @endforeach
        <x-slot:kaki>{{ $daftar->links() }}</x-slot:kaki>
    </x-tabel>
</div>
</x-layouts::app>
