<x-layouts::app title="Backup" :cari="false">
<div class="mx-auto max-w-5xl">
    <h1 class="mb-1 font-headline-xl text-headline-xl">Pengaturan</h1>
    <p class="mb-5 font-body-md text-body-md text-on-surface-variant">Cadangkan dan pulihkan data SIPERSU (aturan 3-2-1).</p>
    @include('pengaturan._tab')

    @if ($peringatan)
        <x-peringatan jenis="darurat" judul="Perhatian: backup bermasalah" class="mb-5"><ul class="list-disc pl-5">@foreach ($peringatan as $m)<li>{{ $m }}</li>@endforeach</ul></x-peringatan>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <x-kartu judul="Status Backup Terakhir" ikon="cloud_done" class="lg:col-span-2">
            @if ($terakhir)
                <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                    <div><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">Waktu</p><p class="font-label-lg text-label-lg">{{ $terakhir->created_at->translatedFormat('j F Y, H:i') }} WITA</p></div>
                    <div><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">Ukuran</p><p class="font-label-lg text-label-lg tabular">{{ number_format($terakhir->ukuran / 1048576, 2, ',', '.') }} MB</p></div>
                    <div><p class="font-label-sm text-label-sm uppercase text-on-surface-variant">Jenis</p><p class="font-label-lg text-label-lg">{{ ucfirst($terakhir->jenis) }}</p></div>
                </div>
            @else
                <p class="text-on-surface-variant">Belum ada backup yang berhasil.</p>
            @endif
            <dl class="mt-4 space-y-1.5 rounded-lg bg-surface-container-low p-3 font-body-sm text-body-sm">
                <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Folder backup harian</dt><dd class="text-right font-semibold">{{ $tujuan ?: '— belum diatur (BACKUP_LOCAL_PATH)' }} <span class="{{ $tujuanAda ? 'text-primary' : 'text-error' }}">{{ $tujuan ? ($tujuanAda ? '● tersedia' : '● TIDAK tersedia') : '' }}</span></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Kata sandi enkripsi (BACKUP_PASSWORD)</dt><dd class="font-semibold {{ $passwordAda ? 'text-primary' : 'text-error' }}">{{ $passwordAda ? 'Terisi' : 'BELUM diisi' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-on-surface-variant">Jadwal otomatis</dt><dd class="font-semibold">Harian 16.30 • Mingguan Jumat → Google Drive (rclone)</dd></div>
            </dl>
        </x-kartu>
        <x-kartu judul="Backup Sekarang" ikon="backup">
            <p class="mb-3 font-body-sm text-body-sm text-on-surface-variant">Membuat ZIP terenkripsi (database, berkas, kunci, .env). Disimpan di folder internal aplikasi dan dapat diunduh.</p>
            <form method="post" action="{{ route('backup.jalankan') }}" x-data="{ proses: false }" @submit="proses = true">@csrf
                <x-tombol type="submit" ukuran="lg" ikon="backup" class="w-full" x-bind:disabled="proses"><span x-text="proses ? 'Membuat backup…' : 'Backup Sekarang'"></span></x-tombol>
            </form>
        </x-kartu>
    </div>

    @if ($perluUji)
        <x-peringatan jenis="peringatan" judul="Pengingat bulanan" class="mt-5">Lakukan <strong>uji pemulihan backup</strong> bulan ini: <code class="rounded bg-black/5 px-1">php artisan backup:restore &lt;berkas.zip&gt; --uji-saja</code>. Backup yang tidak pernah diuji belum tentu bisa dipulihkan.</x-peringatan>
    @endif

    <h2 class="mb-3 mt-8 font-headline-md text-headline-md">Riwayat Backup</h2>
    <x-tabel :jumlah="$riwayat->count()" kosong="Belum ada riwayat backup.">
        <x-slot:kepala><th class="px-4">Tanggal</th><th>Jenis</th><th>Berkas</th><th>Ukuran</th><th>Lokasi</th><th>Status</th><th class="px-4 text-right">Aksi</th></x-slot:kepala>
        @foreach ($riwayat as $r)
            <tr>
                <td class="px-4 py-3 font-body-sm text-body-sm tabular">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                <td class="font-body-sm text-body-sm">{{ ucfirst($r->jenis) }}</td>
                <td class="max-w-[220px] truncate font-body-sm text-body-sm tabular" title="{{ $r->nama_berkas }}">{{ $r->nama_berkas }}</td>
                <td class="font-body-sm text-body-sm tabular">{{ $r->ukuran ? number_format($r->ukuran / 1048576, 2, ',', '.').' MB' : '-' }}</td>
                <td class="max-w-[200px] truncate font-body-sm text-body-sm text-on-surface-variant" title="{{ $r->lokasi }}">{{ $r->lokasi }}</td>
                <td>@if ($r->status === 'sukses')<span class="rounded-full bg-status-selesai-bg px-2.5 py-1 font-label-md text-label-md text-status-selesai-text">Sukses</span>@else<span class="rounded-full bg-status-ditolak-bg px-2.5 py-1 font-label-md text-label-md text-status-ditolak-text" title="{{ $r->pesan }}">Gagal</span>@endif</td>
                <td class="px-4 text-right">@if ($r->status === 'sukses' && ! str_starts_with($r->lokasi, 'Google Drive'))<x-tombol :href="route('backup.unduh', $r)" varian="sekunder" ukuran="sm" ikon="download">Unduh</x-tombol>@endif</td>
            </tr>
        @endforeach
    </x-tabel>
</div>
</x-layouts::app>
