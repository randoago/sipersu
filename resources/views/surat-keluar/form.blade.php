@php $d = $s?->data ?? []; $v = fn ($k, $b = null) => old($k, match ($k) { 'klasifikasi_id' => $s?->klasifikasi_id, 'sifat' => $s?->sifat, 'perihal' => $s?->perihal, 'jabatan_id' => $s?->jabatan_id, 'mode_ttd' => $s?->mode_ttd, default => $d[$k] ?? $b }); @endphp
<x-layouts::app :title="$s ? 'Ubah Draf Surat' : 'Buat Surat'" :cari="false">
<div class="mx-auto max-w-4xl">
    <nav class="mb-2 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('surat-keluar.index') }}" class="hover:text-primary">Surat Keluar</a><x-ikon name="chevron_right" class="text-[14px]" /><span>{{ $s ? 'Ubah draf' : 'Buat surat' }}</span></nav>
    <h1 class="mb-5 font-headline-xl-mobile text-headline-xl-mobile lg:font-headline-xl lg:text-headline-xl">{{ $s ? 'Ubah Draf Surat' : 'Buat Surat Keluar' }}</h1>
    <form method="post" action="{{ $s ? route('surat-keluar.perbarui', $s) : route('surat-keluar.simpan') }}" class="space-y-5">
        @csrf @if ($s) @method('PUT') @endif
        <x-kartu judul="Data Surat" ikon="description" deskripsi="Dicetak pada kop resmi Fakultas Teknik UM Buton.">
            <div class="grid gap-space-md md:grid-cols-2">
                <x-select label="Klasifikasi (untuk nomor)" name="klasifikasi_id" wajib>@foreach ($klasifikasi as $k)<option value="{{ $k->id }}" @selected($v('klasifikasi_id') == $k->id)>{{ $k->kode }} — {{ $k->nama }}</option>@endforeach</x-select>
                <x-select label="Sifat surat" name="sifat" wajib>@foreach (['biasa' => 'Biasa', 'penting' => 'Penting', 'segera' => 'Segera', 'rahasia' => 'Rahasia'] as $k => $l)<option value="{{ $k }}" @selected($v('sifat', 'biasa') === $k)>{{ $l }}</option>@endforeach</x-select>
                <x-textarea label="Tujuan surat (Yth.)" name="tujuan" wajib rows="3" class="md:col-span-2" bantuan="Satu baris per baris alamat. Contoh: Kepala Dinas Pendidikan Kota Baubau, di Tempat">{{ $v('tujuan') }}</x-textarea>
                <x-input label="Perihal" name="perihal" wajib class="md:col-span-2" :value="$v('perihal')" />
                <x-input label="Lampiran" name="lampiran" :value="$v('lampiran')" placeholder="Contoh: 1 (satu) berkas — kosongkan bila tidak ada" />
                <label class="flex items-end gap-2 pb-2 font-label-lg text-label-lg"><input type="checkbox" name="salam" value="1" @checked($v('salam', true)) class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">Sertakan salam pembuka & penutup</label>
                <x-textarea label="Isi surat" name="isi" wajib rows="12" class="md:col-span-2" bantuan="Pisahkan paragraf dengan satu baris kosong.">{{ $v('isi') }}</x-textarea>
            </div>
        </x-kartu>
        <x-kartu judul="Bentuk Surat" ikon="qr_code_2" deskripsi="Pilih cara surat ini disahkan.">
            <div class="grid gap-3 md:grid-cols-2" x-data="{ m: @js($v('mode_ttd', 'qr')) }">
                @foreach ([
                    'qr' => ['Surat ber-QR (TTE)', 'Ditandatangani secara elektronik oleh pejabat. PDF memuat kode QR + spesimen tanda tangan, dapat diverifikasi publik. Tanpa cap basah.', 'qr_code_2'],
                    'basah' => ['Surat tanpa QR', 'Nomor tetap terbit otomatis, tetapi PDF tanpa QR: dicetak lalu ditandatangani basah dan dibubuhi cap. Tidak ada verifikasi online.', 'print'],
                ] as $k => [$j, $d2, $i])
                    <label class="flex cursor-pointer gap-3 rounded-xl border-2 p-4 transition" :class="m === '{{ $k }}' ? 'border-primary-container bg-primary-fixed/20' : 'border-outline-variant bg-surface-container-lowest hover:bg-surface-container-low'">
                        <input type="radio" name="mode_ttd" value="{{ $k }}" x-model="m" class="mt-1 text-primary focus:ring-primary">
                        <span><span class="flex items-center gap-1.5 font-label-lg text-label-lg"><x-ikon name="{{ $i }}" class="text-[20px] text-primary" />{{ $j }}</span><span class="mt-1 block font-body-sm text-body-sm text-on-surface-variant">{{ $d2 }}</span></span>
                    </label>
                @endforeach
            </div>
            @error('mode_ttd')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
        </x-kartu>
        <x-kartu judul="Alur Persetujuan" ikon="alt_route">
            <div class="grid gap-space-md md:grid-cols-2">
                <x-select label="Penandatangan" name="jabatan_id" wajib>@foreach ($jabatan as $j)<option value="{{ $j->id }}" @selected($v('jabatan_id') == $j->id)>{{ $j->nama }} — {{ $j->pejabat?->namaLengkap() }}</option>@endforeach</x-select>
                <x-select label="Paraf sebelum tanda tangan" name="paraf_role" bantuan="Nomor surat baru terbit saat surat ditandatangani."><option value="">Tanpa paraf</option><option value="wakil_dekan" @selected($v('paraf_role') === 'wakil_dekan')>Wakil Dekan</option><option value="kaprodi" @selected($v('paraf_role') === 'kaprodi')>Kaprodi</option></x-select>
            </div>
        </x-kartu>
        <div class="flex justify-end gap-2"><x-tombol :href="$s ? route('surat-keluar.show', $s) : route('surat-keluar.index')" varian="sekunder">Batal</x-tombol><x-tombol type="submit" ikon="save">Simpan Draf</x-tombol></div>
    </form>
</div>
</x-layouts::app>
