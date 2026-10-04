@php $paraf = $bolehParaf; $ttd = $bolehTtd; $tahap = $ttd ? 'Menunggu Tanda Tangan' : ($paraf ? 'Menunggu Paraf' : 'Pratinjau'); @endphp
<x-layouts::app :title="'Persetujuan '.$p->kode" :cari="false">
<div class="mx-auto max-w-7xl space-y-5">
    <nav class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('persetujuan.index') }}" class="hover:text-primary">Persetujuan</a><x-ikon name="chevron_right" class="text-[14px]" /><span class="font-semibold text-on-surface tabular">{{ $p->kode }}</span></nav>
    <header>
        <h1 class="font-headline-xl-mobile text-headline-xl-mobile lg:font-headline-xl lg:text-headline-xl">Persetujuan {{ $p->jenis->nama }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-secondary-container/40 px-3 py-1 font-label-md text-label-md text-on-secondary-fixed-variant"><span class="h-2 w-2 rounded-full bg-secondary"></span>{{ $tahap }}</span>
            <x-lencana-status :status="$p->status" />
            <span class="font-body-sm text-body-sm text-on-surface-variant">Pemohon: <strong>{{ $p->pemohon->nama }}</strong> ({{ $p->pemohon->nomor_induk }})</span>
        </div>
    </header>

    <div class="grid items-start gap-6 xl:grid-cols-3">
        {{-- Pratinjau surat (A4) --}}
        <div class="xl:col-span-2">
            <div class="rounded-xl bg-surface-container-high/60 p-3 sm:p-6">
                <style>@include('pdf._gaya')</style>
                <div class="mx-auto min-h-[1123px] w-full max-w-[794px] aspect-[210/297] bg-white px-[8%] py-12 shadow-md">
                    @include('pdf._surat', $dokumen)
                </div>
            </div>
            <p class="mt-2 flex items-center gap-1.5 font-body-sm text-body-sm text-on-surface-variant"><x-ikon name="info" class="text-[16px]" />Pratinjau sesuai tata letak cetak A4. Nomor surat @if ($surat->pakaiQr())dan kode QR @endif terbit otomatis saat surat disetujui penandatangan.</p>
        </div>

        <aside class="space-y-5">
            @if ($paraf || $ttd)
                <x-kartu :judul="$ttd ? 'Aksi Pengesahan Pimpinan' : 'Aksi Paraf'" ikon="draw">
                    @if ($ttd)
                        <form method="post" action="{{ route('persetujuan.tandatangani', $p) }}" class="space-y-3">@csrf
                            <div class="rounded-lg bg-surface-container-low p-3">
                                <p class="mb-2 flex items-center gap-1.5 font-label-md text-label-md font-semibold"><x-ikon name="key" class="text-[16px]" />Konfirmasi Kata Sandi Akun</p>
                                <p class="mb-2 font-body-sm text-body-sm text-on-surface-variant">Masukkan kata sandi akun Anda untuk membubuhkan tanda tangan elektronik pada surat ini.</p>
                                <div x-data="{ lihat: false }" class="relative">
                                    <input :type="lihat ? 'text' : 'password'" name="password" autocomplete="current-password" required placeholder="Kata sandi" class="h-10 w-full rounded-lg border-outline-variant bg-surface-container-lowest pr-10 font-body-md text-body-md focus:ring-2 focus:ring-primary">
                                    <button type="button" @click="lihat = !lihat" class="absolute right-2 top-1/2 -translate-y-1/2 text-outline"><x-ikon name="visibility" class="text-[20px]" /></button>
                                </div>
                                @error('password')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
                            </div>
                            <x-tombol type="submit" ukuran="lg" ikon="lock" class="w-full">{{ $surat->pakaiQr() ? 'Setujui & Tanda Tangani (TTE)' : 'Setujui & Terbitkan Nomor' }}</x-tombol>
                            @unless ($surat->pakaiQr())<p class="font-body-sm text-body-sm text-on-surface-variant">Surat tanpa QR: setelah terbit dicetak, ditandatangani basah, dan diberi cap.</p>@endunless
                        </form>
                    @else
                        <form method="post" action="{{ route('persetujuan.paraf', $p) }}" class="space-y-3">@csrf
                            <x-textarea label="Catatan paraf (opsional)" name="catatan" rows="2" />
                            <x-tombol type="submit" ukuran="lg" ikon="task_alt" class="w-full">Paraf & Setujui</x-tombol>
                        </form>
                    @endif
                    <div class="mt-3 space-y-2">
                        <x-tombol varian="lembut" ikon="assignment_return" class="w-full" x-on:click="$dispatch('buka-modal','kembalikan')">Kembalikan untuk Revisi</x-tombol>
                        <button type="button" x-on:click="$dispatch('buka-modal','tolak')" class="flex w-full items-center justify-center gap-1.5 py-2 font-label-md text-label-md font-semibold text-error hover:underline"><x-ikon name="cancel" class="text-[18px]" />Tolak Pengajuan Surat</button>
                    </div>
                </x-kartu>
                <x-modal nama="kembalikan" judul="Kembalikan untuk Revisi">
                    <form method="post" action="{{ route('persetujuan.kembalikan', $p) }}" class="space-y-4">@csrf
                        <x-textarea label="Catatan revisi" name="catatan" wajib rows="4" bantuan="Pengajuan kembali ke verifikator beserta catatan Anda." />
                        <div class="flex justify-end gap-2"><x-tombol varian="sekunder" x-on:click="$dispatch('tutup-modal')">Batal</x-tombol><x-tombol type="submit">Kembalikan</x-tombol></div>
                    </form>
                </x-modal>
                <x-modal nama="tolak" judul="Tolak Pengajuan">
                    <form method="post" action="{{ route('persetujuan.tolak', $p) }}" class="space-y-4">@csrf
                        <x-textarea label="Alasan penolakan" name="alasan" wajib rows="4" bantuan="Alasan akan dikirim ke pemohon." />
                        <div class="flex justify-end gap-2"><x-tombol varian="sekunder" x-on:click="$dispatch('tutup-modal')">Batal</x-tombol><x-tombol type="submit" varian="bahaya">Tolak Pengajuan</x-tombol></div>
                    </form>
                </x-modal>
            @else
                <x-peringatan jenis="info">Anda tidak memiliki tindakan yang menunggu pada pengajuan ini.</x-peringatan>
            @endif

            <x-kartu judul="Rantai Verifikasi Dokumen" ikon="timeline">
                <x-garis-waktu>
                    @foreach ($p->garisWaktu() as $t)
                        <x-garis-waktu.butir :status="$t['status']">
                            <p class="font-label-lg text-label-lg">{{ $t['judul'] }} @if ($t['waktu'])<span class="font-body-sm text-body-sm font-normal text-on-surface-variant tabular">· {{ $t['waktu']->translatedFormat('j M H:i') }}</span>@endif</p>
                            @if ($t['oleh'])<p class="font-body-sm text-body-sm text-on-surface-variant">{{ $t['oleh'] }}</p>@endif
                            @if ($t['catatan'])<p class="font-body-sm text-body-sm italic">"{{ $t['catatan'] }}"</p>@endif
                        </x-garis-waktu.butir>
                    @endforeach
                </x-garis-waktu>
                @if ($p->lampiran->isNotEmpty())
                    <div class="mt-4 space-y-1.5 border-t border-surface-container pt-3">
                        <p class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Lampiran pemohon</p>
                        @foreach ($p->lampiran as $l)<a href="{{ route('lampiran.unduh', $l) }}" target="_blank" class="flex items-center gap-2 font-body-sm text-body-sm text-primary hover:underline"><x-ikon name="attach_file" class="text-[16px]" />{{ $l->label }}</a>@endforeach
                    </div>
                @endif
                <div class="mt-4 flex items-center gap-2 rounded-lg bg-surface-container-low p-3 font-body-sm text-body-sm text-on-surface-variant"><x-ikon name="enhanced_encryption" class="text-[20px] text-primary" />{{ $surat->pakaiQr() ? 'Tanda tangan elektronik Ed25519 + hash SHA-256 pada PDF final.' : 'Surat tanpa QR: tanda tangan basah + cap setelah dicetak.' }}</div>
            </x-kartu>
        </aside>
    </div>
</div>
</x-layouts::app>
