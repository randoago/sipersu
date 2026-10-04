@php
    $valid = ! $batal && $signatureSah;
    $nimSamar = $pemohon ? preg_replace('/^(\d{2})\d+(\d{3})$/', '$1•••$2', ($pemohon['npm'] ?? $pemohon['nim'])) : null;
@endphp
<x-layouts::guest title="Verifikasi Dokumen" :tanpa-livewire="true">
<div class="relative min-h-screen overflow-hidden bg-surface px-4 font-jakarta py-8 sm:px-6 lg:px-8 lg:py-14">
    <div class="pointer-events-none absolute inset-0 opacity-40">
        <svg class="h-full w-full" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="pola" width="160" height="160" patternTransform="rotate(25)" patternUnits="userSpaceOnUse"><text x="20" y="40" font-weight="700" font-size="16" letter-spacing="3" fill="#00512c" fill-opacity="0.04">VERIFIED</text><text x="40" y="110" font-weight="700" font-size="16" letter-spacing="3" fill="#785900" fill-opacity="0.04">FT-UMB</text><circle cx="80" cy="80" r="1.5" fill="#00512c" fill-opacity="0.1"/></pattern></defs><rect width="100%" height="100%" fill="url(#pola)"/></svg>
    </div>
    <div class="pointer-events-none absolute -top-32 left-1/2 h-[350px] w-[650px] -translate-x-1/2 bg-gradient-to-b from-primary-fixed/30 via-tertiary-fixed/15 to-transparent blur-3xl"></div>

    <div class="relative z-10 mx-auto flex max-w-4xl flex-col items-center">
        <header class="mb-8 flex w-full flex-col items-center text-center">
            <div class="mb-4 flex items-center justify-center rounded-xl bg-surface-container-lowest p-2 shadow-sm"><x-logo ukuran="h-16" /></div>
            <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-surface-container-high px-3 py-1 text-primary">
                <x-ikon name="verified_user" filled class="text-sm" /><span class="font-label-sm text-label-sm uppercase tracking-wider">Portal Autentikasi Publik Naskah Dinas</span>
            </div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">Universitas Muhammadiyah Buton</h1>
            <p class="mt-1 max-w-xl font-body-md text-body-md text-on-surface-variant">Sistem Informasi Persuratan (SIPERSU FT-UMB) • Layanan Verifikasi Tanda Tangan Elektronik</p>
        </header>

        {{-- Status utama --}}
        <section class="relative mb-8 flex w-full max-w-2xl flex-col items-center overflow-hidden rounded-xl bg-surface-container-lowest p-6 text-center shadow-md sm:p-8">
            <div class="pointer-events-none absolute -right-12 -top-12 h-32 w-32 rounded-full {{ $valid ? 'bg-primary/5' : 'bg-error/5' }}"></div>
            <div class="relative mb-4 flex h-20 w-20 items-center justify-center rounded-full shadow-sm {{ $valid ? 'bg-primary-fixed text-primary' : 'bg-error-container text-error' }}">
                <x-ikon :name="$valid ? 'task_alt' : 'cancel'" filled class="text-4xl" />
            </div>
            @if ($valid)
                <span class="mb-2 inline-block rounded-full bg-primary-container px-3 py-1 font-label-md text-label-md uppercase text-on-primary">Tanda Tangan Elektronik Sah</span>
                <h2 class="font-headline-xl text-headline-xl text-primary">Dokumen Asli – Terverifikasi</h2>
                <p class="mt-2 max-w-lg font-body-md text-body-md text-on-surface-variant">Naskah dinas ini diterbitkan oleh SIPERSU FT-UMB dan ditandatangani secara elektronik dengan kunci kriptografi Fakultas Teknik Universitas Muhammadiyah Buton. Tanda tangan pada data di bawah ini berhasil diperiksa dan tidak berubah.</p>
            @elseif ($batal)
                <span class="mb-2 inline-block rounded-full bg-error px-3 py-1 font-label-md text-label-md uppercase text-on-error">TIDAK BERLAKU</span>
                <h2 class="font-headline-xl text-headline-xl text-error">Dokumen Telah Dibatalkan</h2>
                <p class="mt-2 max-w-lg font-body-md text-body-md text-on-surface-variant">Surat ini pernah diterbitkan secara sah, namun <strong>telah dibatalkan</strong>@if ($surat->dibatalkan_pada) pada {{ $surat->dibatalkan_pada->translatedFormat('j F Y') }}@endif dan tidak berlaku lagi. @unless ($rahasia)Alasan: {{ $surat->alasan_batal ?: '—' }}@endunless</p>
            @else
                <span class="mb-2 inline-block rounded-full bg-error px-3 py-1 font-label-md text-label-md uppercase text-on-error">Tidak Valid</span>
                <h2 class="font-headline-xl text-headline-xl text-error">Tanda Tangan Tidak Cocok</h2>
                <p class="mt-2 max-w-lg font-body-md text-body-md text-on-surface-variant">Tanda tangan elektronik pada data surat ini tidak dapat diverifikasi. Jangan percaya dokumen ini dan hubungi Tata Usaha Fakultas Teknik.</p>
            @endif
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <div class="flex items-center gap-1.5 rounded-lg bg-surface-container px-3 py-1.5 font-label-sm text-label-sm"><x-ikon name="security" class="text-sm text-primary" />Tanda tangan Ed25519: {{ $signatureSah ? 'Cocok' : 'Tidak cocok' }}</div>
                <div class="flex items-center gap-1.5 rounded-lg bg-surface-container px-3 py-1.5 font-label-sm text-label-sm"><x-ikon name="schedule" class="text-sm text-primary" />Waktu tercatat otomatis</div>
            </div>
        </section>

        {{-- Data dokumen --}}
        <section class="mb-8 w-full rounded-xl bg-surface-container-lowest p-6 shadow-md sm:p-8">
            <div class="mb-6 flex flex-col justify-between rounded-lg bg-surface-container-low p-4 sm:flex-row sm:items-center">
                <div>
                    <span class="block font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Nomor Surat Resmi</span>
                    <span class="font-headline-md text-headline-md font-bold text-primary tabular">{{ $surat->nomor }}</span>
                </div>
                <div class="mt-3 sm:mt-0">
                    <span class="inline-flex items-center rounded-full px-3 py-1 font-label-sm text-label-sm {{ $batal ? 'bg-error text-on-error' : 'bg-primary text-on-primary' }}">
                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-secondary-container"></span>Status: {{ $batal ? 'Dibatalkan' : 'Aktif & Terdaftar' }}
                    </span>
                </div>
            </div>

            @if ($rahasia)
                <div class="rounded-lg bg-surface p-4"><p class="flex items-center gap-2 font-body-md text-body-md text-on-surface-variant"><x-ikon name="lock" class="text-lg" />Surat ini berklasifikasi <strong>rahasia</strong>. Rincian isi, pemohon, dan tujuan tidak ditampilkan pada halaman publik.</p></div>
                <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg bg-surface p-4"><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Diterbitkan oleh</dt><dd class="mt-1 font-label-lg text-label-lg">{{ $surat->penandatangan_jabatan }}</dd></div>
                    <div class="rounded-lg bg-surface p-4"><dt class="font-label-sm text-label-sm uppercase text-on-surface-variant">Tanggal Surat</dt><dd class="mt-1 font-label-lg text-label-lg">{{ $surat->tgl_surat?->translatedFormat('j F Y') }}</dd></div>
                </dl>
            @else
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="rounded-lg bg-surface p-4 md:col-span-2">
                        <span class="mb-1 block font-label-sm text-label-sm uppercase text-on-surface-variant">Perihal Dokumen</span>
                        <p class="font-headline-sm text-headline-sm text-on-surface">{{ $surat->perihal }}</p>
                    </div>
                    @if ($pemohon)
                        <div class="flex items-start gap-3 rounded-lg bg-surface p-4">
                            <div class="rounded-lg bg-surface-container p-2 text-primary"><x-ikon name="school" class="text-lg" /></div>
                            <div><span class="block font-label-sm text-label-sm uppercase text-on-surface-variant">Pemohon / Mahasiswa</span>
                                <p class="mt-1 font-label-lg text-label-lg">{{ \Illuminate\Support\Str::title(mb_strtolower($pemohon['nama'])) }}</p>
                                <p class="font-body-sm text-body-sm text-on-surface-variant tabular">NPM: {{ $nimSamar }}</p>
                                <p class="mt-0.5 font-body-sm text-body-sm font-semibold text-primary">Program Studi {{ $pemohon['prodi'] }}</p></div>
                        </div>
                    @endif
                    @if ($tujuan)
                        <div class="flex items-start gap-3 rounded-lg bg-surface p-4">
                            <div class="rounded-lg bg-surface-container p-2 text-secondary"><x-ikon name="apartment" class="text-lg" /></div>
                            <div><span class="block font-label-sm text-label-sm uppercase text-on-surface-variant">Instansi Tujuan</span><p class="mt-1 font-label-lg text-label-lg">{{ $tujuan }}</p></div>
                        </div>
                    @endif
                    <div class="flex items-start gap-3 rounded-lg bg-surface p-4">
                        <div class="rounded-lg bg-surface-container p-2 text-primary"><x-ikon name="badge" class="text-lg" /></div>
                        <div><span class="block font-label-sm text-label-sm uppercase text-on-surface-variant">Penanda Tangan Sah</span>
                            <p class="mt-1 font-headline-sm text-headline-sm">{{ $surat->penandatangan_nama }}</p>
                            <p class="mt-1 font-body-sm text-body-sm font-medium text-primary">{{ $surat->penandatangan_jabatan }} Universitas Muhammadiyah Buton</p></div>
                    </div>
                    <div class="flex items-start gap-3 rounded-lg bg-surface p-4">
                        <div class="rounded-lg bg-surface-container p-2 text-primary"><x-ikon name="calendar_today" class="text-lg" /></div>
                        <div><span class="block font-label-sm text-label-sm uppercase text-on-surface-variant">Tanggal Surat</span>
                            <p class="mt-1 font-headline-sm text-headline-sm">{{ $surat->tgl_surat?->translatedFormat('d F Y') }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ \App\Support\TanggalHijriah::format($surat->tgl_surat) }}</p></div>
                    </div>
                    <div class="flex items-start gap-3 rounded-lg bg-surface p-4">
                        <div class="rounded-lg bg-surface-container p-2 text-tertiary"><x-ikon name="history_edu" class="text-lg" /></div>
                        <div><span class="block font-label-sm text-label-sm uppercase text-on-surface-variant">Waktu Penandatanganan Digital</span>
                            <p class="mt-1 font-headline-sm text-headline-sm">{{ $surat->ditandatangani_pada->translatedFormat('d F Y') }}</p>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Pukul {{ $surat->ditandatangani_pada->format('H:i:s') }} WITA (Waktu Indonesia Tengah)</p></div>
                    </div>
                </div>
            @endif

            <div class="mt-6 rounded-lg bg-surface-container-low p-4">
                <div class="mb-2 flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-2"><x-ikon name="fingerprint" class="text-base text-primary" /><span class="font-label-sm text-label-sm font-semibold uppercase tracking-wider">Hash Keamanan Berkas PDF</span></div>
                    <span class="rounded bg-surface-container px-2 py-0.5 font-mono text-label-sm text-on-surface-variant">SHA-256</span>
                </div>
                <div class="select-all overflow-x-auto break-all rounded bg-surface-container-lowest p-3 font-mono text-mono-data text-on-surface-variant">{{ $surat->pdf_hash }}</div>
            </div>

            {{-- Riwayat surat --}}
            <div class="mt-6 border-t border-surface-container pt-6">
                <h3 class="mb-3 flex items-center gap-2 font-headline-sm text-headline-sm"><x-ikon name="history" class="text-primary" />Riwayat Surat</h3>
                <x-garis-waktu>
                    @foreach ($riwayat as $r)
                        <x-garis-waktu.butir :status="$r['status']">
                            <p class="font-label-lg text-label-lg">{{ $r['judul'] }} <span class="font-body-sm text-body-sm font-normal text-on-surface-variant tabular">· {{ $r['waktu']->translatedFormat('j F Y, H:i') }} WITA</span></p>
                            @if ($r['oleh'])<p class="font-body-sm text-body-sm text-on-surface-variant">{{ $r['oleh'] }}</p>@endif
                        </x-garis-waktu.butir>
                    @endforeach
                </x-garis-waktu>
                @if ($rahasia)<p class="mt-3 font-body-sm text-body-sm text-on-surface-variant">Surat berklasifikasi rahasia: hanya tahap penandatanganan yang ditampilkan.</p>@endif
            </div>

            {{-- Cocokkan berkas --}}
            <div class="mt-6 border-t border-surface-container pt-6">
                <h3 class="mb-1 flex items-center gap-2 font-headline-sm text-headline-sm"><x-ikon name="fact_check" class="text-primary" />Cek Integritas Berkas PDF</h3>
                <p class="mb-3 font-body-sm text-body-sm text-on-surface-variant">Unggah PDF surat yang Anda terima. Sistem menghitung hash SHA-256 di server dan mencocokkannya dengan arsip. Berkas tidak disimpan.</p>
                @if ($hasilBerkas === 'cocok')
                    <x-peringatan jenis="sukses" judul="Berkas cocok" class="mb-3">PDF yang Anda unggah identik dengan dokumen asli yang diterbitkan.</x-peringatan>
                @elseif ($hasilBerkas === 'beda')
                    <x-peringatan jenis="bahaya" judul="Berkas TIDAK cocok" class="mb-3">PDF yang Anda unggah berbeda dari dokumen asli. Dokumen mungkin telah diubah atau bukan salinan resmi.</x-peringatan>
                @endif
                <form method="post" action="{{ route('verifikasi.cek', $surat->qr_token) }}" enctype="multipart/form-data" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    @csrf
                    <div class="flex-1">
                        <input type="file" name="berkas" accept="application/pdf,.pdf" required class="block w-full rounded-lg border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm file:mr-3 file:rounded-l-lg file:border-0 file:bg-surface-container-high file:px-4 file:py-2.5 file:font-label-md">
                        @error('berkas')<p class="mt-1 font-body-sm text-body-sm text-[#e11d48]">{{ $message }}</p>@enderror
                    </div>
                    <x-tombol type="submit" ikon="fact_check">Cocokkan</x-tombol>
                </form>
            </div>
        </section>

        <section class="mb-8 w-full rounded-xl bg-surface-container-lowest p-6 shadow-md" x-data="{ buka: false }">
            <button type="button" @click="buka = !buka" class="flex w-full items-center justify-between text-left focus:outline-none">
                <span class="flex items-center gap-3"><span class="rounded-lg bg-surface-container p-2 text-primary"><x-ikon name="gavel" class="text-lg" /></span>
                    <span><span class="block font-headline-sm text-headline-sm">Tentang Verifikasi Ini</span><span class="font-body-sm text-body-sm text-on-surface-variant">Cara kerja tanda tangan elektronik SIPERSU FT-UMB</span></span></span>
                <x-ikon name="expand_more" class="text-on-surface-variant transition-transform" ::class="buka && 'rotate-180'" />
            </button>
            <div x-show="buka" x-cloak x-transition class="mt-4 space-y-3 rounded-lg bg-surface p-4 font-body-sm text-body-sm text-on-surface-variant">
                <p><strong>Cara kerja.</strong> Saat surat ditandatangani, sistem membuat tanda tangan digital (Ed25519) atas nomor, perihal, penanda tangan, jabatan, tanggal, dan ringkasan isi surat. Tanda tangan itu dicetak dalam kode QR dan diperiksa ulang setiap kali halaman ini dibuka.</p>
                <p><strong>Surat ber-TTE sah tanpa tanda tangan dan cap basah.</strong> Keaslian ditentukan oleh hasil pemeriksaan di halaman ini.</p>
                <p><strong>Tanpa internet/server?</strong> Tanda tangan pada QR juga dapat diperiksa secara offline lewat berkas verifikasi statis fakultas @if ($urlOffline)(<a class="font-semibold text-primary underline" href="{{ $urlOffline }}">{{ $urlOffline }}</a>)@endif.</p>
            </div>
        </section>

        <footer class="w-full px-4 py-6 text-center">
            <p class="mx-auto max-w-2xl font-body-sm text-body-sm leading-relaxed text-on-surface-variant">Halaman ini dihasilkan otomatis oleh server SIPERSU FT-UMB. Setiap pemindaian verifikasi dicatat.</p>
            <div class="mt-4 font-label-sm text-label-sm text-outline">© {{ now()->year }} Fakultas Teknik • Universitas Muhammadiyah Buton. Seluruh Hak Cipta Dilindungi.</div>
        </footer>
    </div>
</div>
</x-layouts::guest>
