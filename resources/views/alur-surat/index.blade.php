<x-layouts::app title="Alur Surat" :cari="false">
@php
    $kelompok = [
        ['mahasiswa', 'Layanan Mahasiswa', 'school', 'Diajukan mahasiswa lewat e-Layanan. Tiap pengajuan diverifikasi dulu, lalu (bila ber-QR) diparaf dan ditandatangani.', $mahasiswa],
        ['staf', 'Surat Keluar', 'outbox', 'Dibuat Admin TU atau pejabat dari format. Surat ber-QR melewati paraf (opsional) dan tanda tangan sebelum nomor terbit.', $staf],
    ];
@endphp
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="font-headline-xl text-headline-xl">Alur Surat</h1>
            <p class="mt-1 max-w-2xl font-body-md text-body-md text-on-surface-variant">Atur <strong>siapa yang memverifikasi, memaraf, dan menandatangani</strong> setiap jenis surat, serta bentuknya (ber-QR atau tanpa QR). Isian dan templat surat tetap diatur di <a class="font-semibold text-primary underline" href="{{ route('format-surat.index') }}">Format Surat</a>.</p></div>
        <x-tombol :href="route('format-surat.index')" varian="sekunder" ikon="edit_document">Format Surat</x-tombol>
    </div>

    <x-peringatan jenis="info">Surat <strong>tanpa QR</strong> tidak melewati paraf dan tanda tangan elektronik: setelah diverifikasi/diajukan, nomor langsung terbit untuk dicetak dan ditandatangani basah. Surat bebas (tanpa format) memilih paraf dan penandatangannya sendiri saat dibuat.</x-peringatan>

    <div class="flex justify-end gap-2">
        <x-tombol varian="lembut" ukuran="sm" onclick="window.dispatchEvent(new CustomEvent('alur-semua', { detail: true }))">Buka semua</x-tombol>
        <x-tombol varian="lembut" ukuran="sm" onclick="window.dispatchEvent(new CustomEvent('alur-semua', { detail: false }))">Ciutkan semua</x-tombol>
    </div>

    @foreach ($kelompok as [$kunci, $judul, $ikonKelompok, $ket, $daftar])
        <section class="space-y-3">
            <div class="flex items-start gap-3"><span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-fixed/50 text-primary"><x-ikon :name="$ikonKelompok" class="text-[20px]" /></span>
                <div class="min-w-0 flex-1"><h2 class="font-headline-md text-headline-md">{{ $judul }}</h2><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $ket }}</p></div></div>

            @forelse ($daftar as $f)
                @php
                    $e = $errors->getBag('f'.$f->id);
                    $gagal = $e->any();
                    $v = fn ($k, $b = null) => $gagal ? old($k, $b) : $b;
                    $mhs = $f->sasaran === 'mahasiswa';
                @endphp
                @php
                    $tahapAwal = collect($f->tahapAlur());
                    $verifikasiAwal = $gagal ? array_values((array) old('verifikasi', [])) : $tahapAwal->where('tahap', 'verifikasi')->pluck('peran')->values()->all();
                    $parafAwal = $gagal ? array_values((array) old('paraf', [])) : $tahapAwal->where('tahap', 'paraf')->pluck('peran')->values()->all();
                    $dataAlpine = '{ lihat: '.($gagal ? 'true' : 'false').', buka: '.($gagal ? 'true' : 'false').', mode: '.\Illuminate\Support\Js::from($v('mode_ttd', $f->mode_ttd) === 'basah' ? 'basah' : 'qr').', verifikasi: '.\Illuminate\Support\Js::from($verifikasiAwal).', paraf: '.\Illuminate\Support\Js::from($parafAwal).' }';
                @endphp
                <x-kartu :id="'f'.$f->id" padding="p-space-md" :x-data="$dataAlpine" x-on:alur-semua.window="lihat = $event.detail; if (!lihat) buka = false">
                    <div class="flex items-center justify-between gap-3">
                        <button type="button" class="flex min-w-0 flex-1 items-center gap-2 rounded-lg text-left" x-on:click="lihat = !lihat; if (!lihat) buka = false" x-bind:aria-expanded="lihat">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-fixed/50 text-primary"><x-ikon :name="$f->ikon" class="text-[18px]" /></span>
                            <span class="min-w-0 flex-1 break-words font-label-lg text-label-lg">{{ $f->nama }}@if (! $f->aktif) <span class="font-body-sm text-body-sm font-normal text-on-surface-variant">· Nonaktif</span>@endif</span>
                            <x-ikon name="expand_more" class="shrink-0 text-[22px] text-on-surface-variant transition-transform" x-bind:class="lihat ? 'rotate-180' : ''" />
                        </button>
                    </div>

                    <div x-show="lihat" x-cloak class="mt-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="min-w-0 flex-1 font-body-sm text-body-sm text-on-surface-variant">@if ($mhs)Target {{ $f->sla_hari }} hari kerja @else Format surat keluar @endif @if ($f->berjalan_count)· <span class="font-semibold text-primary">{{ $f->berjalan_count }} pengajuan berjalan</span>@endif</p>
                        <x-tombol varian="sekunder" ukuran="sm" ikon="edit" class="shrink-0 self-start sm:self-auto" x-on:click="buka = !buka"><span x-text="buka ? 'Tutup' : 'Ubah alur'"></span></x-tombol>
                    </div>

                    <ol class="mt-4 flex flex-wrap items-stretch gap-2" aria-label="Tahapan alur {{ $f->nama }}">
                        @foreach ($f->langkahAlur() as $i => $l)
                            @if ($i)<li class="flex items-center text-outline" aria-hidden="true"><x-ikon name="arrow_forward" class="text-[18px]" /></li>@endif
                            <li @class(['flex min-w-[9rem] flex-1 items-start gap-2 rounded-lg border p-2.5', 'border-outline-variant bg-surface-container-low' => empty($l['peringatan']), 'border-error/40 bg-error-container/40' => ! empty($l['peringatan'])])>
                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-container-lowest text-primary"><x-ikon :name="$l['ikon']" class="text-[16px]" /></span>
                                <div class="min-w-0"><p class="font-label-md text-label-md">{{ $l['judul'] }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $l['oleh'] }}</p>
                                    @if (! empty($l['peringatan']))<p class="mt-1 flex items-start gap-1 font-body-sm text-body-sm font-semibold text-error"><x-ikon name="warning" class="mt-0.5 text-[14px]" />{{ $l['peringatan'] }}</p>@endif</div>
                            </li>
                        @endforeach
                    </ol>

                    <form method="post" action="{{ route('alur-surat.perbarui', $f) }}" x-show="buka" x-cloak class="mt-4 space-y-4 border-t border-outline-variant pt-4">@csrf @method('PUT')
                        @if ($gagal)<x-peringatan jenis="bahaya"><ul class="list-inside list-disc">@foreach ($e->all() as $m)<li>{{ $m }}</li>@endforeach</ul></x-peringatan>@endif
                        @if ($mhs && $f->berjalan_count)<x-peringatan jenis="peringatan">{{ $f->berjalan_count }} pengajuan sedang berjalan. Perubahan tahapan hanya berlaku untuk pengajuan baru; yang berjalan tetap memakai tahapan saat diajukan. Bentuk surat (ber-QR/tanpa QR) baru bisa diubah setelah semuanya selesai atau ditolak.</x-peringatan>@endif
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-select label="Bentuk surat" name="mode_ttd" x-model="mode" bantuan="Ber-QR: paraf/tanda tangan elektronik. Tanpa QR: langsung terbit untuk dicetak.">
                                <option value="qr" @selected($v('mode_ttd', $f->mode_ttd) === 'qr')>Ber-QR (tanda tangan elektronik)</option>
                                <option value="basah" @selected($v('mode_ttd', $f->mode_ttd) === 'basah')>Tanpa QR (cetak, tanda tangan basah &amp; cap)</option>
                            </x-select>
                            @if ($mhs)<x-input label="Target selesai (hari kerja)" name="sla_hari" type="number" min="1" max="30" :value="$v('sla_hari', $f->sla_hari)" />@endif
                        </div>

                        <div>
                            <p class="mb-2 font-label-lg text-label-lg">Tahapan <span class="font-body-sm text-body-sm font-normal text-on-surface-variant">— atur langsung pada kotak: pilih pelaksana, geser, hapus, atau tambah tahap.</span></p>
                            <div class="flex flex-wrap items-stretch gap-2 overflow-x-auto rounded-lg border border-outline-variant bg-surface p-3">
                                <div class="flex w-40 items-start gap-2 rounded-lg border border-outline-variant bg-surface-container-lowest p-2.5">
                                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-container text-primary"><x-ikon :name="$mhs ? 'send' : 'edit_note'" class="text-[16px]" /></span>
                                    <div><p class="font-label-md text-label-md">{{ $mhs ? 'Pengajuan' : 'Draf surat' }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $mhs ? 'Mahasiswa' : 'Admin TU / pejabat' }}</p></div>
                                </div>
                                @if ($mhs)
                                    @include('alur-surat._daftar-tahap', ['model' => 'verifikasi', 'judul' => 'Verifikasi', 'ikon' => 'fact_check', 'opsi' => ['admin_tu' => 'Admin TU', 'kaprodi' => 'Kaprodi (prodi pemohon)'], 'bawaan' => 'admin_tu', 'tambah' => 'Tambah verifikasi', 'minimal' => 1])
                                @endif
                                <div class="contents" x-show="mode === 'qr'" x-cloak>
                                    @include('alur-surat._daftar-tahap', ['model' => 'paraf', 'judul' => 'Paraf', 'ikon' => 'edit_note', 'opsi' => ['wakil_dekan' => 'Wakil Dekan', 'kaprodi' => 'Kaprodi'], 'bawaan' => 'wakil_dekan', 'tambah' => 'Tambah paraf'])
                                </div>
                                <div class="flex items-center text-outline" aria-hidden="true"><x-ikon name="arrow_forward" class="text-[18px]" /></div>
                                <div x-show="mode === 'qr'" x-cloak class="flex w-52 flex-col gap-2 rounded-lg border border-primary/40 bg-primary-fixed/20 p-2.5">
                                    <div class="flex items-center gap-2"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-container-lowest text-primary"><x-ikon name="draw" class="text-[16px]" /></span><p class="font-label-md text-label-md">Tanda tangan (QR)</p></div>
                                    <x-select name="penandatangan_jabatan_id" aria-label="Penandatangan">
                                        @foreach ($jabatan as $j)<option value="{{ $j->id }}" @selected((int) $v('penandatangan_jabatan_id', $f->penandatangan_jabatan_id) === $j->id)>{{ $j->nama }}{{ $j->pejabat ? ' — '.$j->pejabat->namaLengkap() : ' (belum ada pejabat)' }}</option>@endforeach
                                    </x-select>
                                </div>
                                <div x-show="mode === 'basah'" x-cloak class="flex w-52 items-start gap-2 rounded-lg border border-outline-variant bg-surface-container-lowest p-2.5">
                                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-container text-primary"><x-ikon name="print" class="text-[16px]" /></span>
                                    <div><p class="font-label-md text-label-md">Terbit &amp; cetak</p><p class="font-body-sm text-body-sm text-on-surface-variant">Tanda tangan basah dan cap di TU</p></div>
                                </div>
                                <div x-show="mode === 'qr'" x-cloak class="flex items-center text-outline" aria-hidden="true"><x-ikon name="arrow_forward" class="text-[18px]" /></div>
                                <div x-show="mode === 'qr'" x-cloak class="flex w-40 items-start gap-2 rounded-lg border border-outline-variant bg-surface-container-lowest p-2.5">
                                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-container text-primary"><x-ikon name="verified" class="text-[16px]" /></span>
                                    <div><p class="font-label-md text-label-md">Terbit</p><p class="font-body-sm text-body-sm text-on-surface-variant">Nomor &amp; QR verifikasi</p></div>
                                </div>
                            </div>
                        </div>
                        @unless ($mhs)<p class="font-body-sm text-body-sm text-on-surface-variant">Perubahan berlaku untuk surat yang dibuat setelah ini; draf yang sudah ada tetap memakai alur lamanya.</p>@endunless
                        <div class="flex justify-end gap-2"><x-tombol varian="sekunder" x-on:click="buka = false">Batal</x-tombol><x-tombol type="submit" ikon="save">Simpan Alur</x-tombol></div>
                    </form>
                    </div>
                </x-kartu>
            @empty
                <x-kartu padding="p-space-md"><p class="text-on-surface-variant">Belum ada format untuk kelompok ini.</p></x-kartu>
            @endforelse
        </section>
    @endforeach
</div>
</x-layouts::app>
