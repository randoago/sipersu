.surat { font-family: 'Times', 'Times New Roman', serif; font-size: 11.5pt; color: #000; line-height: 1.35; }
.surat .judul { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13pt; margin-top: 8px; }
.surat .nomor { text-align: center; margin-bottom: 14px; }
.surat .isi p { text-align: justify; margin: 0 0 6px; }
.surat table.data { border-collapse: collapse; margin: 4px 0 8px 18px; width: 92%; }
.surat table.data td { padding: 1px 2px; vertical-align: top; }
.surat .ttd { margin-top: 8px; font-size: 11.5pt; page-break-inside: avoid; }
.surat .qr-kosong { border: 1px dashed #888; font-size: 8pt; color: #666; text-align: center; padding: 30px 6px; font-family: Helvetica, Arial, sans-serif; }
.surat .catatan-tte { font-family: Helvetica, Arial, sans-serif; font-size: 7.5pt; line-height: 1.3; color: #333; padding-top: 5px; }
/* Kop: header hijau sebagai latar. Tinggi gambar = 36,45 mm pada lebar A4; area hijau terendah di kanan ±74% tinggi. */
.surat .kop-teks { color: #fff; text-align: center; font-family: 'Helvetica', Arial, sans-serif; line-height: 1.2; }
.surat .kop-kecil { font-size: 7pt; font-weight: bold; }
.surat .kop-univ { font-size: 13pt; font-weight: bold; letter-spacing: .2pt; }
.surat .kop-fak { font-size: 11pt; font-weight: bold; }
.surat .kop-alamat { font-size: 6pt; line-height: 1.25; }
/* PDF (DomPDF): bleed ke tepi halaman memakai posisi mutlak terhadap margin @page (1,2 cm atas; 2,4 cm kiri). */
.surat .kop-pdf { position: relative; height: 2.45cm; }
.surat .kop-pdf .kop-img { position: absolute; top: -1.2cm; left: -2.4cm; width: 21cm; }
.surat .kop-pdf .kop-teks { position: absolute; top: -0.9cm; left: 1.8cm; width: 16.2cm; }
/* Layar: lebar gambar mengikuti kertas; ukuran huruf memakai satuan kontainer agar skalanya sama dengan PDF. */
.surat .kop-layar { position: relative; container-type: inline-size; margin: -48px -9.52% 14px; }
.surat .kop-layar img { display: block; width: 100%; height: auto; }
.surat .kop-layar .kop-teks { position: absolute; top: 8%; left: 20%; right: 2%; }
.surat.layar .kop-kecil { font-size: 1.17cqw; }
.surat.layar .kop-univ { font-size: 2.18cqw; }
.surat.layar .kop-fak { font-size: 1.85cqw; }
.surat.layar .kop-alamat { font-size: 1cqw; }
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
