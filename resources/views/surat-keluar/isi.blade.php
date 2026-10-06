@php $j = $jenis; $m = $bentuk ?? $j->mode_ttd ?? 'qr'; $alurParaf = $m === 'qr' && $j->perlu_paraf ? ['wakil_dekan' => 'Wakil Dekan', 'kaprodi' => 'Kaprodi'][$j->paraf_role] ?? null : null; @endphp
<x-layouts::app :title="$j->nama" :cari="false">
<div class="mx-auto max-w-6xl">
    <nav class="mb-2 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('surat-keluar.index') }}" class="hover:text-primary">Surat Keluar</a><x-ikon name="chevron_right" class="text-[14px]" /><a href="{{ route('surat-keluar.buat') }}" class="hover:text-primary">Pilih format</a><x-ikon name="chevron_right" class="text-[14px]" /><span class="text-on-surface">{{ $j->nama }}</span></nav>
    <h1 class="mb-1 font-headline-xl-mobile text-headline-xl-mobile lg:font-headline-xl lg:text-headline-xl">{{ $s ? 'Ubah Isian: ' : 'Isi Surat: ' }}{{ $j->nama }}</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">{{ $j->deskripsi ?: 'Lengkapi isian berikut; surat disusun otomatis pada kop resmi.' }}</p>

    <form method="post" action="{{ $s ? route('surat-keluar.perbarui', $s) : route('surat-keluar.simpan-format', $j) }}" class="grid items-start gap-6 lg:grid-cols-3">
        @csrf @if ($s) @method('PUT') @endif
        <input type="hidden" name="mode_ttd" value="{{ $m }}">
        <div class="space-y-5 lg:col-span-2">
            <x-kartu judul="Isian Surat" ikon="edit_note" deskripsi="Tanda * wajib diisi.">
                <x-tanggal-surat :nilai="$s?->tgl_surat?->toDateString()" class="mb-space-md md:max-w-md" />
                <x-isian-dinamis :fields="$j->field_formulir" :nilai="$nilai" />
            </x-kartu>
            <div class="flex flex-wrap justify-end gap-2"><x-tombol :href="$s ? route('surat-keluar.show', $s) : route('surat-keluar.buat')" varian="sekunder">Batal</x-tombol>
                <x-tombol varian="lembut" ikon="visibility" x-on:click="pratinjauSurat('{{ route('surat-keluar.pratinjau') }}', $el.closest('form'), { format: '{{ $j->kode }}' })">Lihat Tampilan Surat</x-tombol>
                <x-tombol type="submit" ikon="save">Simpan Draf</x-tombol></div>
        </div>
        <aside class="space-y-5">
            <x-kartu judul="Ketentuan Format" ikon="info">
                <dl class="space-y-3 font-body-sm text-body-sm">
                    <div><dt class="text-on-surface-variant">Klasifikasi / nomor</dt><dd class="font-semibold">{{ $j->klasifikasi?->kode }} — {{ $j->klasifikasi?->nama }}</dd></div>
                    <div><dt class="text-on-surface-variant">Penandatangan</dt><dd class="font-semibold">{{ $j->penandatanganJabatan?->nama }}</dd></div>
                    <div><dt class="text-on-surface-variant">Alur</dt><dd class="font-semibold">{{ $m === 'basah' ? 'Draf → Terbitkan langsung (tanpa persetujuan) → Cetak & tanda tangan basah' : 'Draf → '.($alurParaf ? 'Paraf '.$alurParaf.' → ' : '').'Tanda tangan → Nomor terbit' }}</dd></div>
                    <div><dt class="text-on-surface-variant">Bentuk surat</dt><dd class="flex items-center gap-1 font-semibold"><x-ikon :name="$m === 'basah' ? 'print' : 'qr_code_2'" class="text-[16px] text-primary" />{{ $m === 'basah' ? 'Tanpa QR — disiapkan kosong untuk tanda tangan basah dan cap' : 'Ber-QR — tanda tangan elektronik' }}@if (! $s)<a href="{{ route('surat-keluar.buat') }}" class="ml-2 font-normal text-primary underline">ganti</a>@endif</dd></div>
                </dl>
                <p class="mt-3 rounded-lg bg-surface-container-low p-2.5 font-body-sm text-body-sm text-on-surface-variant">{{ $m === 'basah' ? 'Nomor surat terbit saat tombol Terbitkan ditekan.' : 'Nomor surat baru terbit saat surat ditandatangani.' }}</p>
            </x-kartu>
        </aside>
    </form>
<x-pratinjau-surat />
</div>
</x-layouts::app>
