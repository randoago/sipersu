<x-layouts::app title="Surat Masuk" :cari="false">
<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><div class="flex items-center gap-2"><h1 class="font-headline-xl text-headline-xl">Surat Masuk</h1><span class="rounded-full bg-primary-fixed px-2.5 py-1 font-label-md text-label-md text-primary">{{ $total }} surat</span></div>
            <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Pencatatan surat yang diterima fakultas, lengkap dengan nomor agenda otomatis dan pindaian.</p></div>
        @if ($bolehCatat)<div class="flex gap-2"><x-tombol :href="route('format-surat.index', ['sasaran' => 'masuk'])" varian="sekunder" ikon="edit_document">Atur Format</x-tombol><x-tombol :href="route('surat-masuk.buat')" ikon="add" varian="aksen">Catat Surat Masuk</x-tombol></div>@endif
    </div>
    <form method="get" class="grid gap-2 rounded-lg bg-surface-container-lowest p-space-md shadow-soft sm:grid-cols-2 lg:grid-cols-5">
        <div class="relative lg:col-span-2"><x-ikon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline" /><input name="q" value="{{ $f['q'] ?? '' }}" placeholder="Cari agenda, nomor asal, pengirim, perihal…" class="h-10 w-full rounded-lg border-outline-variant bg-surface-container-lowest pl-10 font-body-md text-body-md focus:ring-2 focus:ring-primary"></div>
        <x-select name="tahun" onchange="this.form.submit()"><option value="">Semua tahun</option>@foreach ($tahunList as $y)<option value="{{ $y }}" @selected(($f['tahun'] ?? null) == $y)>{{ $y }}</option>@endforeach</x-select>
        <x-select name="sifat" onchange="this.form.submit()"><option value="">Semua sifat</option>@foreach ($sifat as $k => $l)<option value="{{ $k }}" @selected(($f['sifat'] ?? null) === $k)>{{ $l }}</option>@endforeach</x-select>
        <x-select name="jenis" onchange="this.form.submit()"><option value="">Semua jenis</option>@foreach ($formats as $j)<option value="{{ $j->id }}" @selected(($f['jenis'] ?? null) == $j->id)>{{ $j->nama }}</option>@endforeach</x-select>
    </form>
    <x-tabel :jumlah="$daftar->count()" kosong="Belum ada surat masuk yang cocok.">
        <x-slot:kepala><th class="px-4">No. Agenda & Nomor Asal</th><th>Tgl Surat / Diterima</th><th>Perihal</th><th>Asal & Sifat</th><th class="text-center">Pindaian</th><th class="px-4 text-right">Aksi</th></x-slot:kepala>
        @foreach ($daftar as $s)
            <tr>
                <td class="px-4 py-3"><p class="font-label-lg text-label-lg font-bold tabular text-primary">{{ $s->no_agenda }}</p><p class="font-body-sm text-body-sm text-on-surface-variant tabular">{{ $s->data['nomor_asal'] ?? '-' }}</p>@if ($s->klasifikasi)<span class="mt-1 inline-block rounded bg-surface-container px-1.5 py-0.5 font-label-sm text-label-sm text-on-surface-variant">Klas: {{ $s->klasifikasi->kode }}</span>@endif</td>
                <td class="font-body-sm text-body-sm tabular">{{ $s->tgl_surat?->translatedFormat('j M Y') }}<br><span class="text-on-surface-variant">Diterima {{ \Illuminate\Support\Carbon::parse($s->data['tgl_diterima'] ?? $s->created_at)->translatedFormat('j M Y') }}</span></td>
                <td class="max-w-sm"><p class="font-label-lg text-label-lg">{{ $s->perihal }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $s->jenis?->nama }}</p></td>
                <td><p class="font-body-md text-body-md">{{ $s->asal_tujuan }}</p><span @class(['mt-1 inline-block rounded-full px-2 py-0.5 font-label-sm text-label-sm', 'bg-status-ditolak-bg text-status-ditolak-text' => in_array($s->sifat, ['segera', 'rahasia']), 'bg-status-disetujui-bg text-status-disetujui-text' => $s->sifat === 'penting', 'bg-surface-container text-on-surface-variant' => $s->sifat === 'biasa'])>{{ $sifat[$s->sifat] ?? $s->sifat }}</span></td>
                <td class="text-center">@if ($s->lampiran->isNotEmpty())<x-ikon name="picture_as_pdf" class="text-[22px] text-primary" />@else<span class="text-outline">—</span>@endif</td>
                <td class="px-4 text-right"><x-tombol :href="route('surat-masuk.show', $s)" varian="sekunder" ukuran="sm">Detail</x-tombol></td>
            </tr>
        @endforeach
        <x-slot:kaki>{{ $daftar->links() }}</x-slot:kaki>
    </x-tabel>
</div>
</x-layouts::app>
