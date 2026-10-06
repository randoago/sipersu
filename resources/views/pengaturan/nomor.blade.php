<x-layouts::app title="Format Nomor" :cari="false">
<div class="mx-auto max-w-5xl">
    <h1 class="mb-1 font-headline-xl text-headline-xl">Pengaturan</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">Format nomor surat dan identitas kop.</p>
    @include('pengaturan._tab')
    <div class="grid gap-5 lg:grid-cols-3">
        <form method="post" action="{{ route('pengaturan.nomor.simpan') }}" class="space-y-5 lg:col-span-2">@csrf
            <x-kartu judul="Format Nomor Surat" ikon="numbers" deskripsi="Nomor terbit otomatis HANYA saat surat ditandatangani.">
                <fieldset class="mb-4 rounded-lg border border-outline-variant p-3">
                    <legend class="px-1 font-label-lg text-label-lg">Cara penomoran surat keluar</legend>
                    @foreach (['otomatis' => ['Otomatis', 'Nomor urut dibuat sistem saat surat terbit. TU tetap boleh mengetik nomor urut sendiri pada surat tertentu (kosongkan = otomatis).'], 'manual' => ['Manual (diisi TU)', 'TU hanya mengetik NOMOR URUT depan (mis. 009); sisanya (klasifikasi, FT-UMB, bulan romawi, tahun) mengikuti pola di bawah. Nomor urut wajib diisi sebelum surat diajukan, diverifikasi, atau ditandatangani. Penghitung otomatis menyesuaikan nomor manual yang terbit.']] as $k => [$j, $ket])
                        <label class="flex cursor-pointer items-start gap-3 py-1.5"><input type="radio" name="penomoran_mode" value="{{ $k }}" @checked(old('penomoran_mode', $nilai['penomoran_mode'] ?? 'otomatis') === $k) class="mt-1 text-primary focus:ring-primary"><span><span class="font-label-lg text-label-lg">{{ $j }}</span><span class="block font-body-sm text-body-sm text-on-surface-variant">{{ $ket }}</span></span></label>
                    @endforeach
                </fieldset>
                <div class="space-y-4" x-data="{ f: @js(old('format_nomor', $nilai['format_nomor'])), p: {{ (int) old('panjang_urut', $nilai['panjang_urut']) }} }">
                    <x-input label="Pola nomor" name="format_nomor" wajib x-model="f" :value="old('format_nomor', $nilai['format_nomor'])" bantuan="Token: {urut} {klasifikasi} {bulan_romawi} {bulan} {tahun}" />
                    <x-input label="Jumlah digit nomor urut" name="panjang_urut" type="number" wajib x-model.number="p" :value="old('panjang_urut', $nilai['panjang_urut'])" class="max-w-40" />
                    <div class="rounded-lg bg-surface-container-low p-3"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">Contoh</p>
                        <p class="font-headline-sm text-headline-sm tabular text-primary" x-text="f.replace('{urut}', String(45).padStart(p, '0')).replace('{klasifikasi}', 'II.3.AU').replace('{bulan_romawi}', 'X').replace('{bulan}', '10').replace('{tahun}', '{{ now()->year }}')"></p></div>
                </div>
            </x-kartu>
            <x-kartu judul="Nomor Agenda Surat Masuk" ikon="move_to_inbox" deskripsi="Terbit otomatis saat surat masuk dicatat. Counter reset tiap tahun.">
                <div class="grid gap-4 md:grid-cols-2" x-data="{ f: @js(old('format_agenda', $nilai['format_agenda'] ?? 'AGD-{tahun}/{bulan_romawi}/{urut}')), p: {{ (int) old('panjang_agenda', $nilai['panjang_agenda'] ?? 4) }} }">
                    <x-input label="Pola nomor agenda" name="format_agenda" wajib x-model="f" :value="old('format_agenda', $nilai['format_agenda'] ?? 'AGD-{tahun}/{bulan_romawi}/{urut}')" bantuan="Token: {urut} {bulan_romawi} {bulan} {tahun}" />
                    <x-input label="Jumlah digit nomor urut" name="panjang_agenda" type="number" wajib x-model.number="p" :value="old('panjang_agenda', $nilai['panjang_agenda'] ?? 4)" class="max-w-40" />
                    <div class="rounded-lg bg-surface-container-low p-3 md:col-span-2"><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">Contoh • agenda terakhir tahun ini: {{ $agendaTahunIni }}</p>
                        <p class="font-headline-sm text-headline-sm tabular text-primary" x-text="f.replace('{urut}', String(148).padStart(p, '0')).replace('{bulan_romawi}', 'X').replace('{bulan}', '10').replace('{tahun}', '{{ now()->year }}')"></p></div>
                </div>
            </x-kartu>
            <x-kartu judul="Identitas Kop & Dokumen" ikon="apartment">
                <div class="grid gap-4 md:grid-cols-2">
                    <x-input label="Kota penerbitan surat" name="kota_surat" wajib :value="old('kota_surat', $nilai['kota_surat'])" />
                    <x-select label="Koreksi tanggal Hijriah" name="hijriah_koreksi" bantuan="Tanggal Hijriah dihitung otomatis (Umm al-Qura). Bila kalender yang dipakai fakultas berbeda sehari, atur di sini. Contoh hari ini: {{ \App\Support\TanggalHijriah::format(now()) }}.">
                        @foreach ([-1 => 'Mundur 1 hari (−1)', 0 => 'Sesuai perhitungan (0)', 1 => 'Maju 1 hari (+1)'] as $k => $l)<option value="{{ $k }}" @selected((int) old('hijriah_koreksi', $nilai['hijriah_koreksi'] ?? 0) === $k)>{{ $l }}</option>@endforeach
                    </x-select>
                    <x-input label="Tahun akademik (kosong = otomatis)" name="tahun_akademik" :value="old('tahun_akademik', $nilai['tahun_akademik'])" placeholder="2026/2027 Ganjil" />
                    <x-input label="Baris alamat pada header hijau (baris ke-3 kop)" name="kop_alamat" class="md:col-span-2" :value="old('kop_alamat', $nilai['kop_alamat'])" bantuan="Kop surat: FAKULTAS TEKNIK / UNIVERSITAS MUHAMMADIYAH BUTON / baris alamat ini." />
                    <x-input label="Alamat pada footer (setelah &quot;Alamat:&quot;)" name="alamat_fakultas" wajib class="md:col-span-2" :value="old('alamat_fakultas', $nilai['alamat_fakultas'])" />
                    <x-input label="E-mail (footer)" name="email_fakultas" type="email" wajib :value="old('email_fakultas', $nilai['email_fakultas'])" />
                    <x-input label="Laman / page (footer)" name="web_fakultas" wajib :value="old('web_fakultas', $nilai['web_fakultas'])" />
                </div>
            </x-kartu>
            <div class="flex justify-end"><x-tombol type="submit" ikon="save">Simpan Pengaturan</x-tombol></div>
        </form>
        <x-kartu judul="Counter Nomor Terakhir" ikon="tag" deskripsi="Reset tiap tahun per klasifikasi.">
            <ul class="divide-y divide-surface-container">
                @forelse ($penomoran as $n)<li class="flex items-center justify-between py-2 font-body-sm text-body-sm"><span><span class="font-semibold">{{ $n->klasifikasi->kode }}</span> • {{ $n->tahun }}</span><span class="tabular font-semibold">{{ $n->nomor_terakhir }}</span></li>
                @empty<li class="py-2 text-on-surface-variant">Belum ada nomor terbit.</li>@endforelse
            </ul>
        </x-kartu>
    </div>
</div>
</x-layouts::app>
