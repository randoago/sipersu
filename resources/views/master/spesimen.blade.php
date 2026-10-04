<x-layouts::app title="Spesimen Tanda Tangan" :cari="false">
<div class="mx-auto max-w-6xl">
    <h1 class="mb-1 font-headline-xl text-headline-xl">Master Data</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">Spesimen tanda tangan pejabat yang dicetak pada surat ber-QR.</p>
    @include('master._tab')

    <x-peringatan jenis="info" class="mb-5" judul="Cara kerja spesimen">
        Surat <strong>ber-QR</strong> memakai spesimen <strong>tanda tangan + stempel</strong> (bila tidak ada, dipakai tanda tangan saja). Surat <strong>tanpa QR</strong> dibiarkan kosong untuk dibubuhi manual.
        Gambar PNG (latar transparan disarankan) atau JPG, minimal 100×50 piksel, maks. 2 MB, disimpan privat. Pejabat juga dapat mengunggah sendiri di <em>Profil</em>.
    </x-peringatan>

    <div class="space-y-5">
        @forelse ($pejabat as $u)
            <x-kartu :judul="$u->namaLengkap()" ikon="badge" :deskripsi="$u->jabatanAktif->pluck('nama')->implode(' • ') ?: $u->labelPeran()">
                <x-slot:aksi>
                    @if ($u->spesimen_stempel || $u->spesimen_ttd)<span class="rounded-full bg-status-selesai-bg px-2.5 py-1 font-label-md text-label-md text-status-selesai-text">Spesimen tersedia</span>
                    @else<span class="rounded-full bg-status-disetujui-bg px-2.5 py-1 font-label-md text-label-md text-status-disetujui-text">Belum ada spesimen</span>@endif
                </x-slot:aksi>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach (['stempel' => ['Tanda tangan + stempel', 'Dipakai surat ber-QR', $u->spesimen_stempel], 'ttd' => ['Tanda tangan saja (tanpa stempel)', 'Cadangan bila stempel belum ada', $u->spesimen_ttd]] as $jenis => [$judul, $ket, $ada])
                        <div class="space-y-3 rounded-lg border border-outline-variant/60 p-space-md">
                            <div><p class="font-label-lg text-label-lg">{{ $judul }}</p><p class="font-body-sm text-body-sm text-on-surface-variant">{{ $ket }}</p></div>
                            <div class="flex h-28 items-center justify-center rounded-lg border border-dashed border-outline-variant bg-white p-2">
                                @if ($ada)<img src="{{ route('master.spesimen.lihat', ['user' => $u, 'jenis' => $jenis]) }}&v={{ md5($ada) }}" alt="Spesimen {{ $judul }} {{ $u->nama }}" class="max-h-full max-w-full object-contain">@else<span class="font-body-sm text-body-sm text-on-surface-variant">Belum ada</span>@endif
                            </div>
                            <form method="post" action="{{ route('master.spesimen.simpan', $u) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">@csrf
                                <input type="hidden" name="jenis" value="{{ $jenis }}">
                                <input type="file" name="spesimen" accept="image/png,image/jpeg" required class="min-w-0 flex-1 rounded-lg border border-outline-variant bg-surface-container-lowest font-body-sm text-body-sm file:mr-2 file:border-0 file:bg-surface-container-high file:px-3 file:py-1.5 file:font-label-md">
                                <x-tombol type="submit" ukuran="sm" ikon="upload">{{ $ada ? 'Ganti' : 'Unggah' }}</x-tombol>
                            </form>
                            @if ($ada)
                                <form method="post" action="{{ route('master.spesimen.hapus', [$u, $jenis]) }}" onsubmit="return confirm('Hapus spesimen ini?')">@csrf @method('DELETE')<button class="font-label-md text-label-md text-error hover:underline">Hapus spesimen</button></form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-kartu>
        @empty
            <p class="rounded-lg bg-surface-container-lowest p-8 text-center text-on-surface-variant shadow-soft">Belum ada pejabat. Atur pemegang jabatan di Master Data → Jabatan & Pejabat.</p>
        @endforelse
    </div>
    @error('spesimen')<x-peringatan jenis="bahaya" class="mt-5">{{ $message }}</x-peringatan>@enderror
</div>
</x-layouts::app>
