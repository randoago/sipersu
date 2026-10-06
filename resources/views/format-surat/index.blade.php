<x-layouts::app title="Format Surat" :cari="false">
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="font-headline-xl text-headline-xl">Format Surat</h1>
            <p class="mt-1 max-w-2xl font-body-md text-body-md text-on-surface-variant">Atur format untuk <strong>surat keluar</strong> (isian, templat, penandatangan, QR/tanpa QR) dan <strong>surat masuk</strong> (kolom khusus saat TU mencatat). Format aktif langsung muncul di <strong>Surat Keluar → Buat Surat</strong>, <strong>Surat Masuk → Catat Surat</strong>, atau katalog mahasiswa.</p></div>
        <x-tombol :href="route('format-surat.buat')" ikon="add" varian="aksen">Tambah Format Surat</x-tombol>
    </div>
    <div class="flex gap-1 rounded-lg bg-surface-container p-1 sm:w-max">
        @foreach ([null => 'Semua', 'staf' => 'Surat Keluar', 'masuk' => 'Surat Masuk', 'mahasiswa' => 'Layanan Mahasiswa'] as $k => $l)
            <a href="{{ route('format-surat.index', $k ? ['sasaran' => $k] : []) }}" @class(['rounded-md px-3.5 py-2 font-label-md text-label-md transition', 'bg-surface-container-lowest font-semibold text-primary shadow-sm' => $sasaran === $k, 'text-on-surface-variant hover:text-on-surface' => $sasaran !== $k])>{{ $l }}</a>
        @endforeach
    </div>
    <x-tabel :jumlah="$daftar->count()" kosong="Belum ada format surat.">
        <x-slot:kepala><th class="px-4">Format & Kegunaan</th><th>Jenis</th><th>Isian</th><th>Klasifikasi</th><th>Penandatangan</th><th>Bentuk</th><th>Status</th><th class="px-4 text-right">Aksi</th></x-slot:kepala>
        @foreach ($daftar as $f)
            <tr>
                <td class="max-w-xs px-4 py-3"><div class="flex items-center gap-2"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-fixed/50 text-primary"><x-ikon :name="$f->ikon" class="text-[18px]" /></span><div class="min-w-0"><p class="truncate font-label-lg text-label-lg">{{ $f->nama }}</p><p class="truncate font-body-sm text-body-sm text-on-surface-variant">{{ $f->deskripsi ?: '—' }}</p></div></div></td>
                <td><span class="rounded-full bg-surface-container px-2.5 py-1 font-label-md text-label-md whitespace-nowrap">{{ ['staf' => 'Surat Keluar', 'masuk' => 'Surat Masuk', 'mahasiswa' => 'Layanan Mhs'][$f->sasaran] }}</span></td>
                <td class="whitespace-nowrap font-body-sm text-body-sm tabular">{{ count($f->field_formulir) }} isian</td>
                <td class="whitespace-nowrap font-body-sm text-body-sm tabular">{{ $f->klasifikasi?->kode ?? '—' }}</td>
                <td class="max-w-[180px] truncate font-body-sm text-body-sm">{{ $f->sasaran === 'masuk' ? '—' : $f->penandatanganJabatan?->nama }}</td>
                <td>@if ($f->sasaran === 'masuk')<span class="font-body-sm text-body-sm text-on-surface-variant">Agenda otomatis</span>@else<span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-1 font-label-md text-label-md"><x-ikon :name="$f->mode_ttd === 'basah' ? 'print' : 'qr_code_2'" class="text-[14px] text-primary" />{{ $f->mode_ttd === 'basah' ? 'Tanpa QR' : 'Ber-QR' }}</span>@if ($f->langsungTerbit())<span class="ml-1 rounded-full bg-status-selesai-bg px-2 py-1 font-label-md text-label-md text-status-selesai-text">Tanpa persetujuan</span>@endif @endif</td>
                <td>@if ($f->aktif)<span class="rounded-full bg-status-selesai-bg px-2.5 py-1 font-label-md text-label-md text-status-selesai-text">Aktif</span>@else<span class="rounded-full bg-surface-container px-2.5 py-1 font-label-md text-label-md text-on-surface-variant">Nonaktif</span>@endif</td>
                <td class="px-4 text-right"><div class="flex justify-end gap-1.5">
                    <x-tombol :href="route('format-surat.ubah', $f)" varian="sekunder" ukuran="sm" ikon="edit">Ubah</x-tombol>
                    <form method="post" action="{{ route('format-surat.salin', $f) }}">@csrf<x-tombol type="submit" varian="lembut" ukuran="sm" ikon="content_copy" title="Salin format">Salin</x-tombol></form>
                    <form method="post" action="{{ route('format-surat.aktif', $f) }}">@csrf<x-tombol type="submit" varian="lembut" ukuran="sm">{{ $f->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</x-tombol></form>
                </div></td>
            </tr>
        @endforeach
    </x-tabel>
</div>
</x-layouts::app>
