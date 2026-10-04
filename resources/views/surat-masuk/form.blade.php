@php
    $d = $s?->data ?? [];
    $v = fn ($k, $b = null) => old($k, match ($k) {
        'nomor_asal' => $d['nomor_asal'] ?? null, 'asal' => $s?->asal_tujuan, 'tgl_surat' => $s?->tgl_surat?->format('Y-m-d'),
        'tgl_diterima' => $d['tgl_diterima'] ?? null, 'perihal' => $s?->perihal, 'sifat' => $s?->sifat, 'klasifikasi_id' => $s?->klasifikasi_id,
        'lampiran' => $d['lampiran'] ?? null, default => null,
    } ?? $b);
@endphp
<x-layouts::app :title="$s ? 'Ubah Surat Masuk' : 'Catat Surat Masuk'" :cari="false">
<div class="mx-auto max-w-5xl">
    <nav class="mb-2 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('surat-masuk.index') }}" class="hover:text-primary">Surat Masuk</a><x-ikon name="chevron_right" class="text-[14px]" />@unless ($s)<a href="{{ route('surat-masuk.buat') }}" class="hover:text-primary">Pilih jenis</a><x-ikon name="chevron_right" class="text-[14px]" />@endunless<span class="text-on-surface">{{ $jenis->nama }}</span></nav>
    <h1 class="mb-1 font-headline-xl-mobile text-headline-xl-mobile lg:font-headline-xl lg:text-headline-xl">{{ $s ? 'Ubah Surat Masuk' : 'Catat Surat Masuk' }}</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">{{ $jenis->nama }}@if ($s) — Agenda <strong class="tabular">{{ $s->no_agenda }}</strong>@else • Nomor agenda terbit otomatis saat disimpan.@endif</p>

    <form method="post" action="{{ $s ? route('surat-masuk.perbarui', $s) : route('surat-masuk.simpan', $jenis) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf @if ($s) @method('PUT') @endif
        <x-kartu judul="Data Surat" ikon="move_to_inbox" deskripsi="Sesuai yang tertera pada surat yang diterima.">
            <div class="grid gap-space-md md:grid-cols-2">
                <x-input label="Nomor surat asal" name="nomor_asal" wajib :value="$v('nomor_asal')" placeholder="Contoh: 0451/LL9/TU/2026" />
                <x-input label="Asal surat (pengirim)" name="asal" wajib :value="$v('asal')" placeholder="Contoh: LLDIKTI Wilayah IX Sulawesi" />
                <x-input label="Tanggal surat" name="tgl_surat" type="date" wajib :value="$v('tgl_surat')" />
                <x-input label="Tanggal diterima" name="tgl_diterima" type="date" wajib :value="$v('tgl_diterima', now()->toDateString())" />
                <x-input label="Perihal" name="perihal" wajib class="md:col-span-2" :value="$v('perihal')" />
                <x-select label="Sifat surat" name="sifat" wajib>@foreach ($sifat as $k => $l)<option value="{{ $k }}" @selected($v('sifat', 'biasa') === $k)>{{ $l }}</option>@endforeach</x-select>
                <x-select label="Klasifikasi" name="klasifikasi_id"><option value="">— tidak ditentukan —</option>@foreach ($klasifikasi as $k)<option value="{{ $k->id }}" @selected((string) $v('klasifikasi_id', $jenis->klasifikasi_id) === (string) $k->id)>{{ $k->kode }} — {{ $k->nama }}</option>@endforeach</x-select>
                <x-input label="Lampiran" name="lampiran" class="md:col-span-2" :value="$v('lampiran')" placeholder="Contoh: 1 (satu) berkas — kosongkan bila tidak ada" />
            </div>
        </x-kartu>

        @if (count($jenis->field_formulir))
            <x-kartu :judul="'Kolom Khusus: '.$jenis->nama" ikon="edit_note"><x-isian-dinamis :fields="$jenis->field_formulir" :nilai="$nilai" /></x-kartu>
        @endif

        <x-kartu judul="Pindaian Surat" ikon="attach_file" deskripsi="PDF, JPG, atau PNG — maksimal 2 MB. Disimpan di penyimpanan privat.">
            @if ($s && $s->lampiran->isNotEmpty())<p class="mb-3 flex items-center gap-2 rounded-lg bg-surface-container-low p-3 font-body-sm text-body-sm"><x-ikon name="picture_as_pdf" class="text-[20px] text-primary" />Berkas saat ini: <a class="font-semibold text-primary underline" target="_blank" href="{{ route('lampiran.unduh', $s->lampiran->first()) }}">{{ $s->lampiran->first()->nama_asli }}</a> — unggah baru untuk mengganti.</p>@endif
            <input type="file" name="scan" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="block w-full rounded-lg border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm file:mr-3 file:border-0 file:bg-surface-container-high file:px-4 file:py-2.5 file:font-label-md">
            @error('scan')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
        </x-kartu>

        <div class="flex justify-end gap-2"><x-tombol :href="$s ? route('surat-masuk.show', $s) : route('surat-masuk.buat')" varian="sekunder">Batal</x-tombol><x-tombol type="submit" ikon="save">{{ $s ? 'Simpan Perubahan' : 'Simpan & Terbitkan Nomor Agenda' }}</x-tombol></div>
    </form>
</div>
</x-layouts::app>
