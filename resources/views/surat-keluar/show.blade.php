@php
    $tahap = collect($s->tahapan())->map(fn ($t) => [$t['judul'], $t['status'], $t['waktu'], $t['oleh'], $t['catatan']]);
@endphp
<x-layouts::app :title="$s->perihal" :cari="false">
<div class="mx-auto max-w-7xl space-y-5">
    <nav class="flex items-center gap-1.5 font-label-sm text-label-sm text-on-surface-variant"><a href="{{ route('surat-keluar.index') }}" class="hover:text-primary">Surat Keluar</a><x-ikon name="chevron_right" class="text-[14px]" /><span class="text-on-surface">{{ $s->nomor ?? 'Draf' }}</span></nav>
    <header class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div><h1 class="font-headline-xl-mobile text-headline-xl-mobile lg:font-headline-xl lg:text-headline-xl">{{ $s->perihal }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2"><span class="rounded-full bg-secondary-container/40 px-3 py-1 font-label-md text-label-md text-on-secondary-fixed-variant">{{ $statusList[$s->status] }}</span><span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-1 font-label-md text-label-md"><x-ikon name="{{ $s->pakaiQr() ? 'qr_code_2' : 'print' }}" class="text-[16px] text-primary" />{{ $s->pakaiQr() ? 'Ber-QR (TTE)' : 'Tanpa QR' }}</span><span class="font-body-sm text-body-sm text-on-surface-variant">Sifat: {{ ucfirst($s->sifat) }} • Klasifikasi {{ $s->klasifikasi?->kode }}</span>
                <span class="inline-flex items-center gap-1 rounded-full bg-surface-container px-2.5 py-1 font-label-md text-label-md"><x-ikon name="calendar_today" class="text-[14px] text-primary" />{{ $s->tgl_surat ? $s->tgl_surat->translatedFormat('j F Y').' • '.\App\Support\TanggalHijriah::format($s->tgl_surat) : 'Tanggal: hari penandatanganan' }}</span></div></div>
        @if ($s->file_pdf && $s->status !== 'draf')<x-tombol :href="route('surat.pdf', $s)" ikon="download">Unduh PDF</x-tombol>@endif
    </header>
    @if ($s->status === 'batal')<x-peringatan jenis="bahaya" judul="Surat ini telah dibatalkan">Nomor {{ $s->nomor }} tidak dipakai ulang. Alasan: {{ $s->alasan_batal }}. QR akan menampilkan "TIDAK BERLAKU".</x-peringatan>@endif

    <div class="grid items-start gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <x-cetak-surat :dokumen="$dokumen" />
            <div class="rounded-xl bg-surface-container-high/60 p-3 sm:p-6">
                <style>@include('pdf._gaya')</style>
                <div id="kertas-cetak" class="relative mx-auto min-h-[1123px] w-full max-w-[794px] aspect-[210/297] bg-white px-[8%] pb-20 pt-12 shadow-md">@include('pdf._surat', $dokumen)</div>
            </div>
        </div>
        <aside class="space-y-5">
            @if (auth()->user()->adalahAdmin() && ! in_array($s->status, ['ditandatangani', 'batal'], true))
                <x-kartu judul="Nomor Surat" ikon="numbers" deskripsi="{{ \App\Support\NomorManual::wajib() ? 'Penomoran manual: nomor wajib diisi TU.' : 'Ketik angka urut atau edit nomor lengkap; kosongkan untuk nomor otomatis.' }}">
                    <form method="post" action="{{ route('surat-keluar.nomor', $s) }}" class="space-y-2">@csrf
                        <x-input label="Nomor surat" name="nomor_manual" :value="old('nomor_manual', $s->nomor_manual)" placeholder="{{ \App\Support\NomorManual::usulan($s) }}" :bantuan="\App\Support\NomorManual::lengkap($s) ? 'Nomor yang akan terbit: '.\App\Support\NomorManual::lengkap($s) : 'Nomor otomatis berikutnya: '.\App\Support\NomorManual::usulan($s).' (angka urut saja juga boleh, mis. 009)'" />
                        @if (\App\Support\NomorManual::usulan($s))<button type="button" class="font-label-sm text-label-sm text-primary hover:underline" onclick="this.closest('form').querySelector('[name=nomor_manual]').value = @js(\App\Support\NomorManual::usulan($s))">Isi dengan nomor usulan, lalu edit</button>@endif
                        <x-tombol type="submit" varian="sekunder" ikon="save" class="w-full">Simpan Nomor</x-tombol>
                    </form>
                </x-kartu>
            @endif
            @if ($izin['ubah'] || $izin['paraf'] || $izin['ttd'] || $izin['batal'])
                <x-kartu judul="Tindakan" ikon="gavel">
                    <div class="space-y-3">
                        @if ($izin['ubah'])
                            <form method="post" action="{{ route('surat-keluar.ajukan', $s) }}">@csrf<x-tombol type="submit" ukuran="lg" ikon="send" class="w-full">{{ $s->langsungTerbit() ? 'Terbitkan Surat (tanpa persetujuan)' : 'Ajukan untuk '.(($s->data['paraf_role'] ?? null) ? 'Paraf' : 'Tanda Tangan') }}</x-tombol></form>
                            <div class="flex gap-2"><x-tombol :href="route('surat-keluar.ubah', $s)" varian="sekunder" ikon="edit" class="flex-1">Ubah</x-tombol>
                                <form method="post" action="{{ route('surat-keluar.hapus', $s) }}" onsubmit="return confirm('Hapus draf ini?')">@csrf @method('DELETE')<x-tombol type="submit" varian="bahaya" ikon="delete">Hapus</x-tombol></form></div>
                        @endif
                        @if ($izin['paraf'])
                            <form method="post" action="{{ route('surat-keluar.paraf', $s) }}" class="space-y-2">@csrf<x-textarea label="Catatan paraf (opsional)" name="catatan" rows="2" /><x-tombol type="submit" ukuran="lg" ikon="task_alt" class="w-full">Paraf & Teruskan</x-tombol></form>
                        @endif
                        @if ($izin['ttd'])
                            <form method="post" action="{{ route('surat-keluar.tandatangani', $s) }}" class="space-y-2">@csrf
                                <x-input label="Konfirmasi kata sandi akun" name="password" type="password" wajib autocomplete="current-password" :bantuan="$s->pakaiQr() ? 'Diperlukan untuk membubuhkan tanda tangan elektronik.' : 'Diperlukan untuk menyetujui dan menerbitkan nomor surat.'" />
                                <x-tombol type="submit" ukuran="lg" ikon="lock" class="w-full">{{ $s->pakaiQr() ? 'Setujui & Tanda Tangani (TTE)' : 'Setujui & Terbitkan Nomor' }}</x-tombol>
                                @unless ($s->pakaiQr())<p class="font-body-sm text-body-sm text-on-surface-variant">Setelah terbit, unduh PDF, cetak, lalu tanda tangani basah dan beri cap.</p>@endunless</form>
                        @endif
                        @if ($izin['paraf'] || $izin['ttd'])<x-tombol varian="lembut" ikon="assignment_return" class="w-full" x-on:click="$dispatch('buka-modal','kembalikan')">Kembalikan untuk Revisi</x-tombol>@endif
                        @if ($izin['batal'])<x-tombol varian="bahaya" ikon="block" class="w-full" x-on:click="$dispatch('buka-modal','batalkan')">Batalkan Surat Terbit</x-tombol>@endif
                    </div>
                </x-kartu>
                <x-modal nama="kembalikan" judul="Kembalikan untuk Revisi"><form method="post" action="{{ route('surat-keluar.kembalikan', $s) }}" class="space-y-4">@csrf<x-textarea label="Catatan revisi" name="catatan" wajib rows="4" /><div class="flex justify-end gap-2"><x-tombol varian="sekunder" x-on:click="$dispatch('tutup-modal')">Batal</x-tombol><x-tombol type="submit">Kembalikan</x-tombol></div></form></x-modal>
                <x-modal nama="batalkan" judul="Batalkan Surat Terbit"><form method="post" action="{{ route('surat-keluar.batalkan', $s) }}" class="space-y-4">@csrf<p class="font-body-sm text-body-sm text-on-surface-variant">Surat yang dibatalkan tetap tercatat; nomornya <strong>tidak dipakai ulang</strong>@if ($s->pakaiQr()) dan QR menampilkan "TIDAK BERLAKU"@endif.</p><x-textarea label="Alasan pembatalan" name="alasan" wajib rows="3" /><div class="flex justify-end gap-2"><x-tombol varian="sekunder" x-on:click="$dispatch('tutup-modal')">Batal</x-tombol><x-tombol type="submit" varian="bahaya">Batalkan Surat</x-tombol></div></form></x-modal>
            @endif
            <x-kartu judul="Tahapan Surat" deskripsi="Semua tahap dari draf sampai terbit." ikon="timeline">
                <x-garis-waktu>@foreach ($tahap as [$j, $st, $w, $oleh, $cat])
                    <x-garis-waktu.butir :status="$st"><p class="font-label-lg text-label-lg">{{ $j }} @if ($w)<span class="font-body-sm text-body-sm font-normal text-on-surface-variant">· {{ $w->translatedFormat('j M H:i') }}</span>@endif</p>@if ($oleh)<p class="font-body-sm text-body-sm text-on-surface-variant">{{ $oleh }}</p>@endif @if ($cat)<p class="font-body-sm text-body-sm italic">"{{ $cat }}"</p>@endif</x-garis-waktu.butir>
                @endforeach</x-garis-waktu>
            </x-kartu>
        </aside>
    </div>
</div>
</x-layouts::app>
