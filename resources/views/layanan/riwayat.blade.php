<x-layouts::app title="Riwayat Surat" :cari="false">
<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="font-headline-xl text-headline-xl text-on-surface">Riwayat Surat</h1><p class="mt-1 font-body-md text-body-md text-on-surface-variant">Seluruh pengajuan surat yang pernah Anda buat.</p></div>
        <form method="get" class="flex gap-2">
            <x-select name="status" onchange="this.form.submit()" class="w-48">
                <option value="">Semua status</option>
                @foreach (\App\Enums\StatusPengajuan::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>@endforeach
            </x-select>
        </form>
    </div>
    <div class="grid gap-3 lg:grid-cols-2">
        @forelse ($daftar as $p)<x-kartu-pengajuan :p="$p" />@empty
            <div class="rounded-xl bg-surface-container-lowest p-8 text-center text-on-surface-variant shadow-sm lg:col-span-2">Tidak ada pengajuan.</div>
        @endforelse
    </div>
    {{ $daftar->links() }}
</div>
</x-layouts::app>
