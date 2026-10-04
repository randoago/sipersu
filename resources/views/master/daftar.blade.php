<x-layouts::app :title="'Master Data — '.$d['judul']" :cari="false">
<div class="mx-auto max-w-6xl">
    <h1 class="mb-1 font-headline-xl text-headline-xl">Master Data</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">Kelola data referensi sistem. Data tidak dihapus, hanya dinonaktifkan, agar riwayat surat tetap utuh.</p>
    @include('master._tab')
    @if ($entitas === 'pengguna')
        <div class="mb-4 inline-flex gap-1 rounded-lg bg-surface-container p-1" role="tablist" aria-label="Kelompok pengguna">
            @foreach (['dosen' => 'Dosen, Tendik & Pejabat', 'mahasiswa' => 'Mahasiswa'] as $kode => $nama)
                <a href="{{ route('master.daftar', ['entitas' => 'pengguna', 'kelompok' => $kode]) }}" @class(['rounded-md px-3.5 py-1.5 font-label-md text-label-md transition', 'bg-surface-container-lowest font-semibold text-primary shadow-sm' => $kelompok === $kode, 'text-on-surface-variant hover:text-on-surface' => $kelompok !== $kode])>{{ $nama }}</a>
            @endforeach
        </div>
    @endif
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <form method="get" class="relative sm:w-80">@if ($entitas === 'pengguna')<input type="hidden" name="kelompok" value="{{ $kelompok }}">@endif<x-ikon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline" /><input name="q" value="{{ $q }}" placeholder="Cari {{ mb_strtolower($d['judul']) }}…" class="h-10 w-full rounded-lg border-outline-variant bg-surface-container-lowest pl-10 font-body-md text-body-md focus:ring-2 focus:ring-primary"></form>
        <div class="flex gap-2">
            @if ($entitas === 'pengguna')<x-tombol :href="route('master.impor')" varian="sekunder" ikon="upload_file">Impor CSV</x-tombol>@endif
            <x-tombol :href="route('master.buat', $entitas)" ikon="add">Tambah {{ $d['judul'] }}</x-tombol>
        </div>
    </div>
    <x-tabel :jumlah="$daftar->count()">
        <x-slot:kepala>@foreach ($d['kolom'] as $k)<th class="px-4">{{ $k[0] }}</th>@endforeach<th>Status</th><th class="px-4 text-right">Aksi</th></x-slot:kepala>
        @foreach ($daftar as $m)
            <tr>
                @foreach ($d['kolom'] as $k)<td class="px-4 py-3 font-body-md text-body-md {{ $k[2] ?? '' }}">{{ $k[1]($m) }}</td>@endforeach
                <td>@if ($m->aktif)<span class="rounded-full bg-status-selesai-bg px-2.5 py-1 font-label-md text-label-md text-status-selesai-text">Aktif</span>@else<span class="rounded-full bg-surface-container px-2.5 py-1 font-label-md text-label-md text-on-surface-variant">Nonaktif</span>@endif</td>
                <td class="px-4 text-right"><div class="flex justify-end gap-1.5">
                    <x-tombol :href="route('master.ubah', [$entitas, $m->id])" varian="sekunder" ukuran="sm" ikon="edit">Ubah</x-tombol>
                    <form method="post" action="{{ route('master.aktif', [$entitas, $m->id]) }}">@csrf<x-tombol type="submit" varian="lembut" ukuran="sm">{{ $m->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</x-tombol></form>
                </div></td>
            </tr>
        @endforeach
        <x-slot:kaki>{{ $daftar->links() }}</x-slot:kaki>
    </x-tabel>
</div>
</x-layouts::app>
