{{-- Dokumen surat: dipakai untuk PDF (DomPDF: tanpa flex/grid) dan pratinjau layar. --}}
<div class="surat {{ $untukPdf ? '' : 'layar' }}">
    @php
        // Kop sesuai surat resmi fakultas: tiga baris putih pada header hijau.
        $teksKop = fn () => '<div class="kop-fak">FAKULTAS TEKNIK</div><div class="kop-univ">UNIVERSITAS MUHAMMADIYAH BUTON</div><div class="kop-alamat">'.e($kopAlamat).'</div>';
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
    <div class="nomor">Nomor : {{ $nomor ?: '— (diterbitkan saat ditandatangani)' }}</div>

    <div class="isi">{!! $isiAtas !!}</div>

    @php $lebarQr = $modeQr && $terbit && $qrSvg; $geser = $lebarQr ? 'padding-left:0.66cm' : ''; @endphp
    <table class="ttd" cellspacing="0" cellpadding="0" align="right" width="{{ $lebarQr ? '56%' : '52%' }}">
        <tr><td style="{{ $geser }}">
            @if ($gayaTanggal === 'hijriah')
                <table class="tgl-hijriah" cellspacing="0" cellpadding="0"><tr><td class="tgl-kota">{{ $kota }},</td><td>{{ $tanggalHijriah }}<br>{{ $tanggalMasehi }}</td></tr></table>
                {{ $sebutanJabatan }},
            @else
                Dikeluarkan di : {{ $kota }}<br>Pada tanggal &nbsp;&nbsp;: {{ $tanggal }}<br>{{ $sebutanJabatan }},
            @endif
        </td></tr>
        <tr><td height="108" valign="middle" style="{{ $geser }}">
            @if ($modeQr && $terbit && $qrSvg)
                <table cellspacing="0" cellpadding="0"><tr>
                    <td width="94" valign="top"><img src="data:image/svg+xml;base64,{{ base64_encode($qrSvg) }}" width="92" height="92" alt="QR"></td>
                    <td valign="middle" style="padding-left:0">@if ($spesimen)<img src="{{ $spesimen }}" height="102" style="margin-left:-14pt" alt="">@endif</td>
                </tr></table>
            @elseif ($modeQr)
                <div class="qr-kosong">QR tanda tangan elektronik<br>muncul setelah surat ditandatangani</div>
            @elseif (! $untukPdf)
                <div class="qr-kosong">Ruang tanda tangan basah &amp; cap<br>(surat dicetak, tanpa QR)</div>
            @endif
        </td></tr>
        <tr><td style="{{ $geser }}"><strong class="nama-pejabat">{{ $namaPejabat }}</strong><br>@if ($nidn)NIDN. {{ $nidn }}@endif</td></tr>
        @if ($terbit && $modeQr)
            <tr><td class="catatan-tte" style="{{ $geser }}">Dokumen ini telah ditandatangani secara elektronik oleh {{ $sebutanJabatan }} Fakultas Teknik Universitas Muhammadiyah Buton. Keaslian dokumen dapat diverifikasi dengan memindai kode QR.</td></tr>
        @endif
    </table>
    <div style="clear:both"></div>
    @if (trim($isiBawah) !== '')<div class="isi isi-bawah">{!! $isiBawah !!}</div>@endif
    {{-- Footer hijau: alamat & kontak (PDF: berulang di setiap halaman; layar: di bawah kertas) --}}
    <div class="{{ $untukPdf ? 'kop-footer kop-footer-pdf' : 'kop-footer kop-footer-layar' }}">Alamat: {{ $alamat }}<br>e-mail: {{ $email }}, page: {{ $web }}</div>
</div>
