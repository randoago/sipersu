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
    @if ($gayaTanggal === 'hijriah_atas')
        {{-- Nomor di kiri; tanggal Hijriah + Masehi rata kanan di pojok kanan atas (posisi mutlak agar Lampiran & Perihal tetap rapat di bawah Nomor).
             Lebar kolom Nomor sejajar dengan tabel Lampiran/Perihal pada isi. --}}
        <div class="nomor-atas-wrap" style="position:relative">
            <table class="nomor-atas" width="58%" cellspacing="0" cellpadding="0"><tr><td width="24%" valign="top">Nomor</td><td width="5%" valign="top">:</td><td valign="top">{{ $nomor ?: '000' }}</td></tr></table>
            <div class="tgl-atas" style="position:absolute;top:0;right:0;text-align:right">{{ $kota }}, {{ $tanggalHijriah }}<br>{{ $tanggalMasehi }}</div>
        </div>
    @else
        <div class="nomor">Nomor : {{ $nomor ?: '000' }}</div>
    @endif

    @if (! empty($kolomPratinjau))
        <table class="data kolom-pratinjau" cellspacing="0" cellpadding="0" style="margin-left:0;width:100%">
            @foreach ($kolomPratinjau as $label => $nilai)
                <tr><td width="14%">{{ $label }}</td><td width="3%">:</td><td>@if ($label === 'Perihal')<strong>{{ $nilai }}</strong>@else{{ $nilai }}@endif</td></tr>
            @endforeach
        </table>
    @endif

    <div class="isi">{!! $isiAtas !!}</div>

    @php $lebarQr = $modeQr && $terbit && $qrSvg; $geser = $lebarQr ? 'padding-left:0.66cm' : ''; @endphp
    <table class="ttd" cellspacing="0" cellpadding="0" align="right" width="{{ $lebarQr ? '56%' : '52%' }}">
        <tr><td style="{{ $geser }}">
            @if ($gayaTanggal === 'hijriah_atas')
                {{ $sebutanJabatan }},
            @elseif ($gayaTanggal === 'hijriah')
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
                    <td valign="middle" style="padding-left:0">@if ($spesimen)<img class="ttd-spesimen" src="{{ $spesimen }}" height="88" style="margin-left:-12pt" alt="">@endif</td>
                </tr></table>
            @elseif ($modeQr)
                <div class="qr-kosong">QR tanda tangan elektronik<br>muncul setelah surat ditandatangani</div>
            @elseif (! $untukPdf)
                <div class="qr-kosong ttd-kosong">Ruang tanda tangan basah &amp; cap<br>(surat dicetak, tanpa QR)</div>
                @if (! empty($spesimenWeb))<img class="ttd-spesimen" src="{{ $spesimenWeb }}" height="88" style="display:none" alt="">@endif
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
