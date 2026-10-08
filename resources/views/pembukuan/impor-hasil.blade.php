<x-layouts::app title="Hasil Impor Pembukuan" :cari="false">
<div class="mx-auto max-w-3xl space-y-5">
    <h1 class="font-headline-xl text-headline-xl">Impor Selesai</h1>
    <x-peringatan jenis="sukses" judul="{{ $hasil['tersimpan'] }} surat dicatat di pembukuan{{ $hasil['dilewati'] ? ', '.$hasil['dilewati'].' dilewati (sudah ada)' : '' }}" />
    @if ($hasil['penghitung'])
        <x-kartu judul="Penghitung nomor otomatis disesuaikan" ikon="numbers">
            <ul class="space-y-1 font-body-md text-body-md">@foreach ($hasil['penghitung'] as $p)<li>Unit <strong>{{ $p['kode'] }}</strong> tahun {{ $p['tahun'] }}: nomor otomatis berikutnya <strong class="tabular">{{ str_pad((string) ($p['maks'] + 1), 3, '0', STR_PAD_LEFT) }}</strong> (atau lebih bila penghitung sudah lebih tinggi).</li>@endforeach</ul>
        </x-kartu>
    @endif
    <x-tombol :href="route('pembukuan.index')" ikon="menu_book">Ke Pembukuan</x-tombol>
</div>
</x-layouts::app>
