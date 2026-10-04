<x-layouts::app title="Notifikasi" :cari="false">
<div class="mx-auto max-w-3xl space-y-5">
    <div class="flex items-center justify-between"><h1 class="font-headline-xl text-headline-xl">Notifikasi</h1>
        <form method="post" action="{{ route('notifikasi.baca-semua') }}">@csrf<x-tombol type="submit" varian="sekunder" ukuran="sm" ikon="done_all">Tandai semua dibaca</x-tombol></form></div>
    <div class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-soft">
        @forelse ($daftar as $n)
            <a href="{{ route('notifikasi.buka', $n) }}" @class(['flex gap-3 border-b border-surface-container px-space-md py-3 last:border-0 hover:bg-surface-container-low', 'bg-primary-fixed/20' => ! $n->dibaca_pada])>
                <span @class(['mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full', 'bg-error-container text-error' => $n->jenis === 'bahaya', 'bg-secondary-fixed text-secondary' => in_array($n->jenis, ['tugas', 'peringatan']), 'bg-primary-fixed text-primary' => ! in_array($n->jenis, ['bahaya', 'tugas', 'peringatan'])])><x-ikon :name="$n->jenis === 'bahaya' ? 'error' : ($n->jenis === 'tugas' ? 'assignment_turned_in' : 'notifications')" class="text-[20px]" /></span>
                <div class="min-w-0 flex-1"><p class="font-label-lg text-label-lg {{ $n->dibaca_pada ? '' : 'font-bold' }}">{{ $n->judul }}</p>@if ($n->isi)<p class="font-body-sm text-body-sm text-on-surface-variant">{{ $n->isi }}</p>@endif<p class="mt-0.5 font-label-sm text-label-sm text-outline">{{ $n->created_at->diffForHumans() }}</p></div>
            </a>
        @empty<p class="p-8 text-center text-on-surface-variant">Belum ada notifikasi.</p>@endforelse
    </div>
    {{ $daftar->links() }}
</div>
</x-layouts::app>
