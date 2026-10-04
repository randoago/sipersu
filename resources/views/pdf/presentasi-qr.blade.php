<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Surat Ber-QR di SIPERSU FT-UMB — Presentasi</title>
<style>
    @page { margin: 0; size: 960pt 540pt; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Helvetica, Arial, sans-serif; color: #1c1b1f; }
    .slide { position: relative; width: 960pt; height: 538pt; overflow: hidden; page-break-after: always; background: #ffffff; }
    .slide.akhir { page-break-after: auto; }
    .bar { background: #0b5c33; color: #fff; height: 74pt; padding: 22pt 44pt 0; }
    .bar h1 { margin: 0; font-size: 27pt; font-weight: bold; }
    .bar .no { position: absolute; right: 44pt; top: 28pt; font-size: 12pt; color: #bfe3cf; }
    .isi { padding: 22pt 44pt 0; }
    .foot { position: absolute; left: 44pt; right: 44pt; bottom: 14pt; font-size: 9pt; color: #8a8a8a; border-top: 0.7pt solid #d8d8d8; padding-top: 5pt; }
    .judul { background: #0b5c33; color: #fff; }
    .judul .tengah { padding: 150pt 60pt 0 60pt; }
    .judul h1 { font-size: 44pt; margin: 0 0 12pt; line-height: 1.1; }
    .judul .sub { font-size: 20pt; color: #cfeadb; margin-bottom: 38pt; }
    .judul .kecil { font-size: 13pt; color: #bfe3cf; line-height: 1.5; }
    .garis { width: 90pt; height: 5pt; background: #f2b705; margin: 0 0 22pt; }
    p { margin: 0 0 9pt; font-size: 16pt; line-height: 1.4; }
    ul { margin: 0 0 8pt 20pt; padding: 0; }
    li { font-size: 18.5pt; line-height: 1.38; margin-bottom: 11pt; }
    li small { display: block; font-size: 13.5pt; color: #5f5f5f; line-height: 1.3; margin-top: 1pt; }
    .kartu { border: 1.3pt solid #0b5c33; background: #eef7f1; padding: 11pt 14pt; }
    .kartu h3 { margin: 0 0 6pt; font-size: 18pt; color: #0b5c33; }
    .kartu p, .kartu li { font-size: 16pt; }
    .merah { border-color: #b3261e; background: #fdeeee; }
    .merah h3 { color: #b3261e; }
    .kuning { border-color: #c79100; background: #fff7dd; }
    .kuning h3 { color: #8a6500; }
    table.k { border-collapse: collapse; width: 100%; }
    table.k th { background: #0b5c33; color: #fff; padding: 8pt 10pt; font-size: 15pt; text-align: left; }
    table.k td { border: 0.8pt solid #b8c4bc; padding: 8pt 10pt; font-size: 15pt; vertical-align: top; }
    table.k tr.z td { background: #f4f8f5; }
    .langkah td.k { background: #0b5c33; color: #fff; padding: 12pt 10pt; font-size: 13pt; vertical-align: top; }
    .langkah td.k b { display: block; font-size: 24pt; color: #f2b705; margin-bottom: 4pt; }
    .langkah td.p { width: 22pt; text-align: center; font-size: 22pt; color: #0b5c33; vertical-align: middle; }
    .url { font-family: Courier, monospace; font-size: 10.5pt; line-height: 1.4; word-break: break-all; border: 0.8pt solid #ccc; background: #f4f4f4; padding: 8pt; }
    .u1 { color: #0b5c33; font-weight: bold; } .u2 { color: #b3261e; font-weight: bold; } .u3 { color: #1a56b3; font-weight: bold; }
    .tengah { text-align: center; }
    .mono { font-family: Courier, monospace; font-size: 11pt; }
    .kecil { font-size: 11.5pt; color: #5f5f5f; }
    .box { border: 1.3pt solid #0b5c33; padding: 9pt 11pt; text-align: center; font-size: 13pt; background: #fff; }
    .box.hijau { background: #0b5c33; color: #fff; }
    .box.abu { background: #eee; border-color: #999; color: #555; }
    .ref li { font-size: 11pt; margin-bottom: 4pt; line-height: 1.3; }
</style>
</head>
<body>

{{-- 1. Judul --}}
<div class="slide judul">
    <div class="tengah">
        <div class="garis"></div>
        <h1>Surat Ber-QR<br>di SIPERSU FT-UMB</h1>
        <div class="sub">Cara kerja, keamanan, dan penggunaannya</div>
        <div class="kecil">Sistem Informasi Persuratan<br>Fakultas Teknik — Universitas Muhammadiyah Buton<br>{{ $tanggal }}</div>
    </div>
</div>

{{-- 2. Masalah --}}
<div class="slide">
    <div class="bar"><h1>Masalah pada surat kertas</h1><span class="no">2 / {{ $total }}</span></div>
    <div class="isi">
        <ul>
            <li><b>Mudah dipalsukan.</b> Tanda tangan, cap, dan kop dapat ditiru atau dipindai ulang.<small>Pihak penerima sulit membedakan surat asli dan palsu hanya dari tampilan.</small></li>
            <li><b>Mudah diubah.</b> Nomor, tanggal, atau nama pada salinan dapat diedit.</li>
            <li><b>Pemeriksaan lambat.</b> Penerima harus menelepon atau datang ke TU untuk memastikan keaslian surat.</li>
            <li><b>Nomor surat rawan ganda atau terlewat</b> bila dibuat manual.</li>
        </ul>
        <div class="kartu" style="margin-top:14pt"><h3>Yang dibutuhkan</h3><p style="margin:0">Cara memeriksa keaslian surat <b>dalam hitungan detik</b>, tanpa menelepon, dan <b>tidak bisa ditiru</b> tanpa kunci rahasia fakultas.</p></div>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 3. Solusi --}}
<div class="slide">
    <div class="bar"><h1>Solusi: segel digital pada setiap surat</h1><span class="no">3 / {{ $total }}</span></div>
    <div class="isi">
        <table width="100%" cellspacing="0" cellpadding="0"><tr>
            <td width="62%" valign="top">
                <ul>
                    <li><b>QR code</b> dicetak pada surat resmi.</li>
                    <li>Di dalamnya ada <b>data inti surat</b> dan <b>tanda tangan digital</b> (Ed25519) dari kunci rahasia fakultas.</li>
                    <li>Siapa pun yang memindai dapat <b>memeriksa keasliannya</b> dengan kunci publik, <b>tanpa server dan tanpa internet</b>.</li>
                    <li>Mengubah satu huruf pada data membuat pemeriksaan <b>gagal</b>.</li>
                </ul>
            </td>
            <td width="38%" valign="top" class="tengah">
                <img src="data:image/svg+xml;base64,{{ base64_encode($qrSvg) }}" width="210" height="210" alt="QR contoh"><br>
                <span class="kecil">QR contoh (data nyata, ditandatangani kunci fakultas)</span>
            </td>
        </tr></table>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 4. Perjalanan surat --}}
<div class="slide">
    <div class="bar"><h1>Perjalanan surat sampai ber-QR</h1><span class="no">4 / {{ $total }}</span></div>
    <div class="isi">
        <table class="langkah" width="100%" cellspacing="0" cellpadding="0"><tr>
            <td class="k" width="19%"><b>1</b>Pengajuan / draf<br><span style="font-size:11pt;color:#cfeadb">Mahasiswa mengajukan atau TU membuat surat dari format.</span></td><td class="p">&raquo;</td>
            <td class="k" width="19%"><b>2</b>Verifikasi &amp; paraf<br><span style="font-size:11pt;color:#cfeadb">Admin TU, Kaprodi, atau Wakil Dekan memeriksa.</span></td><td class="p">&raquo;</td>
            <td class="k" width="19%"><b>3</b>Tanda tangan<br><span style="font-size:11pt;color:#cfeadb">Dekan memasukkan kata sandi akun.</span></td><td class="p">&raquo;</td>
            <td class="k" width="19%"><b>4</b>Nomor &amp; QR<br><span style="font-size:11pt;color:#cfeadb">Nomor resmi dibuat otomatis; QR ditandatangani server.</span></td><td class="p">&raquo;</td>
            <td class="k" width="19%"><b>5</b>PDF A4<br><span style="font-size:11pt;color:#cfeadb">Surat + lembar riwayat dokumen siap cetak/unduh.</span></td>
        </tr></table>
        <ul style="margin-top:22pt">
            <li><b>Nomor surat hanya dibuat saat penandatanganan</b>, urut, tanpa lompatan, dan nomor yang dibatalkan tidak dipakai lagi.</li>
            <li>Setiap langkah <b>tercatat</b> (siapa, kapan) dan tampil pada <b>lembar riwayat</b> di halaman terakhir PDF.</li>
        </ul>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 5. Isi QR --}}
<div class="slide">
    <div class="bar"><h1>Apa isi QR-nya?</h1><span class="no">5 / {{ $total }}</span></div>
    <div class="isi">
        <p style="font-size:14pt">Satu tautan, tiga bagian:</p>
        <div class="url"><span class="u1">{{ $urlDasar }}</span><span class="u2">#</span><span class="u3">{{ \Illuminate\Support\Str::limit($payloadB64, 70, '…') }}</span><span class="u2">.</span><span class="u3">{{ \Illuminate\Support\Str::limit($signature, 40, '…') }}</span></div>
        <table class="k" style="margin-top:12pt">
            <tr><th width="26%">Bagian</th><th>Fungsi</th></tr>
            <tr><td><span class="u1">Alamat halaman</span></td><td>Halaman verifikasi statis (HTML + JavaScript murni).</td></tr>
            <tr class="z"><td><span class="u2">#</span> fragmen</td><td>Yang di belakang <span class="mono">#</span> <b>tidak pernah dikirim ke server</b>; diperiksa di perangkat pemindai.</td></tr>
            <tr><td><span class="u3">Payload</span></td><td>Data surat: nomor, perihal, penandatangan, jabatan, tanggal, dan ringkasan isi (SHA-256).</td></tr>
            <tr class="z"><td><span class="u3">Signature</span></td><td>Tanda tangan Ed25519 64 byte atas payload.</td></tr>
        </table>
        <p class="kecil" style="margin-top:8pt">Panjang {{ $panjangUrl }} karakter; QR versi {{ $level['L']['versi'] }} ({{ $level['L']['modul'] }}×{{ $level['L']['modul'] }} modul), koreksi galat level L, tercetak ±3,2 cm.</p>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 6. Tanda tangan digital --}}
<div class="slide">
    <div class="bar"><h1>Tanda tangan digital, secara sederhana</h1><span class="no">6 / {{ $total }}</span></div>
    <div class="isi">
        <table width="100%" cellspacing="0" cellpadding="0"><tr>
            <td width="46%" valign="top"><div class="kartu merah" style="height:150pt"><h3>Kunci privat (rahasia)</h3><p style="margin:0">Hanya ada di <b>server fakultas</b>. Dipakai <b>menandatangani</b> data surat. Tidak pernah keluar dari server dan cadangan terenkripsi.</p></div></td>
            <td width="8%" class="tengah" valign="middle" style="font-size:26pt;color:#0b5c33">&raquo;</td>
            <td width="46%" valign="top"><div class="kartu" style="height:150pt"><h3>Kunci publik (terbuka)</h3><p style="margin:0">Tertanam di halaman verifikasi. Dipakai <b>memeriksa</b> tanda tangan. Boleh dibagikan ke siapa pun.</p></div></td>
        </tr></table>
        <ul style="margin-top:20pt">
            <li><b>Analogi:</b> kunci privat adalah <i>stempel basah</i> yang hanya dimiliki fakultas; kunci publik adalah <i>alat uji</i> yang boleh dimiliki semua orang.</li>
            <li>Tanda tangan <b>hanya cocok</b> dengan data yang <b>persis sama</b>: salin-tempel data lain atau ubah satu huruf, hasilnya <b>Tidak Valid</b>.</li>
            <li>Memakai <b>Ed25519</b> (RFC 8032) lewat pustaka libsodium: cepat dan tanda tangannya hanya 64 byte.</li>
        </ul>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 7. Memeriksa --}}
<div class="slide">
    <div class="bar"><h1>Memeriksa keaslian surat</h1><span class="no">7 / {{ $total }}</span></div>
    <div class="isi">
        <table width="100%" cellspacing="0" cellpadding="0"><tr>
            <td width="58%" valign="top">
                <table class="langkah" width="100%" cellspacing="0" cellpadding="0">
                    <tr><td class="k" style="font-size:15pt"><b style="display:inline;font-size:22pt">1 </b> Pindai QR dengan kamera ponsel</td></tr>
                    <tr><td style="height:7pt"></td></tr>
                    <tr><td class="k" style="font-size:15pt"><b style="display:inline;font-size:22pt">2 </b> Halaman verifikasi terbuka, tanda tangan diperiksa di peramban</td></tr>
                    <tr><td style="height:7pt"></td></tr>
                    <tr><td class="k" style="font-size:15pt"><b style="display:inline;font-size:22pt">3 </b> <u>Cocokkan</u> nomor, perihal, nama, dan tanggal dengan kertas</td></tr>
                </table>
                <table class="k" style="margin-top:14pt">
                    <tr><td width="38%" style="background:#e3f4ea"><b>Dokumen Asli</b></td><td>Diterbitkan SIPERSU FT-UMB, data utuh.</td></tr>
                    <tr><td style="background:#fdeeee"><b>Tidak Valid</b></td><td>Data diubah / QR palsu / kunci berbeda.</td></tr>
                </table>
            </td>
            <td width="4%"></td>
            <td width="38%" valign="top" class="tengah">
                <img src="data:image/svg+xml;base64,{{ base64_encode($qrSvg) }}" width="230" height="230" alt="QR contoh"><br>
                <span class="kecil">Coba pindai (jaringan fakultas)</span>
            </td>
        </tr></table>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 8. Dua halaman verifikasi --}}
<div class="slide">
    <div class="bar"><h1>Dua halaman verifikasi</h1><span class="no">8 / {{ $total }}</span></div>
    <div class="isi">
        <table class="k">
            <tr><th width="20%"></th><th width="40%">Halaman statis (tujuan QR)</th><th>Verifikasi lengkap (petugas TU)</th></tr>
            <tr><td><b>Alamat</b></td><td class="mono">{{ $urlDasar }}</td><td class="mono">/v/{token}</td></tr>
            <tr class="z"><td><b>Pengguna</b></td><td>Siapa pun yang memindai QR</td><td>Petugas TU, di jaringan lokal</td></tr>
            <tr><td><b>Cara kerja</b></td><td>Di peramban; tanpa server dan basis data</td><td>Server memeriksa ulang dari basis data</td></tr>
            <tr class="z"><td><b>Menampilkan</b></td><td>Asli / Tidak Valid + data surat</td><td>Status <b>batal</b>, riwayat, dan <b>cocokkan hash PDF</b></td></tr>
            <tr><td><b>Catatan</b></td><td>Tidak tahu bila surat sudah dibatalkan</td><td>Setiap pemindaian tercatat di log</td></tr>
        </table>
        <div class="kartu" style="margin-top:16pt"><p style="margin:0"><b>Untuk salinan PDF asli, hubungi TU Fakultas Teknik UM Buton.</b> Kalimat ini tampil di halaman verifikasi.</p></div>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 9. QR vs tanpa QR --}}
<div class="slide">
    <div class="bar"><h1>Dua bentuk surat: ber-QR dan tanpa QR</h1><span class="no">9 / {{ $total }}</span></div>
    <div class="isi">
        <table class="k">
            <tr><th width="24%"></th><th>Ber-QR (tanda tangan elektronik)</th><th>Tanpa QR (tanda tangan basah)</th></tr>
            <tr><td><b>Dipilih</b></td><td>Pada format surat oleh TU</td><td>Pada format surat oleh TU</td></tr>
            <tr class="z"><td><b>Tampilan</b></td><td>QR + tanda tangan bercap (spesimen) + lembar riwayat (2 halaman)</td><td>Ruang kosong di atas nama pejabat (1 halaman)</td></tr>
            <tr><td><b>Pengesahan</b></td><td>Hasil pemeriksaan QR</td><td>Tanda tangan basah dan cap, dibubuhkan manual</td></tr>
            <tr class="z"><td><b>Nomor surat</b></td><td colspan="2">Sama: dibuat otomatis saat penandatanganan, urut, tanpa pemakaian ulang</td></tr>
            <tr><td><b>Cocok untuk</b></td><td>Surat keluar rutin, surat mahasiswa, dokumen yang diperiksa pihak luar</td><td>Instansi yang mewajibkan tanda tangan basah atau cap basah</td></tr>
        </table>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 10. Perlindungan & batasan --}}
<div class="slide">
    <div class="bar"><h1>Yang dilindungi dan yang perlu diwaspadai</h1><span class="no">10 / {{ $total }}</span></div>
    <div class="isi">
        <table width="100%" cellspacing="0" cellpadding="0"><tr>
            <td width="48%" valign="top"><div class="kartu" style="height:350pt"><h3>Dilindungi</h3><ul style="margin-left:16pt">
                <li>Keaslian <b>penerbit</b>: hanya pemilik kunci privat yang bisa membuat QR sah.</li>
                <li>Keutuhan <b>data inti</b> dan ringkasan isi surat.</li>
                <li><b>Privasi</b> pemindai: fragmen tidak dikirim ke server.</li>
                <li>Pemeriksaan <b>tanpa internet</b>.</li>
            </ul></div></td>
            <td width="4%"></td>
            <td width="48%" valign="top"><div class="kartu merah" style="height:350pt"><h3>Waspadai</h3><ul style="margin-left:16pt">
                <li>QR <b>dapat disalin</b> ke dokumen lain: selalu <b>cocokkan data</b> dengan kertas.</li>
                <li>Halaman statis <b>tidak tahu surat dibatalkan</b>; cek ke TU.</li>
                <li>Isi kertas diperiksa lewat ringkasan dan <b>hash PDF</b> oleh TU.</li>
                <li>Bila <b>kunci privat bocor</b>, QR palsu bisa dibuat: jaga dan cadangkan kunci.</li>
            </ul></div></td>
        </tr></table>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 11. Fase 1 --}}
<div class="slide">
    <div class="bar"><h1>Fase 1: hanya jaringan lokal kampus</h1><span class="no">11 / {{ $total }}</span></div>
    <div class="isi">
        <table width="100%" cellspacing="0" cellpadding="0"><tr>
            <td width="24%"><div class="box">HP / laptop<br>di Wi-Fi fakultas</div></td><td width="6%" class="tengah" style="font-size:22pt;color:#0b5c33">&raquo;</td>
            <td width="26%"><div class="box"><b>sipersu.ft.umbuton.ac.id</b><br><span class="kecil">DNS statis di router</span></div></td><td width="6%" class="tengah" style="font-size:22pt;color:#0b5c33">&raquo;</td>
            <td width="24%"><div class="box hijau">Laptop server<br>Laragon + SQLite</div></td>
        </tr></table>
        <table width="100%" cellspacing="0" cellpadding="0" style="margin-top:10pt"><tr>
            <td width="24%"></td><td width="6%"></td><td width="26%"></td><td width="6%"></td>
            <td width="24%"><div class="box abu">Internet: <b>tidak ada rute</b> (IP luar = 403)</div></td>
        </tr></table>
        <ul style="margin-top:16pt">
            <li>Hanya IP privat, 127.0.0.1, dan Tailscale yang diterima; <b>tanpa tunnel, tanpa VPS</b>.<small>Alamat cadangan bila DNS bermasalah: http://IP-laptop</small></li>
            <li><b>Backup:</b> HDD eksternal harian + flashdisk arsip tiap semester (terenkripsi AES-256).<small>Google Drive dan email bersifat opsional; dilewati otomatis bila tidak ada internet.</small></li>
            <li>QR menuju halaman verifikasi di server sendiri, sehingga dapat dipindai dari perangkat di jaringan fakultas.</li>
        </ul>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 12. Hukum --}}
<div class="slide">
    <div class="bar"><h1>Status hukum dan kebijakan</h1><span class="no">12 / {{ $total }}</span></div>
    <div class="isi">
        <ul>
            <li>UU ITE mengakui tanda tangan elektronik yang memenuhi persyaratan sebagai <b>sah dan berakibat hukum</b> (UU 11/2008 jo. UU 19/2016 dan UU 1/2024, antara lain Pasal 5 dan 11).</li>
            <li>PP 71/2019 membedakan tanda tangan elektronik <b>tersertifikasi</b> dan <b>tidak tersertifikasi</b>.</li>
        </ul>
        <div class="kartu kuning" style="margin-top:12pt"><h3>Posisi SIPERSU</h3><p style="margin:0">Tanda tangan Ed25519 fakultas <b>belum diterbitkan penyelenggara sertifikasi elektronik</b> (mis. BSrE-BSSN). Cocok untuk <b>pembuktian keaslian internal dan administratif</b>.</p></div>
        <div class="kartu" style="margin-top:12pt"><h3>Rekomendasi</h3><ul style="margin-left:16pt"><li>Untuk pihak yang mewajibkan tanda tangan tersertifikasi, gunakan <b>tanda tangan basah</b> (surat tanpa QR) atau layanan tersertifikasi.</li><li>Konsultasikan kebijakan dengan bagian hukum/TIK universitas.</li></ul></div>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR • Periksa kembali pasal pada sumber resmi sebelum dikutip</div>
</div>

{{-- 13. Fase 2 --}}
<div class="slide">
    <div class="bar"><h1>Rencana lanjutan (Fase 2, bila internet tersedia)</h1><span class="no">13 / {{ $total }}</span></div>
    <div class="isi">
        <ul>
            <li><b>Halaman verifikasi di internet</b> (GitHub Pages): hanya <i>satu berkas statis</i>; server SIPERSU tetap tertutup.<small>Cukup mengisi VERIFIKASI_URL di .env; berlaku untuk surat yang terbit sesudahnya.</small></li>
            <li><b>Email dan Google Drive</b> aktif otomatis begitu internet ada.</li>
            <li><b>HTTPS</b> lokal dengan sertifikat Let's Encrypt (validasi DNS-01 lewat TI kampus).</li>
            <li><b>Tanda tangan tersertifikasi</b> bila universitas mewajibkan (kerja sama BSrE / penyelenggara resmi).</li>
        </ul>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 14. Ringkasan --}}
<div class="slide">
    <div class="bar"><h1>Ringkasan</h1><span class="no">14 / {{ $total }}</span></div>
    <div class="isi">
        <table width="100%" cellspacing="0" cellpadding="0"><tr>
            <td width="64%" valign="top">
                <ul>
                    <li>Surat ber-QR membawa <b>segel digital</b> (Ed25519) yang hanya bisa dibuat fakultas.</li>
                    <li>Pemeriksaan <b>cepat, di peramban, tanpa internet</b>.</li>
                    <li>Selalu <b>cocokkan</b> data hasil pindai dengan kertas.</li>
                    <li>Aplikasi dan data tetap <b>di jaringan lokal</b> fakultas.</li>
                    <li>Rincian: <span class="mono">documentation/penjelasan-qrcode.pdf</span></li>
                </ul>
            </td>
            <td width="36%" valign="top" class="tengah">
                <img src="data:image/svg+xml;base64,{{ base64_encode($qrSvg) }}" width="200" height="200" alt="QR contoh"><br>
                <span class="kecil">Terima kasih</span>
            </td>
        </tr></table>
    </div>
    <div class="foot">SIPERSU FT-UMB — Surat Ber-QR</div>
</div>

{{-- 15. Referensi --}}
<div class="slide akhir">
    <div class="bar"><h1>Referensi</h1><span class="no">15 / {{ $total }}</span></div>
    <div class="isi" style="padding-top:12pt">
        <ol class="ref" style="margin-left:18pt">
            <li>ISO/IEC 18004:2015. <i>QR Code bar code symbology specification.</i></li>
            <li>Denso Wave. <i>QR Code.com.</i> https://www.qrcode.com/en/</li>
            <li>Reed, I. S. &amp; Solomon, G. (1960). Polynomial Codes Over Certain Finite Fields. <i>J. SIAM</i>, 8(2).</li>
            <li>RFC 3986 — <i>URI: Generic Syntax</i> (bagian 3.5, Fragment). IETF.</li>
            <li>RFC 4648 — <i>Base16, Base32, and Base64 Data Encodings</i> (bagian 5, base64url). IETF.</li>
            <li>RFC 8032 — <i>Edwards-Curve Digital Signature Algorithm (EdDSA).</i> IETF.</li>
            <li>Bernstein, Duif, Lange, Schwabe &amp; Yang (2012). High-speed high-security signatures. <i>J. Cryptographic Engineering</i>, 2(2).</li>
            <li>NIST FIPS PUB 180-4. <i>Secure Hash Standard (SHS).</i> 2015.</li>
            <li>libsodium. <i>Public-key signatures.</i> https://doc.libsodium.org/public-key_cryptography/public-key_signatures</li>
            <li>UU No. 11/2008 tentang ITE, diubah UU No. 19/2016 dan UU No. 1/2024.</li>
            <li>PP No. 71/2019 tentang Penyelenggaraan Sistem dan Transaksi Elektronik.</li>
            <li>BSrE — Badan Siber dan Sandi Negara. https://bsre.bssn.go.id</li>
        </ol>
    </div>
    <div class="foot">Nomor referensi sama dengan documentation/penjelasan-qrcode.pdf. Tautan dan pasal perlu diperiksa pada sumber resmi.</div>
</div>

</body>
</html>
