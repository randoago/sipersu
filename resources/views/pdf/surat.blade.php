<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><title>{{ $nomor ?? 'Surat' }}</title>
<style>
    @page { size: A4 portrait; margin: 1.2cm 2.2cm 1.2cm 2.4cm; }
    body { margin: 0; }
    @include('pdf._gaya')
</style></head><body>
@include('pdf._surat', $dokumen)
@if (! empty($dokumen['riwayat']))@include('pdf._riwayat', $dokumen + ['nomor' => $dokumen['nomor']])@endif
</body></html>
