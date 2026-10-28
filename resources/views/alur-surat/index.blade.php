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

    @foreach ($kelompok as [$kunci, $judul, $ikonKelompok, $ket, $daftar])
        <section class="space-y-3">
            <div class="flex items-start gap-3"><span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-fixed/50 text-primary"><x-ikon :name="$ikonKelompok" class="text-[20px]" /></span>
                <div><h2 class="font-headline-md text-headline-md">{{ $judul }}</h2><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $ket }}</p></div></div>

            @forelse ($daftar as $f)
                @php
                    $e = $errors->getBag('f'.$f->id);
                    $gagal = $e->any();
                    $v = fn ($k, $b = null) => $gagal ? old($k, $b) : $b;
                    $mhs = $f->sasaran === 'mahasiswa';
                @endphp
                <x-kartu :id="'f'.$f->id" padding="p-space-md" x-data="{ buka: {{ $gagal ? 'true' : 'false' }}, mode: '{{ $v('mode_ttd', $f->mode_ttd) === 'basah' ? 'basah' : 'qr' }}', paraf: {{ $v('perlu_paraf', $f->perlu_paraf) ? 'true' : 'false' }} }">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-fixed/50 text-primary"><x-ikon :name="$f->ikon" class="text-[18px]" /></span>
                            <div class="min-w-0"><p class="truncate font-label-lg text-label-lg">{{ $f->nama }}</p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant">@if (! $f->aktif)Nonaktif · @endif @if ($mhs)Target {{ $f->sla_hari }} hari kerja @else Format surat keluar @endif @if ($f->berjalan_count)· <span class="font-semibold text-primary">{{ $f->berjalan_count }} pengajuan berjalan</span>@endif</p></div></div>
                        <x-tombol varian="sekunder" ukuran="sm" ikon="edit" x-on:click="buka = !buka"><span x-text="buka ? 'Tutup' : 'Ubah alur'"></span></x-tombol>
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
                        @if ($mhs && $f->berjalan_count)<x-peringatan jenis="peringatan">{{ $f->berjalan_count }} pengajuan sedang berjalan. Bentuk, pemverifikasi, dan paraf baru bisa diubah setelah semuanya selesai atau ditolak; penandatangan dan target waktu tetap bisa diganti.</x-peringatan>@endif
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-select label="Bentuk surat" name="mode_ttd" x-model="mode" bantuan="Ber-QR: paraf/tanda tangan elektronik. Tanpa QR: langsung terbit untuk dicetak.">
                                <option value="qr" @selected($v('mode_ttd', $f->mode_ttd) === 'qr')>Ber-QR (tanda tangan elektronik)</option>
                                <option value="basah" @selected($v('mode_ttd', $f->mode_ttd) === 'basah')>Tanpa QR (cetak, tanda tangan basah &amp; cap)</option>
                            </x-select>
                            @if ($mhs)
                                <x-select label="Diverifikasi oleh" name="verifikator_role">
                                    <option value="admin_tu" @selected($v('verifikator_role', $f->verifikator_role) === 'admin_tu')>Admin TU</option>
                                    <option value="kaprodi" @selected($v('verifikator_role', $f->verifikator_role) === 'kaprodi')>Kaprodi (prodi pemohon)</option>
                                </x-select>
                            @endif
                            <x-select label="Penandatangan" name="penandatangan_jabatan_id" bantuan="Pejabat yang sedang menjabat pada jabatan ini yang menandatangani.">
                                @foreach ($jabatan as $j)<option value="{{ $j->id }}" @selected((int) $v('penandatangan_jabatan_id', $f->penandatangan_jabatan_id) === $j->id)>{{ $j->nama }}{{ $j->pejabat ? ' — '.$j->pejabat->namaLengkap() : ' (belum ada pejabat)' }}</option>@endforeach
                            </x-select>
                            @if ($mhs)<x-input label="Target selesai (hari kerja)" name="sla_hari" type="number" min="1" max="30" :value="$v('sla_hari', $f->sla_hari)" />@endif
                        </div>
                        <div x-show="mode === 'qr'" class="space-y-2">
                            <label class="flex cursor-pointer items-center gap-2 font-label-lg text-label-lg"><input type="checkbox" name="perlu_paraf" value="1" x-model="paraf" class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">Perlu paraf sebelum tanda tangan</label>
                            <div x-show="paraf" x-cloak class="md:w-1/2"><x-select label="Pemaraf" name="paraf_role"><option value="wakil_dekan" @selected($v('paraf_role', $f->paraf_role) === 'wakil_dekan')>Wakil Dekan</option><option value="kaprodi" @selected($v('paraf_role', $f->paraf_role) === 'kaprodi')>Kaprodi</option></x-select></div>
                        </div>
                        @unless ($mhs)<p class="font-body-sm text-body-sm text-on-surface-variant">Perubahan berlaku untuk surat yang dibuat setelah ini; draf yang sudah ada tetap memakai alur lamanya.</p>@endunless
                        <div class="flex justify-end gap-2"><x-tombol varian="sekunder" x-on:click="buka = false">Batal</x-tombol><x-tombol type="submit" ikon="save">Simpan Alur</x-tombol></div>
                    </form>
                </x-kartu>
            @empty
                <x-kartu padding="p-space-md"><p class="text-on-surface-variant">Belum ada format untuk kelompok ini.</p></x-kartu>
            @endforelse
        </section>
    @endforeach
</div>
</x-layouts::app>
