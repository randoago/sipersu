@php $v = fn ($k, $b = null) => old($k, $b ?? $b); $val = fn ($k, $d = null) => old($k, $b?->{$k} instanceof \Carbon\CarbonInterface ? $b->{$k}->toDateString() : ($b?->{$k} ?? $d)); @endphp
<x-layouts::app :title="$b ? 'Ubah Catatan Pembukuan' : 'Catat Surat'" :cari="false">
<div class="mx-auto max-w-4xl">
    <nav class="mb-2 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('pembukuan.index') }}" class="hover:text-primary">Pembukuan</a><x-ikon name="chevron_right" class="text-[14px]" /><span>{{ $b ? 'Ubah' : 'Catat surat' }}</span></nav>
    <h1 class="mb-5 font-headline-xl text-headline-xl">{{ $b ? 'Ubah Catatan Pembukuan' : 'Catat Surat ke Pembukuan' }}</h1>
    <form method="post" action="{{ $b ? route('pembukuan.perbarui', $b) : route('pembukuan.simpan') }}" class="space-y-5" x-data="{ arah: @js($val('arah', 'keluar')) }">
        @csrf @if ($b) @method('PUT') @endif
        <x-kartu judul="Data Surat" ikon="menu_book" deskripsi="Berdasarkan nomor surat. Untuk surat dari aplikasi, tidak perlu dicatat di sini.">
            <div class="grid gap-space-md md:grid-cols-2">
                <div class="md:col-span-2"><p class="mb-1.5 font-label-lg text-label-lg">Jenis buku</p>
                    <div class="flex flex-wrap gap-2">@foreach (\App\Services\PembukuanService::ARAH as $k => $l)<label class="flex cursor-pointer items-center gap-2 rounded-lg border border-outline-variant px-3 py-2 font-label-lg text-label-lg"><input type="radio" name="arah" value="{{ $k }}" x-model="arah" class="text-primary focus:ring-primary">{{ $l }}</label>@endforeach</div>
                    @error('arah')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror</div>
                <x-input label="Nomor surat" name="nomor" wajib :value="$val('nomor')" class="md:col-span-2" placeholder="045/II.3.AU/FT-UMB/I/2026" bantuan="Tulis nomor lengkap apa adanya. Surat masuk: nomor dari pengirim." />
                <x-input label="Tanggal surat" name="tgl_surat" type="date" wajib :value="$val('tgl_surat')" />
                <div x-show="arah === 'masuk'" x-cloak><x-input label="Tanggal diterima" name="tgl_diterima" type="date" :value="$val('tgl_diterima')" /></div>
                <x-input label="Pihak (asal / tujuan)" name="pihak" :value="$val('pihak')" class="md:col-span-2" placeholder="Rektorat Universitas Muhammadiyah Buton" />
                <x-input label="Perihal" name="perihal" wajib :value="$val('perihal')" class="md:col-span-2" />
                <x-input label="Lampiran" name="lampiran" :value="$val('lampiran')" />
                <x-select label="Sifat" name="sifat">@foreach (['biasa' => 'Biasa', 'penting' => 'Penting', 'segera' => 'Segera', 'rahasia' => 'Rahasia'] as $k => $l)<option value="{{ $k }}" @selected($val('sifat', 'biasa') === $k)>{{ $l }}</option>@endforeach</x-select>
                <x-input label="Jenis surat (opsional)" name="jenis" :value="$val('jenis')" placeholder="Surat Tugas, SK Dekan, Nota Dinas…" />
                <div x-show="arah === 'masuk'" x-cloak><x-input label="Nomor agenda (opsional)" name="no_agenda" :value="$val('no_agenda')" placeholder="AGD-2026/I/0001" /></div>
                <x-textarea label="Keterangan" name="keterangan" rows="2" class="md:col-span-2">{{ $val('keterangan') }}</x-textarea>
                <label x-show="arah === 'keluar'" x-cloak class="flex cursor-pointer items-start gap-2 rounded-lg bg-surface-container-low p-3 font-body-sm text-body-sm md:col-span-2"><input type="checkbox" name="sinkron" value="1" checked class="mt-0.5 h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"><span><strong>Sesuaikan penghitung nomor otomatis</strong> bila nomor mengikuti pola penomoran, sehingga nomor otomatis berikutnya melanjutkan nomor ini.</span></label>
            </div>
        </x-kartu>
        <div class="flex justify-end gap-2"><x-tombol :href="route('pembukuan.index')" varian="sekunder">Batal</x-tombol><x-tombol type="submit" ikon="save">Simpan</x-tombol></div>
    </form>
</div>
</x-layouts::app>
