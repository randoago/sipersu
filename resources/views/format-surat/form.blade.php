@php
    $v = fn ($k, $b = null) => old($k, $f?->{$k} ?? $b);
    $hintPerihal = 'Boleh memakai isian, mis. {{ isian.nama_rapat }}. Kosong = memakai nama format.';
    $tokenTetap = [['pembuat.nama', 'Nama pembuat surat'], ['penandatangan.nama', 'Nama penandatangan'], ['penandatangan.jabatan', 'Jabatan penandatangan']];
    $tokenMhs = [['pemohon.nama', 'Nama mahasiswa'], ['pemohon.nim', 'NIM'], ['pemohon.prodi', 'Program studi'], ['pemohon.ttl', 'Tempat, tanggal lahir'], ['pemohon.alamat', 'Alamat']];
@endphp
<x-layouts::app :title="$f ? 'Ubah Format Surat' : 'Tambah Format Surat'" :cari="false">
<div class="mx-auto max-w-6xl"
     x-data="builder(@js(array_values($fields)), @js(array_values($syarat)), @js($v('sasaran', 'staf')), @js($tokenTetap), @js($tokenMhs), @js(route('format-surat.pratinjau')))">
    <nav class="mb-2 flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('format-surat.index') }}" class="hover:text-primary">Format Surat</a><x-ikon name="chevron_right" class="text-[14px]" /><span>{{ $f ? 'Ubah' : 'Tambah' }}</span></nav>
    <h1 class="mb-5 font-headline-xl-mobile text-headline-xl-mobile lg:font-headline-xl lg:text-headline-xl">{{ $f ? 'Ubah Format: '.$f->nama : 'Tambah Format Surat' }}</h1>

    @if ($errors->any())
        <x-peringatan jenis="bahaya" judul="Periksa kembali isian Anda" class="mb-5"><ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></x-peringatan>
    @endif

    <form x-ref="form" method="post" action="{{ $f ? route('format-surat.perbarui', $f) : route('format-surat.simpan') }}" class="space-y-5">
        @csrf @if ($f) @method('PUT') @endif

        <x-kartu judul="1. Surat untuk apa?" ikon="description" deskripsi="Nama dan kegunaan surat ini.">
            <div class="grid gap-space-md md:grid-cols-2">
                <x-input label="Nama format surat" name="nama" wajib :value="$v('nama')" placeholder="Contoh: Surat Tugas Dosen" class="md:col-span-2" />
                <x-input label="Kegunaan (deskripsi singkat)" name="deskripsi" :value="$v('deskripsi')" placeholder="Contoh: Penugasan dosen mengikuti kegiatan di luar kampus" class="md:col-span-2" />
                <div>
                    <p class="mb-1.5 font-label-lg text-label-lg">Dibuat oleh <span class="text-error">*</span></p>
                    <div class="grid gap-2">
                        @foreach (['staf' => ['Surat keluar (dibuat staf)', 'Muncul di Surat Keluar → Buat Surat; disusun otomatis, ditandatangani, bernomor.'], 'masuk' => ['Surat masuk (dicatat TU)', 'Muncul di Surat Masuk → Catat Surat; nomor agenda otomatis + pindaian.'], 'mahasiswa' => ['Mahasiswa (e-Layanan)', 'Mahasiswa mengajukan, lalu diverifikasi dan ditandatangani.']] as $k => [$j, $d])
                            <label class="flex cursor-pointer gap-2 rounded-lg border border-outline-variant p-3 has-[:checked]:border-primary-container has-[:checked]:bg-primary-fixed/20"><input type="radio" name="sasaran" value="{{ $k }}" x-model="sasaran" class="mt-0.5 text-primary focus:ring-primary"><span><span class="block font-label-lg text-label-lg">{{ $j }}</span><span class="font-body-sm text-body-sm text-on-surface-variant">{{ $d }}</span></span></label>
                        @endforeach
                    </div>
                </div>
                <div class="grid content-start gap-space-md" x-show="sasaran !== 'masuk'">
                    <x-input label="Judul surat (tercetak di atas nomor)" name="judul_surat" :value="$v('judul_surat')" placeholder="Contoh: SURAT TUGAS — kosongkan bila tanpa judul" />
                    <x-input label="Perihal (untuk daftar & arsip)" name="perihal_template" :value="$v('perihal_template')" placeholder="Contoh: Surat Tugas @{{ isian.nama }}" :bantuan="$hintPerihal" />
                </div>
                <x-input label="Kategori" name="kategori" :value="$v('kategori')" placeholder="Akademik, Kepegawaian, …" />
                <x-input label="Ikon (nama Material Symbols)" name="ikon" wajib :value="$v('ikon', 'description')" bantuan="Contoh: description, school, groups, event" />
            </div>
        </x-kartu>

        <x-kartu judul="2. Isian / kolom surat" ikon="edit_note" deskripsi="Kolom yang muncul pada formulir pengisian. Urutan di sini = urutan pada formulir.">
            <div class="space-y-3">
                <div x-show="sasaran === 'masuk'" x-cloak class="rounded-lg bg-surface-container-low p-3 font-body-sm text-body-sm">
                    <p class="font-label-lg text-label-lg">Kolom tetap surat masuk (selalu ada):</p>
                    <p class="mt-1 text-on-surface-variant">Nomor surat asal • Asal surat (pengirim) • Tanggal surat • Tanggal diterima • Perihal • Sifat • Klasifikasi • Lampiran • Pindaian surat. <strong>Nomor agenda terbit otomatis.</strong> Tambahkan di bawah ini hanya kolom <em>khusus</em> jenis surat ini (mis. tanggal kegiatan pada undangan).</p>
                </div>
                <template x-for="(r, i) in isian" :key="r.k">
                    <div class="rounded-lg border border-outline-variant bg-surface-container-low/50 p-3">
                        <div class="grid gap-2 md:grid-cols-12">
                            <div class="md:col-span-5"><label class="mb-1 block font-label-md text-label-md">Nama isian</label>
                                <input type="text" :name="`fields[${i}][label]`" x-model="r.label" @input="r.baru && (r.nama = slug(r.label, i))" placeholder="Contoh: Nama Dosen" class="h-10 w-full rounded-lg border-outline-variant bg-surface-container-lowest font-body-md text-body-md focus:ring-2 focus:ring-primary"></div>
                            <div class="md:col-span-3"><label class="mb-1 block font-label-md text-label-md">Jenis isian</label>
                                <select :name="`fields[${i}][tipe]`" x-model="r.tipe" class="h-10 w-full rounded-lg border-outline-variant bg-surface-container-lowest font-body-md text-body-md focus:ring-2 focus:ring-primary">
                                    @foreach ($tipe as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                                </select></div>
                            <div class="md:col-span-2"><label class="mb-1 block font-label-md text-label-md">Lebar</label>
                                <select :name="`fields[${i}][lebar]`" x-model="r.lebar" class="h-10 w-full rounded-lg border-outline-variant bg-surface-container-lowest font-body-md text-body-md focus:ring-2 focus:ring-primary"><option value="penuh">Penuh</option><option value="setengah">Setengah</option></select></div>
                            <label class="flex items-end gap-2 pb-2 font-label-md text-label-md md:col-span-2"><input type="hidden" :name="`fields[${i}][wajib]`" value="0"><input type="checkbox" :name="`fields[${i}][wajib]`" value="1" x-model="r.wajib" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">Wajib diisi</label>
                        </div>
                        <input type="hidden" :name="`fields[${i}][nama]`" :value="r.nama">
                        <div class="mt-2 grid gap-2 md:grid-cols-12">
                            <div class="md:col-span-7"><input type="text" :name="`fields[${i}][placeholder]`" x-model="r.placeholder" placeholder="Contoh isian / petunjuk (opsional)" class="h-9 w-full rounded-lg border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm focus:ring-2 focus:ring-primary"></div>
                            <div class="flex items-center justify-between gap-2 md:col-span-5">
                                <span class="truncate rounded bg-surface-container px-2 py-1 font-mono text-[11px] text-on-surface-variant" title="Token untuk templat" x-text="r.nama ? tok('isian.' + r.nama) : '(otomatis)'"></span>
                                <span class="flex shrink-0 gap-1">
                                    <button type="button" @click="naik(i)" :disabled="i === 0" class="rounded p-1.5 text-on-surface-variant hover:bg-surface-container disabled:opacity-30" aria-label="Naikkan"><x-ikon name="arrow_upward" class="text-[18px]" /></button>
                                    <button type="button" @click="turun(i)" :disabled="i === isian.length - 1" class="rounded p-1.5 text-on-surface-variant hover:bg-surface-container disabled:opacity-30" aria-label="Turunkan"><x-ikon name="arrow_downward" class="text-[18px]" /></button>
                                    <button type="button" @click="hapus(i)" class="rounded p-1.5 text-error hover:bg-error-container/50" aria-label="Hapus isian"><x-ikon name="delete" class="text-[18px]" /></button>
                                </span>
                            </div>
                        </div>
                        <div class="mt-2" x-show="r.tipe === 'pilihan'" x-cloak>
                            <label class="mb-1 block font-label-md text-label-md">Daftar pilihan <span class="font-normal text-on-surface-variant">(satu per baris)</span></label>
                            <textarea :name="`fields[${i}][opsi]`" x-model="r.opsi" rows="3" class="w-full rounded-lg border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                    </div>
                </template>
                <p class="py-3 text-center font-body-sm text-body-sm text-on-surface-variant" x-show="!isian.length">Belum ada isian. Klik "Tambah Isian".</p>
                <x-tombol varian="sekunder" ikon="add" x-on:click="tambah()">Tambah Isian</x-tombol>
            </div>
        </x-kartu>

        <x-kartu x-show="sasaran !== 'masuk'" judul="3. Isi surat (templat)" ikon="article" deskripsi="Tulis isi surat. Klik tombol isian di bawah untuk menyisipkan nilai yang akan diisi pengguna.">
            <div class="space-y-3">
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" @click="sisip('<p>', '</p>')" class="rounded-md bg-surface-container px-2.5 py-1 font-label-md text-label-md hover:bg-surface-container-high">¶ Paragraf</button>
                    <button type="button" @click="sisip('<strong>', '</strong>')" class="rounded-md bg-surface-container px-2.5 py-1 font-label-md text-label-md font-bold hover:bg-surface-container-high">Tebal</button>
                    <button type="button" @click="sisip('<em>', '</em>')" class="rounded-md bg-surface-container px-2.5 py-1 font-label-md text-label-md italic hover:bg-surface-container-high">Miring</button>
                    <button type="button" @click="tabel()" class="rounded-md bg-surface-container px-2.5 py-1 font-label-md text-label-md hover:bg-surface-container-high">▦ Tabel data</button>
                </div>
                <div>
                    <p class="mb-1 font-label-md text-label-md text-on-surface-variant">Sisipkan isian:</p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="r in isian.filter(x => x.nama)" :key="r.k"><button type="button" @click="sisipToken('isian.' + r.nama)" class="rounded-full border border-primary-container/40 bg-primary-fixed/30 px-2.5 py-1 font-label-md text-label-md text-primary hover:bg-primary-fixed/60" x-text="r.label"></button></template>
                        <template x-for="t in tokenLain()" :key="t[0]"><button type="button" @click="sisipToken(t[0])" class="rounded-full border border-outline-variant bg-surface-container-lowest px-2.5 py-1 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container" x-text="t[1]"></button></template>
                    </div>
                </div>
                <x-textarea name="template_html" wajib rows="16" class="font-mono" x-ref="templat" spellcheck="false" bantuan="Gunakan tombol di atas; hasilnya berupa HTML sederhana (p, strong, em, table, ul, ol, br). Tag berbahaya dibuang otomatis.">{{ $v('template_html', "<p>Dengan hormat,</p>\n<p></p>\n<p>Demikian disampaikan, atas perhatian Bapak/Ibu diucapkan terima kasih.</p>") }}</x-textarea>
                <x-tombol varian="lembut" ikon="visibility" x-on:click="pratinjau()">Lihat Pratinjau (data contoh)</x-tombol>
            </div>
        </x-kartu>

        <x-kartu judul="4. Nomor, penandatangan, dan bentuk surat" ikon="draw" deskripsi="Untuk surat masuk, hanya klasifikasi bawaan yang dipakai.">
            <div class="grid gap-space-md md:grid-cols-2">
                <x-select label="Klasifikasi (kode pada nomor surat / bawaan)" name="klasifikasi_id">@foreach ($klasifikasi as $k)<option value="{{ $k->id }}" @selected($v('klasifikasi_id') == $k->id)>{{ $k->kode }} — {{ $k->nama }}</option>@endforeach</x-select>
                <div x-show="sasaran !== 'masuk'"><x-select label="Penandatangan" name="penandatangan_jabatan_id" wajib x-model="jabatan">@foreach ($jabatan as $j)<option value="{{ $j->id }}" @selected($v('penandatangan_jabatan_id') == $j->id)>{{ $j->nama }}{{ $j->pejabat ? ' — '.$j->pejabat->namaLengkap() : ' (belum ada pejabat)' }}</option>@endforeach</x-select></div>
                <div class="md:col-span-2" x-show="sasaran !== 'masuk'" x-data="{ m: @js($v('mode_ttd', 'qr')) }">
                    <p class="mb-1.5 font-label-lg text-label-lg">Bentuk surat</p>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach (['qr' => ['Surat ber-QR (TTE)', 'Tanda tangan elektronik + QR yang dapat diverifikasi publik, tanpa cap.', 'qr_code_2'], 'basah' => ['Surat tanpa QR', 'Nomor tetap otomatis; PDF dicetak, ditandatangani basah dan dicap.', 'print']] as $k => [$j, $d, $i])
                            <label class="flex cursor-pointer gap-3 rounded-xl border-2 p-3 transition" :class="m === '{{ $k }}' ? 'border-primary-container bg-primary-fixed/20' : 'border-outline-variant hover:bg-surface-container-low'"><input type="radio" name="mode_ttd" value="{{ $k }}" x-model="m" class="mt-1 text-primary focus:ring-primary"><span><span class="flex items-center gap-1.5 font-label-lg text-label-lg"><x-ikon name="{{ $i }}" class="text-[18px] text-primary" />{{ $j }}</span><span class="block font-body-sm text-body-sm text-on-surface-variant">{{ $d }}</span></span></label>
                        @endforeach
                    </div>
                </div>
                <div x-show="sasaran !== 'masuk'" x-data="{ p: {{ $v('perlu_paraf', false) ? 'true' : 'false' }} }" class="space-y-2">
                    <label class="flex cursor-pointer items-center gap-2 font-label-lg text-label-lg"><input type="checkbox" name="perlu_paraf" value="1" x-model="p" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">Perlu paraf sebelum tanda tangan</label>
                    <div x-show="p" x-cloak><x-select label="Pemaraf" name="paraf_role"><option value="wakil_dekan" @selected($v('paraf_role') === 'wakil_dekan')>Wakil Dekan</option><option value="kaprodi" @selected($v('paraf_role') === 'kaprodi')>Kaprodi</option></x-select></div>
                </div>
                <div class="grid gap-space-md" x-show="sasaran === 'mahasiswa'" x-cloak>
                    <x-select label="Diverifikasi oleh (pengajuan mahasiswa)" name="verifikator_role"><option value="admin_tu" @selected($v('verifikator_role', 'admin_tu') === 'admin_tu')>Admin TU</option><option value="kaprodi" @selected($v('verifikator_role') === 'kaprodi')>Kaprodi (prodi pemohon)</option></x-select>
                    <x-input label="Target selesai (hari kerja)" name="sla_hari" type="number" :value="$v('sla_hari', 3)" />
                </div>
                <input type="hidden" name="sla_hari" value="{{ $v('sla_hari', 3) }}" x-bind:disabled="sasaran === 'mahasiswa'">
                <input type="hidden" name="urutan" value="{{ $v('urutan', 50) }}">
                <label class="flex cursor-pointer items-center gap-2 font-label-lg text-label-lg md:col-span-2"><input type="checkbox" name="aktif" value="1" @checked($v('aktif', true)) class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">Format aktif (tampil untuk dipakai)</label>
            </div>
        </x-kartu>

        <x-kartu judul="5. Berkas persyaratan (khusus mahasiswa)" ikon="attach_file" x-show="sasaran === 'mahasiswa'" x-cloak>
            <div class="space-y-2">
                <template x-for="(s, i) in syarat" :key="s.k">
                    <div class="flex items-center gap-2">
                        <input type="text" :name="`syarat[${i}][label]`" x-model="s.label" placeholder="Contoh: Kartu Tanda Mahasiswa (KTM)" class="h-10 flex-1 rounded-lg border-outline-variant bg-surface-container-lowest font-body-md text-body-md focus:ring-2 focus:ring-primary">
                        <label class="flex items-center gap-1.5 font-label-md text-label-md"><input type="hidden" :name="`syarat[${i}][wajib]`" value="0"><input type="checkbox" :name="`syarat[${i}][wajib]`" value="1" x-model="s.wajib" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">Wajib</label>
                        <button type="button" @click="syarat.splice(i, 1)" class="rounded p-1.5 text-error hover:bg-error-container/50" aria-label="Hapus"><x-ikon name="delete" class="text-[18px]" /></button>
                    </div>
                </template>
                <x-tombol varian="sekunder" ikon="add" x-on:click="syarat.push({ k: ++n, label: '', wajib: true })">Tambah Berkas</x-tombol>
            </div>
        </x-kartu>

        <div class="flex justify-end gap-2"><x-tombol :href="route('format-surat.index')" varian="sekunder">Batal</x-tombol><x-tombol type="submit" ikon="save">Simpan Format</x-tombol></div>
    </form>

    {{-- Pratinjau --}}
    <div x-show="lihat" x-cloak class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto bg-[#0f172a]/45 p-4" @keydown.escape.window="lihat = false">
        <div class="my-6 w-full max-w-3xl rounded-lg bg-surface-container-lowest shadow-modal" @click.outside="lihat = false">
            <div class="flex items-center justify-between border-b border-surface-container px-space-lg py-space-md"><h3 class="font-headline-md text-headline-md">Pratinjau (data contoh)</h3><button type="button" @click="lihat = false" class="rounded p-1 hover:bg-surface-container" aria-label="Tutup"><x-ikon name="close" class="text-[20px]" /></button></div>
            <style>@include('pdf._gaya')</style>
            <div class="bg-surface-container-high/60 p-4"><div class="surat layar mx-auto min-h-[400px] max-w-[700px] bg-white px-10 py-8 shadow" x-html="html"></div></div>
        </div>
    </div>
</div>
@push('skrip')
<script>
function builder(isian, syarat, sasaran, tokenTetap, tokenMhs, urlPratinjau) {
    let n = 0;
    return {
        n: 0, sasaran, jabatan: null, lihat: false, html: '',
        isian: isian.map(r => ({ ...r, k: ++n, baru: false, wajib: !!r.wajib })),
        syarat: syarat.map(s => ({ ...s, k: ++n, wajib: !!s.wajib })),
        init() { this.n = n; },
        tambah() { this.isian.push({ k: ++n, nama: '', label: '', tipe: 'teks', lebar: 'penuh', wajib: true, placeholder: '', opsi: '', baru: true }); this.n = n; },
        hapus(i) { this.isian.splice(i, 1); },
        naik(i) { if (i > 0) this.isian.splice(i - 1, 2, this.isian[i], this.isian[i - 1]); },
        turun(i) { if (i < this.isian.length - 1) this.isian.splice(i, 2, this.isian[i + 1], this.isian[i]); },
        slug(t, i) {
            let s = t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
            if (!s || !/^[a-z]/.test(s)) s = 'isian_' + s;
            s = s.slice(0, 40).replace(/_+$/, '');
            let dasar = s, c = 2;
            while (this.isian.some((x, j) => j !== i && x.nama === s)) s = dasar + '_' + c++;
            return s;
        },
        tokenLain() { return this.sasaran === 'mahasiswa' ? [...tokenTetap, ...tokenMhs] : tokenTetap; },
        el() { return this.$refs.templat; },
        sisip(awal, akhir) {
            const e = this.el(), a = e.selectionStart, b = e.selectionEnd, t = e.value;
            e.value = t.slice(0, a) + awal + t.slice(a, b) + akhir + t.slice(b);
            e.focus(); e.selectionStart = a + awal.length; e.selectionEnd = b + awal.length;
        },
        tok(k) { return '{' + '{ ' + k + ' }' + '}'; },
        sisipToken(k) { this.sisip(this.tok(k), ''); },
        tabel() {
            const baris = this.isian.filter(r => r.nama).map(r => `  <tr><td width="32%">${r.label}</td><td width="3%">:</td><td>${this.tok('isian.' + r.nama)}</td></tr>`).join('\n');
            this.sisip(`<table class="data">\n${baris || '  <tr><td>Keterangan</td><td>:</td><td></td></tr>'}\n</table>\n`, '');
        },
        async pratinjau() {
            const fd = new FormData(this.$refs.form); fd.delete('_method');
            const r = await fetch(urlPratinjau, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            this.html = r.ok ? (await r.json()).html : '<p>Pratinjau gagal: periksa isian (ada isian bertipe pilihan tanpa opsi?).</p>';
            this.lihat = true;
        },
    };
}
</script>
@endpush
</x-layouts::app>
