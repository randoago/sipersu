@props(['jenis' => 'info', 'judul' => null, 'tutup' => false])
@php
    [$kelas, $ikon] = [
        'info' => ['bg-[#eef2ff] text-[#3b4ba0] border-[#c7d2fe]', 'info'],
        'sukses' => ['bg-status-selesai-bg text-status-selesai-text border-status-selesai-border', 'check_circle'],
        'peringatan' => ['bg-status-disetujui-bg text-status-disetujui-text border-status-disetujui-border', 'warning'],
        'bahaya' => ['bg-error-container text-on-error-container border-error/30', 'error'],
        'darurat' => ['bg-error text-on-error border-error', 'report_problem'],
    ][$jenis];
@endphp
<div x-data="{ buka: true }" x-show="buka" role="alert" {{ $attributes->class(["flex items-start gap-space-sm rounded-lg border px-space-md py-space-sm font-body-sm text-body-sm $kelas"]) }}>
    <x-ikon :name="$ikon" class="mt-0.5 text-[20px]" />
    <div class="flex-1 min-w-0">
        @if ($judul)<p class="font-label-lg text-label-lg">{{ $judul }}</p>@endif
        <div>{{ $slot }}</div>
    </div>
    @if ($tutup)<button type="button" @click="buka = false" class="-m-1 rounded p-1 hover:bg-black/5" aria-label="Tutup"><x-ikon name="close" class="text-[18px]" /></button>@endif
</div>
