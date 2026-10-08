@php $valid = $ringkas['baru']; $warna = ['baru' => 'bg-status-selesai-bg text-status-selesai-text', 'lewati' => 'bg-surface-container text-on-surface-variant', 'galat' => 'bg-status-ditolak-bg text-status-ditolak-text']; $label = ['baru' => 'Akan dicatat', 'lewati' => 'Dilewati', 'galat' => 'Galat']; @endphp
<x-layouts::app title="Periksa Impor Pembukuan" :cari="false">
<div class="mx-auto max-w-6xl space-y-5">
    <h1 class="font-headline-xl text-headline-xl">Hasil Pemeriksaan Berkas</h1>
    <p class="font-body-md text-body-md text-on-surface-variant">Berkas <strong>{{ $namaBerkas }}</strong> • belum ada data yang disimpan. Periksa ringkasan lalu konfirmasi.</p>
    <div class="grid grid-cols-3 gap-3">
        @foreach (['baru' => 'Akan dicatat', 'lewati' => 'Dilewati', 'galat' => 'Galat (tidak diimpor)'] as $k => $l)
            <div class="rounded-lg bg-surface-container-lowest p-space-md shadow-soft"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">{{ $l }}</p><p class="mt-1 font-headline-xl text-headline-xl tabular {{ $k === 'galat' && $ringkas[$k] ? 'text-error' : '' }}">{{ $ringkas[$k] }}</p></div>
        @endforeach
    </div>
    @if ($kolom_diabaikan)<x-peringatan jenis="peringatan" judul="Kolom tidak dikenal diabaikan">{{ implode(', ', $kolom_diabaikan) }}</x-peringatan>@endif
    @if ($ringkas['galat'])<x-peringatan jenis="bahaya" judul="{{ $ringkas['galat'] }} baris bermasalah">Baris bergalat <strong>tidak akan diimpor</strong>. Impor baris yang valid sekarang, lalu perbaiki sisanya dan unggah ulang.</x-peringatan>@endif

    <form method="post" action="{{ route('pembukuan.impor.proses') }}" x-data="{ proses: false }" @submit="proses = true" class="space-y-5">@csrf
        @if ($penghitung)
            <x-kartu judul="Penghitung nomor otomatis" ikon="numbers" deskripsi="Surat keluar yang nomornya mengikuti pola penomoran.">
                <label class="flex cursor-pointer items-start gap-2 font-body-md text-body-md"><input type="checkbox" name="sinkron" value="1" checked class="mt-1 h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"><span><strong>Sesuaikan penghitung</strong> agar nomor otomatis berikutnya melanjutkan nomor di CSV.</span></label>
                <ul class="mt-3 space-y-1 font-body-sm text-body-sm text-on-surface-variant">@foreach ($penghitung as $p)<li>Unit <strong class="text-on-surface">{{ $p['kode'] }}</strong> tahun {{ $p['tahun'] }}: terakhir {{ str_pad((string) $p['sekarang'], 3, '0', STR_PAD_LEFT) }} → {{ $p['maks'] > $p['sekarang'] ? str_pad((string) $p['maks'], 3, '0', STR_PAD_LEFT).' (nomor otomatis berikutnya '.str_pad((string) ($p['maks'] + 1), 3, '0', STR_PAD_LEFT).')' : 'tidak berubah' }}</li>@endforeach</ul>
            </x-kartu>
        @endif
        <x-tabel :jumlah="count($baris)">
            <x-slot:kepala><th class="px-4">Baris</th><th>Buku</th><th>Nomor</th><th>Tanggal</th><th>Perihal</th><th>Status</th><th class="pr-4">Keterangan</th></x-slot:kepala>
            @foreach ($baris as $b)
                <tr class="{{ $b['status'] === 'galat' ? 'bg-error-container/20' : '' }}">
                    <td class="px-4 py-2.5 font-body-sm text-body-sm tabular">{{ $b['no'] }}</td>
                    <td class="font-body-sm text-body-sm">{{ \App\Services\PembukuanService::ARAH[$b['arah']] ?? '—' }}</td>
                    <td class="font-body-sm text-body-sm tabular">{{ $b['nomor'] }}</td>
                    <td class="whitespace-nowrap font-body-sm text-body-sm tabular">{{ $b['tanggal'] ? \Illuminate\Support\Carbon::parse($b['tanggal'])->format('d/m/Y') : '—' }}</td>
                    <td class="max-w-xs truncate font-body-md text-body-md">{{ $b['perihal'] }}</td>
                    <td><span class="rounded-full px-2.5 py-1 font-label-md text-label-md {{ $warna[$b['status']] }}">{{ $label[$b['status']] }}</span></td>
                    <td class="max-w-xs pr-4 font-body-sm text-body-sm {{ $b['status'] === 'galat' ? 'text-error' : 'text-on-surface-variant' }}">{{ $b['pesan'] }}</td>
                </tr>
            @endforeach
        </x-tabel>
        <div class="flex flex-wrap justify-end gap-2">
            <x-tombol :href="route('pembukuan.impor')" varian="sekunder" ikon="arrow_back">Unggah Ulang</x-tombol>
            @if ($valid)<x-tombol type="submit" ikon="task_alt" x-bind:disabled="proses"><span x-text="proses ? 'Mengimpor…' : 'Impor {{ $valid }} Surat'"></span></x-tombol>@endif
        </div>
    </form>
</div>
</x-layouts::app>
