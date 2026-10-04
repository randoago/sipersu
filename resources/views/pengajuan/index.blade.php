<x-layouts::app title="Layanan Mahasiswa" :cari="false">
<div class="mx-auto max-w-7xl space-y-6">
    <div><h1 class="font-headline-xl text-headline-xl">Layanan Mahasiswa</h1><p class="mt-1 font-body-md text-body-md text-on-surface-variant">Daftar pengajuan surat mahasiswa dan statusnya.</p></div>
    <form method="get" class="flex flex-col gap-2 sm:flex-row">
        <div class="relative flex-1"><x-ikon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[20px] text-outline" />
            <input name="q" value="{{ $q }}" placeholder="Cari nama / NIM / nomor pengajuan…" class="h-10 w-full rounded-lg border-outline-variant bg-surface-container-lowest pl-10 font-body-md text-body-md focus:ring-2 focus:ring-primary"></div>
        <x-select name="status" class="sm:w-52" onchange="this.form.submit()"><option value="">Semua status</option>@foreach (\App\Enums\StatusPengajuan::cases() as $s)<option value="{{ $s->value }}" @selected($status === $s->value)>{{ $s->label() }}</option>@endforeach</x-select>
        <x-tombol type="submit" varian="sekunder" ikon="filter_list">Terapkan</x-tombol>
    </form>
    <x-tabel :jumlah="$daftar->count()" kosong="Tidak ada pengajuan yang cocok.">
        <x-slot:kepala><th class="px-4">Jenis & Nomor</th><th>Pemohon</th><th>Diajukan</th><th>Status</th><th class="px-4 text-right">Aksi</th></x-slot:kepala>
        @foreach ($daftar as $p)
            @php $perlu = $alur->bolehVerifikasi($p, auth()->user()) || $alur->bolehParaf($p, auth()->user()) || $alur->bolehTandatangan($p, auth()->user()); @endphp
            <tr>
                <td class="px-4 py-3"><p class="font-label-lg text-label-lg">{{ $p->jenis->nama }}</p><p class="font-body-sm text-body-sm text-on-surface-variant tabular">{{ $p->kode }}</p></td>
                <td><p class="font-label-lg text-label-lg">{{ $p->pemohon->nama }}</p><p class="font-body-sm text-body-sm text-on-surface-variant tabular">{{ $p->pemohon->nomor_induk }} • {{ $p->pemohon->prodi?->nama }}</p></td>
                <td class="font-body-sm text-body-sm tabular">{{ $p->created_at->translatedFormat('j M Y, H:i') }}</td>
                <td><x-lencana-status :status="$p->status" /></td>
                <td class="px-4 text-right"><x-tombol :href="route('pengajuan.show', $p)" :varian="$perlu ? 'utama' : 'sekunder'" ukuran="sm">{{ $perlu ? 'Periksa / Proses' : 'Detail' }}</x-tombol></td>
            </tr>
        @endforeach
        <x-slot:kaki>{{ $daftar->links() }}</x-slot:kaki>
    </x-tabel>
</div>
</x-layouts::app>
