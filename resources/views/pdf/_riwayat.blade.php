{{-- Lembar riwayat dokumen: halaman terakhir PDF surat ber-QR. --}}
<div class="riwayat" style="page-break-before: always;">
    <div class="rw-kop">UNIVERSITAS MUHAMMADIYAH BUTON<br>FAKULTAS TEKNIK</div>
    <div class="rw-judul">LEMBAR RIWAYAT DOKUMEN</div>
    <div class="rw-sub">Lampiran surat nomor {{ $nomor }}</div>

    <table class="rw-info" width="100%" cellspacing="0" cellpadding="0">
        <tr><td width="22%">Nomor surat</td><td width="2%">:</td><td><strong>{{ $nomor }}</strong></td></tr>
        <tr><td>Perihal</td><td>:</td><td>{{ $surat->perihal }}</td></tr>
        <tr><td>Penanda tangan</td><td>:</td><td>{{ $namaPejabat }} — {{ $jabatanNama }}</td></tr>
        <tr><td>Tanggal surat</td><td>:</td><td>{{ $surat->tgl_surat?->translatedFormat('j F Y') }}</td></tr>
    </table>

    <div class="rw-bagian">Riwayat pemrosesan</div>
    <table class="rw-tabel" width="100%" cellspacing="0" cellpadding="0">
        <thead><tr><th width="5%">No</th><th width="30%">Tahap</th><th>Pelaksana</th><th width="26%">Waktu (WITA)</th></tr></thead>
        <tbody>
        @foreach ($riwayat as $i => $r)
            <tr>
                <td align="center">{{ $i + 1 }}</td>
                <td><strong>{{ $r['judul'] }}</strong></td>
                <td>{{ $r['oleh'] ?: '—' }}</td>
                <td>{{ $r['waktu']->translatedFormat('j M Y') }}, {{ $r['waktu']->format('H:i:s') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="rw-bagian">Keaslian dokumen</div>
    <table class="rw-info" width="100%" cellspacing="0" cellpadding="0">
        <tr><td width="22%">Verifikasi online</td><td width="2%">:</td><td>{{ $urlVerifikasi }}</td></tr>
        <tr><td>Ringkasan isi (SHA-256)</td><td>:</td><td class="rw-mono">{{ $digest }}</td></tr>
        <tr><td>Algoritma tanda tangan</td><td>:</td><td>Ed25519 (kunci Fakultas Teknik UM Buton)</td></tr>
    </table>

    <p class="rw-catatan">Lembar ini dihasilkan otomatis oleh SIPERSU FT-UMB saat surat ditandatangani. Keaslian surat dapat diperiksa dengan memindai kode QR pada halaman pertama atau membuka alamat verifikasi di atas; setiap pemindaian tercatat. Catatan internal pemrosesan tidak dicantumkan.</p>
</div>
