@props(['status'])
@php
    $s = $status instanceof \App\Enums\StatusPengajuan ? $status : \App\Enums\StatusPengajuan::from($status);
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2.5 py-1 font-label-md text-label-md font-semibold whitespace-nowrap', $s->kelas()]) }}>{{ $s->label() }}</span>
