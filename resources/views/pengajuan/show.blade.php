@php
    use App\Enums\StatusPengajuan as S;
    $milikSaya = $p->user_id === $user->id;
    $terbit = in_array($p->status, [S::Ditandatangani, S::Selesai], true) && $p->surat?->file_pdf;
    $garis = $p->garisWaktu();
    $selesaiN = collect($garis)->where('status', 'selesai')->count();
    $bolehV = $alur->bolehVerifikasi($p, $user);
    $bolehTolak = $alur->bolehTolak($p, $user);
    $bolehSelesai = $alur->bolehSelesaikan($p, $user);
    $bolehParaf = $alur->bolehParaf($p, $user) || $alur->bolehTandatangan($p, $user);
@endphp
<x-layouts::app :title="'Pengajuan '.$p->kode" :cari="false">
<div class="mx-auto max-w-6xl space-y-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-container/10 px-3 py-1 font-label-sm text-label-sm text-primary"><x-ikon name="verified_user" class="text-[14px]" />Sistem Pelacakan Resmi FT-UMB</span>
            <h1 class="mt-2 font-headline-xl-mobile text-headline-xl-mobile text-on-surface lg:font-headline-xl lg:text-headline-xl">{{ $milikSaya ? 'Lacak Status Permohonan Surat' : 'Detail Pengajuan Surat' }}</h1>
            <p class="mt-1 font-body-md text-body-md text-on-surface-variant">Alur verifikasi berkas permohonan secara transparan dan berjenjang dari Tata Usaha hingga Dekanat.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-lencana-status :status="$p->status" class="!text-label-lg" />
            @if ($milikSaya)<form method="get" action="{{ route('layanan.lacak') }}" class="hidden sm:block"><input name="kode" placeholder="Cari kode…" class="h-9 w-44 rounded-lg border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm focus:ring-2 focus:ring-primary"></form>@endif
        </div>
    </header>

    {{-- Banner hasil --}}
    @if ($terbit)
        <section class="flex flex-col gap-4 rounded-xl bg-gradient-to-br from-primary to-primary-container p-space-lg text-on-primary shadow-md lg:flex-row lg:items-center">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-white/15"><x-ikon name="verified" filled class="text-[30px] text-secondary-container" /></div>
            <div class="min-w-0 flex-1">
                <span class="mb-1 inline-block rounded-full bg-secondary-container px-2.5 py-0.5 font-label-sm text-label-sm font-bold uppercase text-on-secondary-container">Dokumen Resmi Terbit</span>
                <span class="ml-1 font-label-md text-label-md tabular">No. Surat: {{ $p->surat->nomor }}</span>
                <h2 class="mt-1 font-headline-lg-mobile text-headline-lg-mobile font-bold lg:font-headline-lg lg:text-headline-lg">Surat Resmi Telah Diterbitkan & Ditandatangani!</h2>
                <p class="mt-1 font-body-sm text-body-sm text-on-primary-container">@if ($p->surat->pakaiQr())Ditandatangani secara elektronik oleh {{ $p->surat->penandatangan_nama }} ({{ $p->surat->penandatangan_jabatan }}) pada {{ $p->surat->ditandatangani_pada->translatedFormat('j F Y, H:i') }} WITA.@else Disetujui oleh {{ $p->surat->penandatangan_nama }} ({{ $p->surat->penandatangan_jabatan }}) pada {{ $p->surat->ditandatangani_pada->translatedFormat('j F Y, H:i') }} WITA. <strong>Cetak surat ini lalu minta tanda tangan basah dan cap di Tata Usaha.</strong>@endif</p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <a href="{{ route('surat.pdf', $p->surat) }}" class="inline-flex h-11 items-center gap-2 rounded-lg bg-white px-4 font-label-lg text-label-lg font-semibold text-primary hover:bg-surface"><x-ikon name="download" class="text-[20px]" />Unduh Surat (PDF)</a>
                @if ($p->surat->pakaiQr())<a href="{{ route('verifikasi.show', $p->surat->qr_token) }}" target="_blank" class="inline-flex h-11 items-center gap-2 rounded-lg bg-white/15 px-4 font-label-lg text-label-lg font-semibold text-white hover:bg-white/25"><x-ikon name="qr_code_2" class="text-[20px]" />Verifikasi QR</a>@endif
            </div>
        </section>
    @elseif ($p->status === S::Ditolak)
        <section class="flex gap-3 rounded-xl border border-status-ditolak-border bg-status-ditolak-bg p-space-lg text-status-ditolak-text">
            <x-ikon name="cancel" filled class="text-[28px]" />
            <div><h2 class="font-headline-md text-headline-md">Pengajuan Ditolak</h2><p class="mt-1 font-body-md text-body-md"><strong>Alasan:</strong> {{ $p->alasan_tolak }}</p>
                @if ($milikSaya)<p class="mt-2 font-body-sm text-body-sm">Perbaiki berkas/data sesuai alasan di atas, lalu <a class="font-semibold underline" href="{{ route('layanan.ajukan', $p->jenis) }}">ajukan kembali</a>.</p>@endif</div>
        </section>
    @endif

    {{-- Panel tindakan petugas --}}
    @if ($bolehV || $bolehParaf || $bolehSelesai)
        <x-kartu judul="Tindakan Anda" ikon="gavel" class="border border-secondary-container/60">
            <div class="flex flex-wrap items-center gap-3">
                @if ($bolehV)
                    <form method="post" action="{{ route('pengajuan.verifikasi', $p) }}" class="flex flex-1 flex-wrap items-center gap-3">@csrf
                        <input name="nomor_surat" value="{{ old('nomor_surat') }}" placeholder="Nomor urut surat, mis. 009{{ \App\Support\NomorManual::wajib() ? ' (wajib)' : ' (opsional)' }}" inputmode="numeric" @required(\App\Support\NomorManual::wajib()) class="h-10 min-w-56 flex-1 rounded-lg border-outline-variant font-body-md text-body-md focus:ring-2 focus:ring-primary">
                        <input name="catatan" placeholder="Catatan verifikasi (opsional)" class="h-10 min-w-56 flex-1 rounded-lg border-outline-variant font-body-md text-body-md focus:ring-2 focus:ring-primary">
                        <x-tombol type="submit" ikon="task_alt">Verifikasi & Teruskan</x-tombol>
                    </form>
                @endif
                @if ($bolehParaf)<x-tombol :href="route('persetujuan.show', $p)" ikon="draw">Buka Halaman Persetujuan</x-tombol>@endif
                @if ($bolehSelesai)
                    <form method="post" action="{{ route('pengajuan.selesai', $p) }}">@csrf<x-tombol type="submit" ikon="check_circle">Tandai Selesai</x-tombol></form>
                @endif
                @if ($bolehTolak && ! $bolehParaf)<x-tombol varian="bahaya" ikon="close" x-on:click="$dispatch('buka-modal','tolak')">Tolak Pengajuan</x-tombol>@endif
            </div>
        </x-kartu>
        <x-modal nama="tolak" judul="Tolak Pengajuan">
            <form method="post" action="{{ route('pengajuan.tolak', $p) }}" class="space-y-4">@csrf
                <x-textarea label="Alasan penolakan" name="alasan" wajib rows="4" bantuan="Alasan akan dikirim ke pemohon." />
                <div class="flex justify-end gap-2"><x-tombol varian="sekunder" x-on:click="$dispatch('tutup-modal')">Batal</x-tombol><x-tombol type="submit" varian="bahaya">Tolak Pengajuan</x-tombol></div>
            </form>
        </x-modal>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Rincian --}}
        <div class="space-y-6 lg:col-span-2">
            <x-kartu judul="Rincian Permohonan" ikon="description">
                <x-slot:aksi><x-lencana-status :status="$p->status" /></x-slot:aksi>
                <dl class="space-y-4">
                    <div><dt class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Nomor Registrasi</dt><dd class="mt-0.5 font-label-lg text-label-lg font-bold tabular text-primary">{{ $p->kode }}</dd></div>
                    <div><dt class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Jenis Surat</dt><dd class="mt-0.5 font-label-lg text-label-lg">{{ $p->jenis->nama }}</dd></div>
                    <div class="rounded-lg bg-surface-container-low p-3">
                        <dt class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Identitas Pemohon</dt>
                        <dd class="mt-2 flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-fixed font-label-md text-label-md text-primary">{{ $p->pemohon->inisial() }}</span>
                            <span><span class="block font-label-lg text-label-lg">{{ $p->pemohon->nama }}</span><span class="block font-body-sm text-body-sm text-on-surface-variant tabular">NPM: {{ $p->pemohon->nomor_induk }}</span><span class="block font-label-sm text-label-sm text-primary">{{ $p->pemohon->prodi?->nama }} ({{ $p->pemohon->prodi?->jenjang }})</span></span>
                        </dd>
                    </div>
                    @foreach ($p->jenis->field_formulir as $f)
                        @php $v = $p->data_isian[$f['nama']] ?? ''; @endphp
                        @if ($v !== '')
                            <div><dt class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">{{ $f['label'] }}</dt><dd class="mt-0.5 font-body-md text-body-md">{{ ($f['tipe'] === 'tanggal') ? \Illuminate\Support\Carbon::parse($v)->translatedFormat('j F Y') : $v }}</dd></div>
                        @endif
                    @endforeach
                    <div><dt class="font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Waktu Registrasi Awal</dt><dd class="mt-0.5 font-body-md text-body-md">{{ $p->created_at->translatedFormat('j F Y, H:i') }} WITA</dd></div>
                    @if ($p->lampiran->isNotEmpty())
                        <div><dt class="mb-1.5 font-label-sm text-label-sm uppercase tracking-wider text-on-surface-variant">Lampiran Berkas Pemohon</dt>
                            <dd class="space-y-1.5">@foreach ($p->lampiran as $l)
                                <a href="{{ route('lampiran.unduh', $l) }}" target="_blank" class="flex items-center gap-2 rounded-lg bg-surface-container-low px-3 py-2 hover:bg-surface-container"><x-ikon name="attach_file" class="text-[18px] text-on-surface-variant" /><span class="min-w-0 flex-1 truncate font-body-sm text-body-sm" title="{{ $l->label }}">{{ $l->nama_asli }}</span><span class="font-label-sm text-label-sm text-on-surface-variant">{{ $l->ukuranTerbaca() }}</span></a>
                            @endforeach</dd></div>
                    @endif
                </dl>
            </x-kartu>
            @if ($terbit && $p->surat->pakaiQr())
                <x-kartu padding="p-space-md">
                    <div class="flex items-center gap-4">
                        <div class="rounded-lg border border-outline-variant/60 bg-white p-1.5">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(76)->margin(0)->errorCorrection('M')->generate($p->surat->urlVerifikasi()) !!}</div>
                        <div><p class="font-label-sm text-label-sm font-bold uppercase tracking-wider text-secondary">Tanda Tangan Elektronik</p>
                            <p class="font-body-sm text-body-sm">Ditandatangani oleh {{ $p->surat->penandatangan_jabatan }} dengan kunci kriptografi Fakultas Teknik UM Buton.</p>
                            <p class="mt-1 font-label-sm text-label-sm tabular text-on-surface-variant">ID: {{ \Illuminate\Support\Str::upper(substr($p->surat->qr_token, 0, 12)) }}</p></div>
                    </div>
                </x-kartu>
            @endif
        </div>

        {{-- Nomor surat (diisi TU) sebelum terbit --}}
        @if ($p->surat && $user->adalahAdmin() && ! in_array($p->surat->status, ['ditandatangani', 'batal'], true))
            <div class="lg:col-span-5">
                <x-kartu judul="Nomor Surat" deskripsi="{{ \App\Support\NomorManual::wajib() ? 'Penomoran manual: nomor urut wajib diisi sebelum Dekan menandatangani.' : 'Ketik nomor urut depan; kosongkan untuk nomor otomatis.' }}" ikon="numbers">
                    <form method="post" action="{{ route('pengajuan.nomor', $p) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">@csrf
                        <x-input label="Nomor urut surat" name="nomor_manual" :value="old('nomor_manual', $p->surat->nomor_manual)" placeholder="009" inputmode="numeric" class="flex-1" :bantuan="\App\Support\NomorManual::lengkap($p->surat) ? 'Nomor lengkap: '.\App\Support\NomorManual::lengkap($p->surat) : 'Contoh: '.\App\Support\NomorManual::contoh()" />
                        <x-tombol type="submit" varian="sekunder" ikon="save">Simpan Nomor</x-tombol>
                    </form>
                </x-kartu>
            </div>
        @endif

        {{-- Pratinjau web surat (bisa langsung dicetak) --}}
        @if ($p->surat && ($p->user_id !== $user->id || in_array($p->surat->status, ['ditandatangani', 'batal'], true)))
            @php $dokumenWeb = app(\App\Services\TandaTanganService::class)->dokumenWeb($p->surat); @endphp
            <div class="lg:col-span-5">
                <x-kartu judul="Pratinjau Surat" deskripsi="Tampilan A4 yang sama dengan PDF; dapat langsung dicetak." ikon="description">
                    <x-cetak-surat :dokumen="$dokumenWeb" />
                    <div class="rounded-xl bg-surface-container-high/60 p-3 sm:p-6">
                        <style>@include('pdf._gaya')</style>
                        <div id="kertas-cetak" class="relative mx-auto min-h-[1123px] w-full max-w-[794px] aspect-[210/297] bg-white px-[8%] pb-20 pt-12 shadow-md">@include('pdf._surat', $dokumenWeb)</div>
                    </div>
                </x-kartu>
            </div>
        @endif

        {{-- Garis waktu --}}
        <div class="lg:col-span-3">
            <x-kartu judul="Alur Verifikasi Dokumen Berjenjang" deskripsi="Rekam jejak pemeriksaan setiap pejabat berwenang secara real-time.">
                <x-slot:aksi><span class="inline-flex items-center gap-1 rounded-full bg-primary-fixed px-3 py-1 font-label-sm text-label-sm text-primary"><x-ikon name="check_circle" class="text-[14px]" />{{ $selesaiN }} dari {{ count($garis) }} Tahapan Terpenuhi</span></x-slot:aksi>
                <x-garis-waktu>
                    @foreach ($garis as $k => $t)
                        <x-garis-waktu.butir :status="$t['status']">
                            <div class="rounded-lg {{ $t['status'] === 'sekarang' ? 'bg-secondary-fixed/40' : ($t['status'] === 'ditolak' ? 'bg-status-ditolak-bg' : 'bg-surface-container-low') }} p-3">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                    <p class="font-label-lg text-label-lg"><span class="mr-1 rounded bg-surface-container-high px-1.5 py-0.5 font-label-sm text-label-sm">Tahap {{ $k + 1 }}</span>{{ $t['judul'] }}</p>
                                    @if ($t['waktu'])<span class="font-label-sm text-label-sm text-on-surface-variant tabular">{{ $t['waktu']->translatedFormat('j M Y, H:i') }}</span>@endif
                                </div>
                                @if ($t['oleh'])<p class="mt-1 font-body-sm text-body-sm text-on-surface-variant">{{ $t['oleh'] }}</p>@endif
                                @if ($t['catatan'])<p class="mt-2 rounded bg-surface-container-lowest p-2 font-body-sm text-body-sm"><strong>Catatan:</strong> {{ $t['catatan'] }}</p>@endif
                                @if ($t['status'] === 'sekarang')<p class="mt-1 font-body-sm text-body-sm font-medium text-secondary">Sedang menunggu tindakan: {{ $p->tahapBerjalan() }}</p>@endif
                            </div>
                        </x-garis-waktu.butir>
                    @endforeach
                </x-garis-waktu>
            </x-kartu>
        </div>
    </div>
</div>
</x-layouts::app>
