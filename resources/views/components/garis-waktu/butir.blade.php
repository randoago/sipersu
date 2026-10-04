@props(['status' => 'selesai']) {{-- selesai | sekarang | menunggu | ditolak --}}
<li {{ $attributes->class(['relative pl-10']) }}>
    <span @class([
        'absolute left-0 top-0.5 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-surface-container-lowest',
        'bg-primary-container text-on-primary' => $status === 'selesai',
        'bg-secondary-container text-on-secondary-container' => $status === 'sekarang',
        'bg-outline-variant text-white' => $status === 'menunggu',
        'bg-[#e11d48] text-white' => $status === 'ditolak',
    ])>
        <x-ikon :name="match($status) { 'selesai' => 'check', 'sekarang' => 'edit', 'ditolak' => 'close', default => 'schedule' }" class="text-[14px]" />
    </span>
    {{ $slot }}
</li>
