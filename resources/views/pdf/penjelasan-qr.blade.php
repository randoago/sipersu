<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Penjelasan QR Code SIPERSU FT-UMB</title>
<style>
    @page { margin: 2cm 2cm 2.2cm 2cm; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10pt; line-height: 1.38; color: #1c1b1f; }
    h1 { font-size: 20pt; margin: 0 0 2pt; color: #0b5c33; }
    h2 { font-size: 13pt; color: #0b5c33; border-bottom: 1.5pt solid #0b5c33; padding-bottom: 2pt; margin: 14pt 0 5pt; page-break-after: avoid; }
    h3 { font-size: 10.5pt; margin: 10pt 0 3pt; page-break-after: avoid; }
    p { margin: 0 0 6pt; text-align: justify; }
    .sub { color: #5f5f5f; font-size: 9.5pt; margin-bottom: 10pt; }
    .kotak { border: 1pt solid #0b5c33; background: #eef7f1; padding: 7pt 9pt; margin: 8pt 0; }
    .awas { border: 1pt solid #b3261e; background: #fdeeee; padding: 7pt 9pt; margin: 8pt 0; }
    table.t { border-collapse: collapse; width: 100%; margin: 5pt 0 9pt; }
    table.t th { background: #0b5c33; color: #fff; text-align: left; padding: 4pt 5pt; font-size: 9pt; }
    table.t td { border: 0.7pt solid #b8c4bc; padding: 3.5pt 5pt; vertical-align: top; font-size: 9.5pt; }
    table.t tr.z td { background: #f4f8f5; }
    code, .mono { font-family: Courier, monospace; font-size: 8.5pt; }
    .url { font-family: Courier, monospace; font-size: 7pt; word-break: break-all; line-height: 1.3; background: #f4f4f4; border: 0.7pt solid #ccc; padding: 5pt; }
    .tengah { text-align: center; }
    .kecil { font-size: 8.5pt; color: #5f5f5f; }
    .alur td { border: 1pt solid #0b5c33; padding: 5pt 6pt; vertical-align: top; font-size: 9pt; background: #fff; }
    .alur td.panah { border: 0; text-align: center; font-size: 15pt; color: #0b5c33; vertical-align: middle; background: none; padding: 0 2pt; }
    ol, ul { margin: 0 0 6pt 15pt; padding: 0; }
    li { margin-bottom: 2pt; }
    .ref li { margin-bottom: 4pt; text-align: left; }
    .pisah { page-break-before: always; }
    table.t tr { page-break-inside: avoid; }
    .footer { position: fixed; bottom: -1.5cm; left: 0; right: 0; text-align: center; font-size: 8pt; color: #777; }
</style>
</head>
<body>
<div class="footer">SIPERSU FT-UMB — Penjelasan QR Code pada Surat • Fakultas Teknik Universitas Muhammadiyah Buton • {{ $tanggal }}</div>

<h1>Penjelasan QR Code pada Surat</h1>
<div class="sub">SIPERSU FT-UMB — Sistem Informasi Persuratan Fakultas Teknik, Universitas Muhammadiyah Buton • Dokumen: {{ $tanggal }}</div>

<div class="kotak">
    <strong>Ringkasan.</strong> QR code pada surat SIPERSU bukan sekadar gambar tautan. Di dalamnya tertanam <strong>data inti surat</strong> (nomor, perihal, penandatangan, jabatan, tanggal, dan ringkasan isi) beserta <strong>tanda tangan digital Ed25519</strong> yang dibuat dengan kunci privat fakultas. Siapa pun yang memindainya dapat memeriksa keaslian data itu dengan <strong>kunci publik</strong> di halaman verifikasi, tanpa server dan tanpa internet.
</div>

<h2>1. Apa itu QR Code?</h2>
<p>QR Code (<em>Quick Response Code</em>) adalah kode batang dua dimensi yang diciptakan oleh Denso Wave (Jepang) pada 1994 dan distandarkan sebagai ISO/IEC 18004 [1][2]. Data disimpan sebagai pola kotak hitam-putih yang disebut <strong>modul</strong>. Dibanding barcode satu dimensi, QR dapat memuat ribuan karakter, dibaca dari sudut mana pun, dan memiliki <strong>koreksi galat</strong> sehingga tetap terbaca walau sebagian rusak atau kotor.</p>

<h3>Bagian-bagian simbol QR</h3>
<table width="100%" cellspacing="0" cellpadding="0"><tr>
    <td width="36%" valign="top" class="tengah">
        <img src="data:image/svg+xml;base64,{{ base64_encode($qrSvg) }}" width="190" height="190" alt="QR contoh"><br>
        <span class="kecil">QR contoh SIPERSU (versi {{ $level['L']['versi'] }}, {{ $level['L']['modul'] }}×{{ $level['L']['modul'] }} modul, koreksi galat L). Isinya dapat diperiksa di halaman verifikasi fakultas.</span>
    </td>
    <td width="4%"></td>
    <td width="60%" valign="top">
        <table class="t" style="margin-top:0">
            <tr><th width="34%">Bagian</th><th>Fungsi</th></tr>
            <tr><td><strong>Finder pattern</strong></td><td>Tiga kotak besar di sudut kiri-atas, kanan-atas, dan kiri-bawah. Penanda posisi dan arah, sehingga QR terbaca dari sudut mana pun.</td></tr>
            <tr class="z"><td><strong>Timing pattern</strong></td><td>Garis hitam-putih selang-seling yang menetapkan ukuran modul dan kisi.</td></tr>
            <tr><td><strong>Alignment pattern</strong></td><td>Kotak kecil (mulai versi 2) untuk meluruskan simbol yang miring atau melengkung.</td></tr>
            <tr class="z"><td><strong>Informasi format</strong></td><td>Menyimpan level koreksi galat dan pola topeng (<em>mask</em>), dilindungi kode koreksi sendiri.</td></tr>
            <tr><td><strong>Data + koreksi galat</strong></td><td>Isi QR dan kode Reed-Solomon [3] untuk memulihkan modul yang rusak.</td></tr>
            <tr class="z"><td><strong>Quiet zone</strong></td><td>Tepi putih selebar minimal 4 modul di sekeliling simbol; wajib agar pemindai bisa memisahkan QR dari sekitarnya.</td></tr>
        </table>
    </td>
</tr></table>

<div style="page-break-inside: avoid">
<h3>Versi dan tingkat koreksi galat</h3>
<p>QR memiliki 40 versi, dari 21×21 modul (versi 1) sampai 177×177 modul (versi 40); tiap versi bertambah 4 modul per sisi. Empat tingkat koreksi galat menentukan berapa persen simbol yang masih dapat dipulihkan [1][2]. Makin tinggi tingkatnya, makin banyak ruang terpakai untuk koreksi sehingga kapasitas data turun dan simbol membesar.</p>
<table class="t">
    <tr><th>Level</th><th>Pemulihan (kira-kira)</th><th>Versi QR contoh ini</th><th>Ukuran simbol</th></tr>
    @foreach (['L' => '7%', 'M' => '15%', 'Q' => '25%', 'H' => '30%'] as $k => $pct)
        <tr @class(['z' => $loop->even])><td><strong>{{ $k }}</strong>@if ($k === 'L') (dipakai SIPERSU)@endif</td><td>{{ $pct }}</td><td>{{ $level[$k]['versi'] }}</td><td>{{ $level[$k]['modul'] }}×{{ $level[$k]['modul'] }} modul</td></tr>
    @endforeach
</table>
<p class="kecil">Kapasitas maksimum (versi 40, level L): 7.089 digit, 4.296 karakter alfanumerik, atau 2.953 byte [1]. Data SIPERSU berupa teks UTF-8 sehingga dihitung per byte.</p>
</div>
<p><strong>Mengapa SIPERSU memakai level L?</strong> QR dicetak bersih pada kertas A4 dan dilihat dari dekat, jadi risiko rusak kecil. Level L menjaga simbol tetap kecil (±3,2 cm) agar muat di blok tanda tangan dan mudah dipindai ponsel. Bila nanti surat sering difotokopi, level dapat dinaikkan ke M, dengan konsekuensi QR lebih besar.</p>

<h2>2. Apa yang ada di dalam QR SIPERSU?</h2>
<p>QR berisi <strong>satu tautan</strong> ({{ $panjangUrl }} karakter pada contoh ini) dengan dua bagian yang dipisahkan tanda <code>#</code>:</p>
<div class="url">{{ $urlDasar }}<strong>#</strong>{{ $payloadB64 }}<strong>.</strong>{{ $signature }}</div>
<table class="t">
    <tr><th width="26%">Bagian</th><th>Penjelasan</th></tr>
    <tr><td><span class="mono">{{ $urlDasar }}</span></td><td><strong>Halaman verifikasi statis</strong> (HTML + JavaScript murni). Alamatnya diatur di <code>VERIFIKASI_URL</code>; bila kosong, memakai salinan di server fakultas (<code>/verifikasi</code>, jaringan lokal).</td></tr>
    <tr class="z"><td><code>#</code> (fragmen)</td><td>Semua yang berada <strong>setelah <code>#</code> tidak pernah dikirim peramban ke server</strong> [4]. Pemeriksaan dilakukan sepenuhnya di perangkat pemindai, sehingga data surat tidak tercatat di log server mana pun.</td></tr>
    <tr><td><strong>Payload</strong> (base64url)</td><td>Data JSON yang diberi tanda tangan, dikodekan base64url [5] agar aman berada di dalam URL (<code>+ /</code> diganti <code>- _</code>, tanpa <code>=</code>).</td></tr>
    <tr class="z"><td><strong>Signature</strong> (base64url)</td><td>Tanda tangan Ed25519 sepanjang 64 byte (86 karakter base64url) atas payload [6][7].</td></tr>
</table>

<div style="page-break-inside: avoid">
<h3>Isi payload (contoh nyata, sebelum dikodekan)</h3>
<div class="url" style="font-size:8pt">{{ $payload }}</div>
<table class="t">
    <tr><th width="10%">Kunci</th><th>Arti</th></tr>
    <tr><td><code>v</code></td><td>Versi format payload (agar pembaruan di masa depan tetap dapat dibaca).</td></tr>
    <tr class="z"><td><code>n</code></td><td>Nomor surat resmi (dibuat saat penandatanganan).</td></tr>
    <tr><td><code>p</code></td><td>Perihal surat.</td></tr>
    <tr class="z"><td><code>s</code> / <code>j</code></td><td>Nama dan jabatan penandatangan.</td></tr>
    <tr><td><code>t</code></td><td>Tanggal surat.</td></tr>
    <tr class="z"><td><code>h</code></td><td>Ringkasan SHA-256 [8] dari gabungan nomor, perihal, tanggal, dan <strong>isi surat</strong>. Mengikat QR pada isi surat: mengubah isi akan mengubah <code>h</code>.</td></tr>
</table>
</div>
<p class="kecil">Surat <strong>tanpa QR</strong> (disahkan dengan tanda tangan basah dan cap) tidak memiliki payload maupun signature.</p>

<h2>3. Bagaimana tanda tangan digitalnya bekerja?</h2>
<p>SIPERSU memakai <strong>Ed25519</strong>, skema tanda tangan kunci publik modern berbasis kurva eliptik (EdDSA) [6][7], melalui pustaka libsodium [9]. Singkatnya: pasangan kunci dibuat sekali. <strong>Kunci privat</strong> (32 byte) hanya ada di server fakultas dan dipakai menandatangani. <strong>Kunci publik</strong> (32 byte) boleh disebarkan; dipakai memeriksa. Tanda tangan hanya cocok dengan payload yang <em>persis sama</em>, sehingga mengubah satu huruf pada payload membuat pemeriksaan gagal. Penjelasan mendalam ada di <code>documentation/cara-kerja-tanda-tangan-elektronik.md</code>.</p>

<h2>4. Alur dari penerbitan sampai pemeriksaan</h2>
<table class="alur" width="100%" cellspacing="0" cellpadding="0"><tr>
    <td width="24%"><strong>1. Surat ditandatangani</strong><br>Pejabat memasukkan kata sandi. Nomor surat dibuat; payload disusun.</td>
    <td class="panah" width="3%">&raquo;</td>
    <td width="24%"><strong>2. Server menandatangani</strong><br>Payload ditandatangani kunci privat Ed25519. Hasilnya disimpan.</td>
    <td class="panah" width="3%">&raquo;</td>
    <td width="22%"><strong>3. QR dicetak</strong><br>Tautan + payload + signature menjadi QR pada PDF.</td>
    <td class="panah" width="3%">&raquo;</td>
    <td width="21%"><strong>4. Penerima memindai</strong><br>Peramban membuka halaman statis dan memeriksa signature.</td>
</tr></table>

<h3>Apa yang terlihat saat pemeriksaan</h3>
<table class="t">
    <tr><th width="30%">Hasil</th><th>Artinya</th></tr>
    <tr><td><strong>Dokumen Asli</strong></td><td>Signature cocok dengan kunci publik fakultas: data pada QR memang diterbitkan SIPERSU FT-UMB dan belum diubah. Halaman menampilkan nomor, perihal, penandatangan, jabatan, dan tanggal untuk dicocokkan dengan kertas.</td></tr>
    <tr class="z"><td><strong>Tidak Valid</strong></td><td>Signature tidak cocok (data diubah, QR dipalsukan, atau kunci berbeda).</td></tr>
</table>
<p>Selain halaman statis, petugas TU di jaringan lokal memiliki <strong>verifikasi lengkap</strong> (<code>/v/{token}</code>) yang menampilkan status batal, riwayat dokumen, dan <strong>mencocokkan hash PDF</strong>: berkas yang diunggah dibandingkan dengan hash PDF final yang tersimpan di server. Untuk salinan PDF asli, penerima menghubungi TU Fakultas Teknik UM Buton.</p>

<div style="page-break-inside: avoid">
<h2>5. Yang dilindungi dan yang tidak</h2>
<table class="t">
    <tr><th width="50%">Dilindungi</th><th>Perlu diperhatikan</th></tr>
    <tr>
        <td valign="top"><ul>
            <li>Keaslian <strong>penerbit</strong>: hanya pemilik kunci privat yang dapat membuat signature yang lolos.</li>
            <li>Keutuhan <strong>data inti</strong> (nomor, perihal, penandatangan, jabatan, tanggal) dan ringkasan isi.</li>
            <li>Privasi pemindai: fragmen tidak dikirim ke server.</li>
            <li>Pemeriksaan tetap jalan <strong>tanpa internet</strong>, dan tanpa bergantung pada server fakultas.</li>
        </ul></td>
        <td valign="top"><ul>
            <li>QR <strong>dapat disalin</strong> ke dokumen lain. Karena itu <strong>cocokkan</strong> nomor, perihal, nama, dan tanggal di halaman verifikasi dengan kertas yang dipegang.</li>
            <li>Halaman statis <strong>tidak tahu surat sudah dibatalkan</strong> (tidak terhubung ke basis data). Status batal hanya tampil di verifikasi lengkap oleh TU.</li>
            <li>Isi surat di kertas hanya terjamin lewat ringkasan <code>h</code> dan hash PDF; pencocokan berkas dilakukan TU (verifikasi lengkap).</li>
            <li>Bila <strong>kunci privat bocor</strong>, penyerang dapat menerbitkan QR palsu. Kunci wajib dijaga dan dicadangkan terenkripsi.</li>
            <li>Membuat kunci baru (<code>kunci:buat --force</code>) membuat <strong>QR lama tidak valid</strong>.</li>
        </ul></td>
    </tr>
</table>

</div>
<div class="awas">
    <strong>Status hukum.</strong> UU ITE mengakui tanda tangan elektronik yang memenuhi persyaratan sebagai sah dan berakibat hukum [10], dan PP 71/2019 membedakan tanda tangan elektronik <em>tersertifikasi</em> dan <em>tidak tersertifikasi</em> [11]. Tanda tangan Ed25519 fakultas ini <strong>tidak diterbitkan oleh penyelenggara sertifikasi elektronik</strong> (mis. BSrE-BSSN [12]). Ia efektif untuk <strong>pembuktian keaslian internal dan administratif</strong>; untuk dokumen yang oleh pihak penerima wajib memakai tanda tangan tersertifikasi, gunakan tanda tangan basah atau layanan tersertifikasi. Konsultasikan kebijakan dengan bagian hukum/TIK universitas.
</div>

<h2>6. Pedoman cetak dan pemindaian</h2>
<ul>
    <li><strong>Ukuran:</strong> pada A4 SIPERSU mencetak QR ±3,2 cm. Jangan diperkecil di bawah ±2,5 cm; QR versi ini sangat rapat (±{{ $level['L']['modul'] }} modul per sisi). Ini pedoman praktis, bukan angka standar.</li>
    <li><strong>Kontras:</strong> hitam di atas putih, tanpa gambar atau tulisan menimpa QR. Jangan memberi warna muda atau latar bergambar.</li>
    <li><strong>Tepi putih (quiet zone):</strong> biarkan kosong di sekeliling QR; jangan dipotong atau tertutup stempel.</li>
    <li><strong>Mencetak:</strong> gunakan printer laser/inkjet resolusi baik; hindari mode hemat tinta. Untuk fotokopi, pertimbangkan level M.</li>
    <li><strong>Memindai:</strong> kamera bawaan ponsel cukup; tahan 15-30 cm dari kertas dengan cahaya cukup. Pastikan ponsel tersambung ke Wi-Fi fakultas bila <code>VERIFIKASI_URL</code> masih berupa alamat lokal.</li>
</ul>

<h2>7. Mencoba sendiri</h2>
<ol>
    <li>Pindai QR contoh di halaman 1 dengan ponsel yang tersambung ke jaringan fakultas. Halaman verifikasi terbuka dan menampilkan <strong>Dokumen Asli</strong> dengan data contoh (QR contoh ditandatangani kunci server pembuat dokumen ini; bila kunci diganti, buat ulang dengan <code>php artisan dokumentasi:qrcode</code>).</li>
    <li>Salin tautannya, ubah satu huruf pada bagian setelah <code>#</code>, lalu buka lagi: hasilnya <strong>Tidak Valid</strong>.</li>
    <li>Bandingkan <strong>sidik jari kunci publik</strong> di halaman itu dengan sidik jari yang diumumkan fakultas.</li>
</ol>

<h2 class="pisah">Referensi</h2>
<ol class="ref">
    <li>ISO/IEC 18004:2015. <em>Information technology — Automatic identification and data capture techniques — QR Code bar code symbology specification.</em> International Organization for Standardization.</li>
    <li>Denso Wave Incorporated. <em>QR Code.com — About QR Code / Standardization.</em> https://www.qrcode.com/en/</li>
    <li>Reed, I. S., &amp; Solomon, G. (1960). Polynomial Codes Over Certain Finite Fields. <em>Journal of the Society for Industrial and Applied Mathematics</em>, 8(2), 300–304.</li>
    <li>Berners-Lee, T., Fielding, R., &amp; Masinter, L. (2005). <em>Uniform Resource Identifier (URI): Generic Syntax</em>, RFC 3986 (bagian 3.5, Fragment). IETF. https://www.rfc-editor.org/rfc/rfc3986</li>
    <li>Josefsson, S. (2006). <em>The Base16, Base32, and Base64 Data Encodings</em>, RFC 4648 (bagian 5, Base 64 Encoding with URL and Filename Safe Alphabet). IETF. https://www.rfc-editor.org/rfc/rfc4648</li>
    <li>Josefsson, S., &amp; Liusvaara, I. (2017). <em>Edwards-Curve Digital Signature Algorithm (EdDSA)</em>, RFC 8032. IETF. https://www.rfc-editor.org/rfc/rfc8032</li>
    <li>Bernstein, D. J., Duif, N., Lange, T., Schwabe, P., &amp; Yang, B.-Y. (2012). High-speed high-security signatures. <em>Journal of Cryptographic Engineering</em>, 2(2), 77–89.</li>
    <li>National Institute of Standards and Technology (2015). <em>Secure Hash Standard (SHS)</em>, FIPS PUB 180-4.</li>
    <li>libsodium. <em>Public-key signatures (Ed25519).</em> https://doc.libsodium.org/public-key_cryptography/public-key_signatures</li>
    <li>Undang-Undang Republik Indonesia Nomor 11 Tahun 2008 tentang Informasi dan Transaksi Elektronik, sebagaimana diubah dengan UU Nomor 19 Tahun 2016 dan UU Nomor 1 Tahun 2024 (antara lain Pasal 5 tentang alat bukti elektronik dan Pasal 11 tentang tanda tangan elektronik).</li>
    <li>Peraturan Pemerintah Republik Indonesia Nomor 71 Tahun 2019 tentang Penyelenggaraan Sistem dan Transaksi Elektronik.</li>
    <li>Balai Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara. https://bsre.bssn.go.id</li>
    <li>Pustaka yang dipakai SIPERSU: <em>BaconQrCode</em> / <em>simplesoftwareio/simple-qrcode</em> (pembuat QR), <em>barryvdh/laravel-dompdf</em> (PDF), PHP <em>sodium</em> (libsodium), dan <em>TweetNaCl.js</em> (pemeriksaan tanda tangan di peramban).</li>
</ol>
<p class="kecil">Catatan: tautan dan nomor pasal sebaiknya diperiksa kembali pada sumber resminya sebelum dikutip dalam dokumen resmi.</p>
</body>
</html>
