<x-layouts::app title="Persetujuan & TTD" :cari="false">
<div class="mx-auto max-w-5xl space-y-6">
    <div><h1 class="font-headline-xl text-headline-xl">Persetujuan & Tanda Tangan</h1><p class="mt-1 font-body-md text-body-md text-on-surface-variant">Surat yang menunggu paraf atau tanda tangan Anda.</p></div>
    <x-tabel :jumlah="$daftar->count()" kosong="Tidak ada surat yang menunggu tindakan Anda.">
        <x-slot:kepala><th class="px-4">Pengajuan</th><th>Pemohon</th><th>Tahap</th><th>Diajukan</th><th class="px-4 text-right">Aksi</th></x-slot:kepala>
        @foreach ($daftar as $p)
            <tr>
                <td class="px-4 py-3"><p class="font-label-lg text-label-lg">{{ $p->jenis->nama }}</p><p class="font-body-sm text-body-sm text-on-surface-variant tabular">{{ $p->kode }}</p></td>
                <td><p class="font-label-lg text-label-lg">{{ $p->pemohon->nama }}</p><p class="font-body-sm text-body-sm text-on-surface-variant tabular">{{ $p->pemohon->nomor_induk }} • {{ $p->pemohon->prodi?->nama }}</p></td>
                <td><x-lencana-status :status="$p->status" /></td>
                <td class="font-body-sm text-body-sm tabular">{{ $p->created_at->translatedFormat('j M Y') }}</td>
                <td class="px-4 text-right"><x-tombol :href="route('persetujuan.show', $p)" ukuran="sm">Periksa / Proses</x-tombol></td>
            </tr>
        @endforeach
    </x-tabel>
</div>
</x-layouts::app>
