@props(['p'])
@php
    use App\Enums\StatusPengajuan as S;
    $berjalan = ! in_array($p->status, [S::Selesai, S::Ditolak, S::Ditandatangani], true);
    $terbit = in_array($p->status, [S::Ditandatangani, S::Selesai], true) && $p->surat?->file_pdf;
    $pertama = collect($p->jenis->field_formulir)->first(fn ($f) => ($f['tipe'] ?? '') !== 'tanggal');
    $ringkas = $pertama ? ($p->data_isian[$pertama['nama']] ?? null) : null;
@endphp
<article {{ $attributes->class(['space-y-3 rounded-xl bg-surface-container-lowest p-space-md shadow-sm']) }}>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <span class="font-mono-data text-[11px] font-medium text-on-surface-variant tabular">{{ $p->kode }}</span>
            <h3 class="mt-0.5 font-headline-sm text-headline-sm font-bold text-on-surface">{{ $p->jenis->nama }}</h3>
            <p class="mt-0.5 flex items-center gap-1 font-body-sm text-body-sm text-on-surface-variant"><x-ikon name="calendar_today" class="text-[14px]" />{{ $p->created_at->translatedFormat('j M Y, H:i') }} WITA</p>
        </div>
        <x-lencana-status :status="$p->status" class="shrink-0" />
    </div>

    @if ($p->status === S::Ditolak)
        <div class="rounded-lg bg-status-ditolak-bg p-2.5 font-body-sm text-body-sm text-status-ditolak-text"><strong>Alasan:</strong> {{ $p->alasan_tolak }}</div>
    @elseif ($berjalan)
        <div class="space-y-2 rounded-lg bg-surface-container-low p-2.5">
            @if ($ringkas)
                <div class="flex items-center justify-between font-body-sm text-body-sm text-on-surface-variant"><span>Keperluan:</span><span class="ml-2 truncate text-right font-semibold text-on-surface">{{ \Illuminate\Support\Str::limit($ringkas, 40) }}</span></div>
            @endif
            <div class="space-y-1">
                <div class="flex items-center justify-between font-label-sm text-label-sm text-on-surface-variant"><span>{{ $p->tahapBerjalan() }}</span><span class="font-semibold text-primary">Langkah {{ $p->langkahKe() }} dari {{ $p->totalLangkah() }}</span></div>
                <div class="h-1.5 overflow-hidden rounded-full bg-surface-container-high"><div class="h-full rounded-full bg-primary" style="width: {{ $p->persen() }}%"></div></div>
            </div>
        </div>
    @elseif ($terbit)
        <div class="flex items-center justify-between rounded-lg bg-surface-container-low p-2.5 font-body-sm text-body-sm"><span class="text-on-surface-variant">Nomor Surat:</span><span class="font-semibold tabular">{{ $p->surat->nomor }}</span></div>
    @endif

    <div class="flex gap-2">
        @if ($terbit)
            <a href="{{ route('surat.pdf', $p->surat) }}" class="flex h-9 flex-1 items-center justify-center gap-1.5 rounded-lg bg-primary font-label-md text-label-md font-semibold text-on-primary hover:bg-primary-container"><x-ikon name="download" class="text-[18px]" />Unduh Surat (PDF)</a>
            @if ($p->surat->pakaiQr())<a href="{{ route('verifikasi.show', $p->surat->qr_token) }}" target="_blank" class="flex h-9 items-center gap-1 rounded-lg bg-secondary-container px-3 font-label-md text-label-md font-semibold text-on-secondary-container hover:bg-secondary-fixed"><x-ikon name="qr_code_2" class="text-[18px]" />QR TTD</a>@else<span class="flex h-9 items-center gap-1 rounded-lg bg-surface-container px-3 font-label-md text-label-md text-on-surface-variant" title="Cetak, tanda tangan basah, dan cap"><x-ikon name="print" class="text-[18px]" />Cetak</span>@endif
        @else
            <a href="{{ route('pengajuan.show', $p) }}" class="flex h-9 flex-1 items-center justify-center gap-1.5 rounded-lg {{ $berjalan ? 'bg-primary text-on-primary hover:bg-primary-container' : 'bg-surface-container text-on-surface hover:bg-surface-container-high' }} font-label-md text-label-md font-semibold"><x-ikon name="{{ $berjalan ? 'query_stats' : 'visibility' }}" class="text-[18px]" />{{ $berjalan ? 'Lacak Status' : 'Lihat Detail' }}</a>
        @endif
        @if ($terbit || $berjalan)<a href="{{ route('pengajuan.show', $p) }}" class="flex h-9 items-center rounded-lg bg-surface-container px-3 font-label-md text-label-md font-semibold text-on-surface-variant hover:bg-surface-container-high">Detail</a>@endif
    </div>
</article>
