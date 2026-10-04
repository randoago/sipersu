@php $scan = $s->lampiran->first(); $d = $s->data; @endphp
<x-layouts::app :title="'Agenda '.$s->no_agenda" :cari="false">
<div class="mx-auto max-w-6xl space-y-5">
    <nav class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('surat-masuk.index') }}" class="hover:text-primary">Surat Masuk</a><x-ikon name="chevron_right" class="text-[14px]" /><span class="tabular text-on-surface">{{ $s->no_agenda }}</span></nav>
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><p class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Nomor Agenda</p>
            <h1 class="font-headline-xl text-headline-xl tabular text-primary">{{ $s->no_agenda }}</h1>
            <p class="mt-1 font-headline-sm text-headline-sm">{{ $s->perihal }}</p></div>
        <div class="flex gap-2">@if ($bolehUbah)<x-tombol :href="route('surat-masuk.ubah', $s)" varian="sekunder" ikon="edit">Ubah</x-tombol>@endif @if ($scan)<x-tombol :href="route('lampiran.unduh', $scan)" ikon="open_in_new" target="_blank">Buka Pindaian</x-tombol>@endif</div>
    </header>
    <div class="grid gap-6 lg:grid-cols-5">
        <x-kartu judul="Data Surat" ikon="description" class="lg:col-span-2">
            <dl class="space-y-4">
                @foreach ([['Nomor surat asal', $d['nomor_asal'] ?? '-', 'tabular font-bold'], ['Asal surat (pengirim)', $s->asal_tujuan, ''], ['Tanggal surat', $s->tgl_surat?->translatedFormat('j F Y'), ''],
                    ['Tanggal diterima', \Illuminate\Support\Carbon::parse($d['tgl_diterima'] ?? $s->created_at)->translatedFormat('j F Y'), ''], ['Sifat', $sifat[$s->sifat] ?? $s->sifat, ''],
                    ['Klasifikasi', $s->klasifikasi ? $s->klasifikasi->kode.' — '.$s->klasifikasi->nama : '-', ''], ['Lampiran', ($d['lampiran'] ?? '') ?: '-', ''], ['Jenis surat', $s->jenis?->nama ?? '-', '']] as [$l, $nilai, $kls])
                    <div><dt class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">{{ $l }}</dt><dd class="mt-0.5 font-body-md text-body-md {{ $kls }}">{{ $nilai }}</dd></div>
                @endforeach
                @if ($s->jenis)
                    @foreach ($s->jenis->field_formulir as $f)
                        @php $isi = $d['isian'][$f['nama']] ?? ''; @endphp
                        @if ($isi !== '' && $isi !== '-')<div><dt class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">{{ $f['label'] }}</dt><dd class="mt-0.5 whitespace-pre-line font-body-md text-body-md">{{ $isi }}</dd></div>@endif
                    @endforeach
                @endif
                <div class="border-t border-surface-container pt-3"><dt class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Dicatat oleh</dt><dd class="mt-0.5 font-body-sm text-body-sm">{{ $s->pembuat?->nama }} • {{ $s->created_at->translatedFormat('j M Y, H:i') }} WITA</dd></div>
            </dl>
        </x-kartu>
        <x-kartu judul="Pindaian Surat" ikon="picture_as_pdf" class="lg:col-span-3">
            @if ($scan)
                @if (str_starts_with($scan->mime, 'image/'))<img src="{{ route('lampiran.unduh', $scan) }}" alt="Pindaian surat" class="w-full rounded-lg border border-outline-variant/60">
                @else<iframe src="{{ route('lampiran.unduh', $scan) }}" title="Pindaian surat" class="h-[640px] w-full rounded-lg border border-outline-variant/60"></iframe>@endif
                <p class="mt-2 font-body-sm text-body-sm text-on-surface-variant">{{ $scan->nama_asli }} • {{ $scan->ukuranTerbaca() }}</p>
            @else
                <p class="py-10 text-center text-on-surface-variant">Pindaian belum diunggah.@if ($bolehUbah) <a class="font-semibold text-primary underline" href="{{ route('surat-masuk.ubah', $s) }}">Unggah sekarang</a>@endif</p>
            @endif
        </x-kartu>
    </div>
</div>
</x-layouts::app>
