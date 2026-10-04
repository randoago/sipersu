.surat { font-family: 'Times', 'Times New Roman', serif; font-size: 11.5pt; color: #000; line-height: 1.35; }
.surat .judul { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13pt; margin-top: 8px; letter-spacing: 4pt; }
.surat .nomor { text-align: center; margin-bottom: 14px; }
.surat .nama-pejabat { text-decoration: underline; }
/* Tanggal Hijriah & Masehi: dua baris, rata di kolom yang sama setelah nama kota */
.surat table.tgl-hijriah { border-collapse: collapse; }
.surat table.tgl-hijriah td { padding: 0; vertical-align: top; text-align: left; }
.surat table.tgl-hijriah td.tgl-kota { padding-right: 4pt; white-space: nowrap; }
.surat .isi p { text-align: justify; margin: 0 0 6px; }
.surat table.data { border-collapse: collapse; margin: 4px 0 8px 18px; width: 92%; }
.surat table.data td { padding: 1px 2px; vertical-align: top; }
.surat .ttd { margin-top: 8px; font-size: 11.5pt; page-break-inside: avoid; }
.surat .qr-kosong { border: 1px dashed #888; font-size: 8pt; color: #666; text-align: center; padding: 30px 6px; font-family: Helvetica, Arial, sans-serif; }
.surat .catatan-tte { font-family: Helvetica, Arial, sans-serif; font-size: 7.5pt; line-height: 1.3; color: #333; padding-top: 5px; }
/* Blok "Yth.": baris di bawahnya menjorok masuk (rata dengan teks setelah "Yth.") */
.surat table.yth { border-collapse: collapse; margin: 0 0 7px; }
.surat table.yth td { padding: 0; vertical-align: top; text-align: left; }
.surat table.yth td.yth-label { width: 32pt; }
/* Isian bertipe tabel & daftar, serta bagian setelah tanda tangan (Tembusan) */
.surat table.tabel-isi { border-collapse: collapse; width: 100%; margin: 6px 0 10px; }
.surat table.tabel-isi th, .surat table.tabel-isi td { border: 1px solid #000; padding: 3px 6px; vertical-align: top; text-align: left; }
.surat table.tabel-isi th { text-align: center; font-weight: normal; }
.surat ol.daftar-isi { margin: 2px 0 6px 0; padding-left: 22px; }
.surat ol.daftar-isi li { text-align: left; margin: 0; }
.surat .isi-bawah { margin-top: 10px; }
.surat .isi-bawah p { margin: 0 0 3px; }
/* Kop: header hijau (template/img/Header-Undangan.png) sebagai latar; teks putih 3 baris seperti surat resmi fakultas.
   Tinggi gambar = 36,45 mm pada lebar A4; area hijau terendah di sisi kanan ±74% tinggi. */
.surat .kop-teks { color: #fff; text-align: center; font-family: 'Helvetica', Arial, sans-serif; line-height: 1.12; }
.surat .kop-fak { font-family: 'Helvetica', Arial, sans-serif; font-size: 17pt; font-weight: bold; }
.surat .kop-univ { font-family: 'Helvetica', Arial, sans-serif; font-size: 17pt; font-weight: bold; }
.surat .kop-alamat { font-family: 'Helvetica', Arial, sans-serif; font-size: 9.5pt; font-weight: bold; margin-top: 3pt; line-height: 1.2; }
/* PDF (DomPDF): bleed ke tepi halaman memakai posisi mutlak terhadap margin @page (1,2 cm atas; 2,4 cm kiri). */
.surat .kop-pdf { position: relative; height: 2.45cm; }
.surat .kop-pdf .kop-img { position: absolute; top: -1.2cm; left: -2.4cm; width: 21cm; }
.surat .kop-pdf .kop-teks { position: absolute; top: -0.8cm; left: 1.6cm; width: 15.7cm; }
/* Layar: lebar gambar mengikuti kertas; ukuran huruf memakai satuan kontainer agar skalanya sama dengan PDF. */
.surat .kop-layar { position: relative; container-type: inline-size; margin: -48px -9.52% 14px; }
.surat .kop-layar img { display: block; width: 100%; height: auto; }
.surat .kop-layar .kop-teks { position: absolute; top: 8%; left: 19%; right: 8%; }
.surat.layar .kop-fak { font-size: 3.1cqw; }
.surat.layar .kop-univ { font-size: 3.1cqw; }
.surat.layar .kop-alamat { font-size: 1.62cqw; }
/* Footer hijau rata kanan */
.surat .kop-footer { color: #009245; font-family: 'Helvetica', Arial, sans-serif; font-weight: bold; text-align: right; line-height: 1.35; }
.surat .kop-footer-pdf { position: fixed; bottom: -1.65cm; right: 0; font-size: 7.5pt; }
.surat .kop-footer-layar { position: absolute; bottom: 20px; right: 8%; font-size: 10px; }
/* Lembar riwayat dokumen */
.riwayat { font-family: 'Helvetica', Arial, sans-serif; font-size: 9.5pt; color: #111; line-height: 1.35; }
.riwayat .rw-kop { text-align: center; font-weight: bold; font-size: 11pt; color: #0F6B3E; border-bottom: 2px solid #0F6B3E; padding-bottom: 6px; }
.riwayat .rw-judul { text-align: center; font-weight: bold; font-size: 13pt; margin-top: 14px; letter-spacing: .5pt; }
.riwayat .rw-sub { text-align: center; color: #555; margin-bottom: 14px; }
.riwayat .rw-bagian { font-weight: bold; margin: 16px 0 6px; padding: 4px 8px; background: #E8F5ED; color: #00512c; }
.riwayat .rw-info td { padding: 2px 4px; vertical-align: top; }
.riwayat .rw-mono { font-family: 'Courier', monospace; font-size: 8pt; word-break: break-all; }
.riwayat .rw-tabel { border-collapse: collapse; }
.riwayat .rw-tabel th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; font-size: 9pt; }
.riwayat .rw-tabel td { border: 1px solid #cbd5e1; padding: 5px 6px; vertical-align: top; }
.riwayat .rw-catatan { margin-top: 18px; font-size: 8pt; color: #555; text-align: justify; }
