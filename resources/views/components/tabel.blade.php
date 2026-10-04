{{-- Pembungkus tabel data. Isi <thead>/<tbody> lewat slot; gunakan $kosong untuk keadaan kosong. --}}
@props(['kepala' => null, 'kosong' => 'Belum ada data.', 'jumlah' => null])
<div {{ $attributes->class(['overflow-hidden rounded-lg border border-outline-variant/60 bg-surface-container-lowest']) }}>
    <div class="overflow-x-auto scroll-tipis">
        <table class="w-full min-w-[720px] text-left font-body-md text-body-md">
            @if ($kepala)
                <thead class="bg-surface-container-low text-on-surface-variant">
                    <tr class="h-11 border-b border-outline-variant/60 font-label-md text-label-md uppercase tracking-wider">{{ $kepala }}</tr>
                </thead>
            @endif
            <tbody class="divide-y divide-surface-container [&>tr]:min-h-[52px] [&>tr:hover]:bg-surface-container-low/60">{{ $slot }}</tbody>
        </table>
    </div>
    @if ($jumlah === 0)
        <div class="flex flex-col items-center gap-space-sm px-space-lg py-10 text-on-surface-variant">
            <x-ikon name="inbox" class="text-[36px] text-outline" />
            <p class="font-body-md text-body-md">{{ $kosong }}</p>
        </div>
    @endif
    @isset($kaki)<div class="border-t border-outline-variant/60 px-space-lg py-space-md">{{ $kaki }}</div>@endisset
</div>
