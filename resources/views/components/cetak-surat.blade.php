{{-- Bilah pratinjau web: tombol Cetak + Tambahkan/Tanpa Tanda Tangan. Kertas yang dicetak: elemen ber-id "kertas-cetak". --}}
@props(['dokumen'])
@php
    $adaTtd = ! empty($dokumen['spesimen']) || ! empty($dokumen['spesimenWeb']);
    $awal = ! empty($dokumen['spesimen']) ? 'true' : 'false';
@endphp
<div x-data="{ ttd: {{ $awal }} }" {{ $attributes->class(['mb-3 flex flex-wrap items-center justify-between gap-2']) }}>
    <p class="flex min-w-0 flex-1 items-start gap-1.5 font-body-sm text-body-sm text-on-surface-variant"><x-ikon name="info" class="mt-0.5 shrink-0 text-[16px]" /><span>Pratinjau web sama dengan PDF. Saat mencetak, matikan <em>Headers and footers</em> dan atur margin <em>None</em>.</span></p>
    <div class="flex flex-wrap gap-2">
        @if ($adaTtd)
            <x-tombol varian="sekunder" ikon="draw" x-on:click="ttd = !ttd; document.querySelectorAll('.ttd-spesimen').forEach(e => e.style.display = ttd ? '' : 'none'); document.querySelectorAll('.ttd-kosong').forEach(e => e.style.display = ttd ? 'none' : '')"><span x-text="ttd ? 'Tanpa Tanda Tangan' : 'Tambahkan Tanda Tangan'">{{ $awal === 'true' ? 'Tanpa Tanda Tangan' : 'Tambahkan Tanda Tangan' }}</span></x-tombol>
        @endif
        <x-tombol ikon="print" x-on:click="window.print()">Cetak</x-tombol>
    </div>
</div>
<style>
    @media print {
        body * { visibility: hidden !important; }
        #kertas-cetak, #kertas-cetak * { visibility: visible !important; }
        #kertas-cetak { position: fixed !important; left: 0; top: 0; width: 210mm !important; max-width: none !important; margin: 0 !important; box-shadow: none !important; min-height: 0 !important; aspect-ratio: auto !important; }
        #kertas-cetak .qr-kosong { visibility: hidden !important; }
        @page { size: A4; margin: 0; }
    }
</style>
