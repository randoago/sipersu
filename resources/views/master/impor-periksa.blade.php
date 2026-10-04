@php $valid = $ringkas['baru'] + $ringkas['perbarui']; $warna = ['baru' => 'bg-status-selesai-bg text-status-selesai-text', 'perbarui' => 'bg-status-diverifikasi-bg text-status-diverifikasi-text', 'lewati' => 'bg-surface-container text-on-surface-variant', 'galat' => 'bg-status-ditolak-bg text-status-ditolak-text']; $label = ['baru' => 'Baru', 'perbarui' => 'Perbarui', 'lewati' => 'Dilewati', 'galat' => 'Galat']; @endphp
<x-layouts::app title="Periksa Impor CSV" :cari="false">
<div class="mx-auto max-w-6xl space-y-5">
    <h1 class="font-headline-xl text-headline-xl">Hasil Pemeriksaan Berkas</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">Berkas <strong>{{ $namaBerkas }}</strong> • belum ada data yang disimpan. Periksa ringkasan lalu konfirmasi.</p>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach (['baru' => 'Akan dibuat', 'perbarui' => 'Akan diperbarui', 'lewati' => 'Dilewati', 'galat' => 'Galat (tidak diimpor)'] as $k => $l)
            <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-soft"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ $l }}</p><p class="mt-1 font-headline-xl text-headline-xl tabular {{ $k === 'galat' && $ringkas[$k] ? 'text-error' : '' }}">{{ $ringkas[$k] }}</p></div>
        @endforeach
    </div>
    @if ($kolom_diabaikan)<x-peringatan jenis="peringatan" judul="Kolom tidak dikenal diabaikan">{{ implode(', ', $kolom_diabaikan) }}</x-peringatan>@endif
    @if ($ringkas['galat'])<x-peringatan jenis="bahaya" judul="{{ $ringkas['galat'] }} baris bermasalah">Baris bergalat <strong>tidak akan diimpor</strong>. Anda dapat mengimpor baris yang valid sekarang, lalu memperbaiki sisanya di berkas dan mengunggah ulang.</x-peringatan>@endif

    <x-tabel :jumlah="count($baris)">
        <x-slot:kepala><th class="px-4">Baris</th><th>NPM/NIDN</th><th>Nama</th><th>Peran</th><th>Status</th><th class="pr-4">Keterangan</th></x-slot:kepala>
        @foreach ($baris as $b)
            <tr class="{{ $b['status'] === 'galat' ? 'bg-error-container/20' : '' }}">
                <td class="px-4 py-2.5 font-body-sm text-body-sm tabular">{{ $b['no'] }}</td>
                <td class="font-body-sm text-body-sm tabular">{{ $b['nomor_induk'] }}</td>
                <td class="font-body-md text-body-md">{{ $b['nama'] }}</td>
                <td class="font-body-sm text-body-sm">{{ collect($b['peran'])->map(fn ($v) => \App\Enums\Peran::from($v)->label())->implode(' + ') ?: '—' }}</td>
                <td><span class="rounded-full px-2.5 py-1 font-label-md text-label-md {{ $warna[$b['status']] }}">{{ $label[$b['status']] }}</span></td>
                <td class="max-w-xs pr-4 font-body-sm text-body-sm {{ $b['status'] === 'galat' ? 'text-error' : 'text-on-surface-variant' }}">{{ $b['pesan'] ?: ($b['data']['password'] === '' && $b['status'] === 'baru' ? 'Kata sandi acak akan dibuat' : '') }}</td>
            </tr>
        @endforeach
    </x-tabel>

    <div class="flex flex-wrap justify-end gap-2">
        <x-tombol :href="route('master.impor')" varian="sekunder" ikon="arrow_back">Unggah Ulang</x-tombol>
        @if ($valid)
            <form method="post" action="{{ route('master.impor.proses') }}" x-data="{ proses: false }" @submit="proses = true">@csrf
                <x-tombol type="submit" ikon="task_alt" x-bind:disabled="proses"><span x-text="proses ? 'Mengimpor…' : 'Impor {{ $valid }} Pengguna'"></span></x-tombol>
            </form>
        @endif
    </div>
</div>
</x-layouts::app>
