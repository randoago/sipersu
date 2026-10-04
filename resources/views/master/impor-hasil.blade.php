@php $baru = collect($hasil)->where('aksi', 'dibuat'); $ubah = collect($hasil)->where('aksi', 'diperbarui'); $bersandi = $baru->whereNotNull('password'); @endphp
<x-layouts::app title="Hasil Impor" :cari="false">
<div class="mx-auto max-w-5xl space-y-5" x-data="hasilImpor(@js($hasil))">
    <h1 class="font-headline-xl text-headline-xl">Impor Selesai</h1>
    <x-peringatan jenis="sukses" judul="{{ $baru->count() }} pengguna dibuat, {{ $ubah->count() }} diperbarui" />
    @if ($bersandi->isNotEmpty())
        <x-peringatan jenis="peringatan" judul="Catat kata sandi awal sekarang — hanya ditampilkan SEKALI">Kata sandi di bawah dibuat otomatis dan <strong>tidak dapat dilihat lagi</strong> setelah halaman ini ditutup. Unduh daftarnya, bagikan ke pemilik akun secara aman, dan minta mereka segera menggantinya di <em>Profil → Ganti Kata Sandi</em>. Hapus berkas unduhan setelah dibagikan.</x-peringatan>
        <div><x-tombol varian="aksen" ikon="file_download" x-on:click="unduh()">Unduh Hasil (CSV)</x-tombol></div>
    @endif
    <x-tabel :jumlah="count($hasil)">
        <x-slot:kepala><th class="px-4">NPM/NIDN</th><th>Nama</th><th>Peran</th><th>Hasil</th><th class="pr-4">Kata sandi awal</th></x-slot:kepala>
        @foreach ($hasil as $h)
            <tr><td class="px-4 py-2.5 font-body-sm text-body-sm tabular">{{ $h['nomor_induk'] }}</td><td class="font-body-md text-body-md">{{ $h['nama'] }}</td><td class="font-body-sm text-body-sm">{{ $h['peran'] }}</td>
                <td><span class="rounded-full px-2.5 py-1 font-label-md text-label-md {{ $h['aksi'] === 'dibuat' ? 'bg-status-selesai-bg text-status-selesai-text' : 'bg-status-diverifikasi-bg text-status-diverifikasi-text' }}">{{ ucfirst($h['aksi']) }}</span></td>
                <td class="pr-4 font-mono text-[13px] font-semibold">{{ $h['password'] ?? '—' }}</td></tr>
        @endforeach
    </x-tabel>
    <x-tombol :href="route('master.daftar', 'pengguna')" ikon="groups">Ke Daftar Pengguna</x-tombol>
</div>
@push('skrip')
<script>
function hasilImpor(baris) {
    return {
        unduh() {
            const esc = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
            const csv = '\uFEFFnomor_induk,nama,peran,aksi,kata_sandi_awal\r\n' + baris.map(b => [b.nomor_induk, b.nama, b.peran, b.aksi, b.password].map(esc).join(',')).join('\r\n');
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
            a.download = 'hasil-impor-pengguna.csv';
            a.click();
        },
    };
}
</script>
@endpush
</x-layouts::app>