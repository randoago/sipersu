<x-layouts::app title="Log Aktivitas" :cari="false">
<div class="mx-auto max-w-6xl">
    <h1 class="mb-1 font-headline-xl text-headline-xl">Pengaturan</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">Catatan aksi penting, termasuk setiap pemindaian verifikasi QR.</p>
    @include('pengaturan._tab')
    <form method="get" class="mb-4 flex flex-col gap-2 sm:flex-row">
        <x-input name="q" :value="$f['q'] ?? ''" placeholder="Cari deskripsi…" ikon="search" class="flex-1" />
        <x-select name="aksi" class="sm:w-60" onchange="this.form.submit()"><option value="">Semua aksi</option>@foreach ($aksiList as $a)<option @selected(($f['aksi'] ?? null) === $a)>{{ $a }}</option>@endforeach</x-select>
        <x-tombol type="submit" varian="sekunder" ikon="filter_list">Terapkan</x-tombol>
    </form>
    <x-tabel :jumlah="$log->count()">
        <x-slot:kepala><th class="px-4">Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th><th class="pr-4">IP</th></x-slot:kepala>
        @foreach ($log as $l)
            <tr><td class="whitespace-nowrap px-4 py-2.5 font-body-sm text-body-sm tabular">{{ $l->created_at->format('d/m/Y H:i:s') }}</td>
                <td class="font-body-sm text-body-sm">{{ $l->user?->nama ?? '—' }}</td>
                <td><span class="rounded bg-surface-container px-1.5 py-0.5 font-label-sm text-label-sm">{{ $l->aksi }}</span></td>
                <td class="font-body-sm text-body-sm">{{ $l->deskripsi }}</td><td class="pr-4 font-body-sm text-body-sm tabular text-on-surface-variant">{{ $l->ip }}</td></tr>
        @endforeach
        <x-slot:kaki>{{ $log->links() }}</x-slot:kaki>
    </x-tabel>
</div>
</x-layouts::app>
