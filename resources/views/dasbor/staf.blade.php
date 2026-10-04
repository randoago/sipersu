@php $u = auth()->user(); $hariIni = now()->translatedFormat('l, j F Y'); @endphp
<x-layouts::app title="Dasbor">
<div class="space-y-6" x-data="{ saring: 'semua', q: '' }">
    @if ($peringatanBackup)
        <x-peringatan jenis="darurat" judul="Backup bermasalah — data belum aman">
            <ul class="list-disc pl-5">@foreach ($peringatanBackup as $m)<li>{{ $m }}</li>@endforeach</ul>
            <a href="{{ route('pengaturan.backup') }}" class="mt-1 inline-block font-semibold underline">Buka pengaturan backup</a>
        </x-peringatan>
    @elseif ($ujiPemulihan)
        <x-peringatan jenis="peringatan" judul="Pengingat bulanan">Lakukan <strong>uji pemulihan backup</strong> (<a class="font-semibold underline" href="{{ route('pengaturan.backup') }}">lihat caranya</a>).</x-peringatan>
    @endif

    {{-- Banner sapaan --}}
    <section class="flex flex-col gap-4 rounded-lg bg-surface-container-lowest p-space-lg shadow-soft lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-1">
            <div class="flex items-center gap-2"><span class="inline-block h-2.5 w-2.5 animate-pulse rounded-full bg-primary"></span>
                <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface lg:font-headline-lg lg:text-headline-lg">Selamat datang kembali, {{ $u->nama }}!</h1></div>
            <p class="flex flex-wrap items-center gap-x-1.5 font-body-md text-body-md text-on-surface-variant"><x-ikon name="calendar_today" class="text-[16px] text-secondary" /><span>{{ $hariIni }}</span><span class="text-outline-variant">•</span><span>Ringkasan aktivitas persuratan Fakultas Teknik UM Buton hari ini.</span></p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-3">
            @if (Route::has('persetujuan.index') && $u->hasAnyRole(['dekan', 'wakil_dekan', 'kaprodi']))<x-tombol :href="route('persetujuan.index')" ikon="draw">Persetujuan & TTD</x-tombol>@endif
            @if (Route::has('pengajuan.index'))<x-tombol :href="route('pengajuan.index')" varian="lembut" ikon="school">Layanan Mahasiswa</x-tombol>@endif
        </div>
    </section>

    {{-- Kartu statistik --}}
    <section class="grid grid-cols-1 gap-gutter sm:grid-cols-2 xl:grid-cols-4">
        @if (Route::has('surat-masuk.index'))<x-kartu-statistik label="Surat Masuk Bulan Ini" :nilai="$stat['masuk_bulan_ini']" ikon="move_to_inbox" warna="hijau" :href="route('surat-masuk.index')" :catatan="$stat['agenda_terakhir'] ? 'Agenda terakhir: '.$stat['agenda_terakhir'] : 'Belum ada surat masuk'" catatan-ikon="tag" />@endif
        <x-kartu-statistik label="Surat Keluar Bulan Ini" :nilai="$stat['terbit_bulan_ini']" ikon="outbox" warna="biru" :catatan="$stat['terakhir'] ? 'Terakhir: '.$stat['terakhir'] : 'Belum ada surat terbit'" catatan-ikon="tag" />
        <x-kartu-statistik label="Perlu Tindakan Saya" :nilai="$stat['perlu']" ikon="inbox" warna="emas" :catatan="$stat['perlu'] ? 'Menunggu verifikasi / paraf / TTD Anda' : 'Tidak ada tugas tertunda'" catatan-ikon="priority_high" />
        <x-kartu-statistik label="Menunggu Tanda Tangan" :nilai="$stat['menunggu_ttd']" satuan="surat" ikon="draw" warna="ungu" catatan="Menunggu penandatangan" catatan-ikon="hourglass_top" />
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-kartu class="xl:col-span-2" :judul="'Statistik Surat Tahun '.$tahun" deskripsi="Perbandingan volume surat masuk dan surat keluar per bulan">
            <div class="h-72"><canvas id="grafik" aria-label="Grafik surat per bulan" role="img"></canvas></div>
        </x-kartu>
        <div class="space-y-6">
            <x-kartu judul="Akses Tindakan Cepat" ikon="bolt">
                <div class="space-y-2">
                    @foreach ([['surat-masuk.buat', 'Catat Surat Masuk', 'move_to_inbox'], ['surat-keluar.buat', 'Buat Surat Keluar', 'outbox'], ['pengajuan.index', 'Daftar Pengajuan Mahasiswa', 'school'], ['persetujuan.index', 'Persetujuan & Tanda Tangan', 'draw']] as [$r, $l, $i])
                        @if (Route::has($r))<a href="{{ route($r) }}" class="flex items-center justify-between rounded-lg bg-surface-container-low px-3 py-2.5 font-label-md text-label-md hover:bg-surface-container"><span class="flex items-center gap-2"><x-ikon :name="$i" class="text-[18px] text-primary" />{{ $l }}</span><x-ikon name="arrow_forward" class="text-[16px] text-on-surface-variant" /></a>@endif
                    @endforeach
                </div>
            </x-kartu>
            <x-kartu judul="Aktivitas Terakhir" ikon="history">
                <ul class="space-y-3">
                    @forelse ($aktivitas as $a)
                        <li class="flex gap-2.5"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $a->aksi === 'pengajuan_ditolak' ? 'bg-error' : ($a->aksi === 'ttd' ? 'bg-status-ditandatangani-text' : 'bg-primary') }}"></span>
                            <div class="min-w-0"><p class="font-body-sm text-body-sm">{{ $a->deskripsi }}</p><p class="font-label-sm text-label-sm text-on-surface-variant">{{ $a->user?->nama ?? 'Sistem' }} • {{ $a->created_at->diffForHumans() }}</p></div></li>
                    @empty<li class="font-body-sm text-body-sm text-on-surface-variant">Belum ada aktivitas.</li>@endforelse
                </ul>
            </x-kartu>
        </div>
    </div>

    {{-- Perlu tindakan saya --}}
    <x-kartu padding="p-0">
        <div class="space-y-4 p-space-lg">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div><div class="flex items-center gap-2"><h2 class="font-headline-md text-headline-md">Perlu Tindakan Saya</h2><span class="rounded-full bg-primary-fixed px-2 py-0.5 font-label-sm text-label-sm font-bold text-primary">{{ $perluTindakan->count() }} Berkas</span></div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">Daftar permohonan surat mahasiswa yang menunggu tindakan Anda.</p></div>
                <div class="relative w-full lg:w-72"><x-ikon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline" />
                    <input x-model="q" placeholder="Cari nama / nomor permohonan…" class="h-9 w-full rounded-lg border-outline-variant bg-surface-container-lowest pl-9 font-body-sm text-body-sm focus:ring-2 focus:ring-primary"></div>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['semua' => 'Semua', 'diajukan' => 'Verifikasi', 'diverifikasi' => 'Paraf', 'disetujui' => 'Tanda Tangan'] as $k => $l)
                    <button type="button" @click="saring = '{{ $k }}'" :class="saring === '{{ $k }}' ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high'" class="rounded-full px-3 py-1 font-label-md text-label-md font-semibold transition">{{ $l }} ({{ $k === 'semua' ? $perluTindakan->count() : $perluTindakan->where('filter', $k)->count() }})</button>
                @endforeach
            </div>
        </div>
        <div class="overflow-x-auto scroll-tipis">
            <table class="w-full min-w-[760px] text-left">
                <thead class="bg-surface-container-low font-label-md text-label-md uppercase tracking-wider text-on-surface-variant"><tr class="h-11"><th class="px-space-lg">No</th><th>Jenis & Perihal Surat</th><th>Pemohon</th><th>Tanggal Pengajuan</th><th>Status</th><th class="px-space-lg text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-surface-container">
                    @forelse ($perluTindakan as $i => $t)
                        <tr x-show="(saring === 'semua' || saring === '{{ $t->filter }}') && (q === '' || '{{ addslashes(mb_strtolower($t->ref.' '.$t->nama.' '.$t->sub.' '.$t->judul)) }}'.includes(q.toLowerCase()))" class="hover:bg-surface-container-low/60">
                            <td class="px-space-lg py-3 font-body-sm text-body-sm tabular">{{ $i + 1 }}</td>
                            <td><span class="mb-1 inline-block rounded bg-surface-container px-1.5 py-0.5 font-label-sm text-label-sm text-on-surface-variant">{{ $t->kategori }}</span><p class="font-label-lg text-label-lg">{{ $t->judul }}</p><p class="font-body-sm text-body-sm text-on-surface-variant tabular">{{ str_starts_with($t->ref, 'REG') ? 'Ref: ' : '' }}{{ $t->ref }}</p></td>
                            <td><p class="font-label-lg text-label-lg">{{ $t->nama }}</p><p class="font-body-sm text-body-sm text-on-surface-variant tabular">{{ $t->sub }}</p></td>
                            <td class="font-body-sm text-body-sm tabular">{{ $t->waktu->translatedFormat('j M Y') }}<br><span class="text-on-surface-variant">{{ $t->waktu->format('H:i') }} WITA</span></td>
                            <td><x-lencana-status :status="$t->status" /></td>
                            <td class="px-space-lg text-right"><x-tombol :href="$t->url" ukuran="sm">Periksa / Proses</x-tombol></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-on-surface-variant"><x-ikon name="task_alt" class="text-[32px] text-outline" /><p class="mt-1">Tidak ada berkas yang menunggu tindakan Anda. 🎉</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-kartu>
</div>
@push('skrip')
<script src="{{ asset('js/chart.umd.js') }}"></script>
<script>
    (() => {
        const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        const data = @json($grafik);
        const sekarang = new Date().getMonth();
        const warna = (hex, muda) => bulan.map((_, i) => i === sekarang ? hex : muda);
        new Chart(document.getElementById('grafik'), {
            type: 'bar',
            data: { labels: bulan, datasets: [
                { label: 'Surat Masuk', data: data.masuk, backgroundColor: warna('#00512c', '#a9c4b3'), borderRadius: 4 },
                { label: 'Surat Keluar', data: data.keluar, backgroundColor: warna('#fcc019', '#fbe3a1'), borderRadius: 4 },
            ]},
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef0f6' } }, x: { grid: { display: false } } } },
        });
    })();
</script>
@endpush
</x-layouts::app>
