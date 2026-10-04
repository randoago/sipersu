{{-- Dokumen surat: dipakai untuk PDF (DomPDF: tanpa flex/grid) dan pratinjau layar. --}}
<div class="surat {{ $untukPdf ? '' : 'layar' }}">
    @php
        $teksKop = fn () => '<div class="kop-kecil">MAJELIS PENDIDIKAN TINGGI PENELITIAN DAN PENGEMBANGAN<br>PIMPINAN PUSAT MUHAMMADIYAH</div>'
            .'<div class="kop-univ">UNIVERSITAS MUHAMMADIYAH BUTON</div><div class="kop-fak">FAKULTAS TEKNIK</div>'
            .'<div class="kop-alamat">Program Studi: Teknik Sipil • Rekayasa Sistem Komputer • Sistem dan Teknologi Informasi<br>'
            .e($alamat).' | '.e($web).' | '.e($email).'</div>';
    @endphp
    {{-- Header hijau (template/img/Header-Undangan.png) sebagai latar kop; logo UMB sudah ada pada gambar. --}}
    @if ($untukPdf)
        <div class="kop-pdf">
            <img class="kop-img" src="{{ $header }}" alt="">
            <div class="kop-teks">{!! $teksKop() !!}</div>
        </div>
    @else
        <div class="kop-layar">
            <img src="{{ $header }}" alt="Header Fakultas Teknik UM Buton">
            <div class="kop-teks">{!! $teksKop() !!}</div>
        </div>
    @endif

    @if ($judul)<div class="judul">{{ $judul }}</div>@endif
    <div class="nomor">Nomor: {{ $nomor ?: '— (diterbitkan saat ditandatangani)' }}</div>

    <div class="isi">{!! $surat->isi_html !!}</div>

    <table class="ttd" cellspacing="0" cellpadding="0" align="right" width="52%">
        <tr><td>Dikeluarkan di : {{ $kota }}<br>Pada tanggal &nbsp;&nbsp;: {{ $tanggal }}<br><strong>{{ $jabatanNama }},</strong></td></tr>
        <tr><td height="96" valign="middle">
            @if ($modeQr && $terbit && $qrSvg)
                <table cellspacing="0" cellpadding="0"><tr>
                    <td width="96" valign="top"><img src="data:image/svg+xml;base64,{{ base64_encode($qrSvg) }}" width="92" height="92" alt="QR"></td>
                    <td valign="middle" style="padding-left:4px">@if ($spesimen)<img src="{{ $spesimen }}" height="70" alt="">@endif</td>
                </tr></table>
            @elseif ($modeQr)
                <div class="qr-kosong">QR tanda tangan elektronik<br>muncul setelah surat ditandatangani</div>
            @elseif (! $untukPdf)
                <div class="qr-kosong">Ruang tanda tangan basah &amp; cap<br>(surat dicetak, tanpa QR)</div>
            @endif
        </td></tr>
        <tr><td><strong class="nama-pejabat">{{ $namaPejabat }}</strong><br>@if ($nidn)NIDN. {{ $nidn }}@endif</td></tr>
        @if ($terbit && $modeQr)
            <tr><td class="catatan-tte">Dokumen ini telah ditandatangani secara elektronik oleh {{ $sebutanJabatan }} Fakultas Teknik Universitas Muhammadiyah Buton. Keaslian dokumen dapat diverifikasi dengan memindai kode QR.</td></tr>
        @endif
    </table>
    <div style="clear:both"></div>
</div>
