# Contoh Surat (PDF)

PDF di folder ini dibuat langsung oleh mesin PDF SIPERSU (kop, nomor, QR, spesimen tanda tangan Dekan),
sehingga tampilannya sama persis dengan surat yang terbit di aplikasi.

Setiap jenis surat tersedia **berpasangan**: **BER-QR** (tanda tangan elektronik) dan **TANPA-QR** (tanda tangan basah + cap).

| No | Jenis surat | Ber-QR (2 halaman) | Tanpa QR (1 halaman) |
|---|---|---|---|
| 01 | Surat Keterangan Aktif Kuliah | `01-surat-keterangan-aktif-kuliah-BER-QR.pdf` | `01-surat-keterangan-aktif-kuliah-TANPA-QR.pdf` |
| 02 | Surat Izin Penelitian (dengan paraf Wakil Dekan) | `02-surat-izin-penelitian-BER-QR.pdf` | `02-surat-izin-penelitian-TANPA-QR.pdf` |
| 03 | Surat Pengantar Kerja Praktik (diverifikasi Kaprodi) | `03-surat-pengantar-kerja-praktik-BER-QR.pdf` | `03-surat-pengantar-kerja-praktik-TANPA-QR.pdf` |
| 04 | Surat Cuti Akademik | `04-surat-cuti-akademik-BER-QR.pdf` | `04-surat-cuti-akademik-TANPA-QR.pdf` |
| 05 | Surat Rekomendasi Beasiswa | `05-surat-rekomendasi-beasiswa-BER-QR.pdf` | `05-surat-rekomendasi-beasiswa-TANPA-QR.pdf` |
| 06 | Surat keluar umum (undangan rapat koordinasi) | `06-surat-keluar-umum-BER-QR.pdf` | `06-surat-keluar-umum-TANPA-QR.pdf` |
| 07 | Surat Undangan dari format buatan TU (tanggal Hijriah + Masehi) | `07-surat-undangan-dari-format-BER-QR.pdf` | `07-surat-undangan-dari-format-TANPA-QR.pdf` |
| 08 | Surat Tugas dari format (tabel yang ditugaskan, tema/mitra/waktu, tembusan) | `08-surat-tugas-dari-format-BER-QR.pdf` | `08-surat-tugas-dari-format-TANPA-QR.pdf` |
| 09 | Surat Tugas Rekomendasi dari format (tanggal Hijriah) | `09-surat-tugas-rekomendasi-dari-format-BER-QR.pdf` | `09-surat-tugas-rekomendasi-dari-format-TANPA-QR.pdf` |
| 10 | Surat Pemberitahuan dari format | `10-surat-pemberitahuan-dari-format-BER-QR.pdf` | `10-surat-pemberitahuan-dari-format-TANPA-QR.pdf` |
| 11 | Surat Keterangan Aktif Kuliah (dibuat TU dari format) | `11-surat-keterangan-aktif-kuliah-dari-format-BER-QR.pdf` | `11-surat-keterangan-aktif-kuliah-dari-format-TANPA-QR.pdf` |
| 12 | Surat Keterangan Cuti Akademik (format; paraf Wakil Dekan) | `12-surat-keterangan-cuti-dari-format-BER-QR.pdf` | `12-surat-keterangan-cuti-dari-format-TANPA-QR.pdf` |
| 13 | Surat Keterangan Aktif Kembali Setelah Cuti (format) | `13-surat-keterangan-aktif-kembali-dari-format-BER-QR.pdf` | `13-surat-keterangan-aktif-kembali-dari-format-TANPA-QR.pdf` |
| 14 | Surat Permohonan Pencairan Anggaran (format; tabel rincian) | `14-surat-pencairan-anggaran-dari-format-BER-QR.pdf` | `14-surat-pencairan-anggaran-dari-format-TANPA-QR.pdf` |
| 15 | Surat Izin Penelitian (dibuat TU dari format) | `15-surat-izin-penelitian-dari-format-BER-QR.pdf` | `15-surat-izin-penelitian-dari-format-TANPA-QR.pdf` |

Surat masuk (SM-UMUM, SM-UNDANGAN, SM-PERMOHONAN) adalah catatan surat dari pihak luar, bukan surat yang diterbitkan fakultas, sehingga tidak dibuatkan contoh PDF.

## Catatan

- **Kop surat** mengikuti surat resmi fakultas: header hijau (`template/img/Header-Undangan.png`) berisi FAKULTAS TEKNIK / UNIVERSITAS MUHAMMADIYAH BUTON / alamat, dan footer hijau rata kanan berisi alamat serta e-mail/laman (diatur di Pengaturan → Format Nomor).
- **Ukuran kertas A4** (210 × 297 mm) untuk semua berkas.
- **Surat ber-QR terdiri dari 2 halaman**: halaman 1 = surat (kop header hijau, isi, QR + spesimen tanda tangan); halaman 2 = **Lembar Riwayat Dokumen** (diajukan, diverifikasi, diparaf, ditandatangani, beserta pelaksana dan waktu, alamat verifikasi, dan ringkasan SHA-256). Surat **tanpa QR** hanya 1 halaman karena disahkan dengan tanda tangan basah dan cap.

- Semua data (nama mahasiswa, nomor, tanggal) adalah **data contoh**. Pejabat penandatangan memakai data seeder.
- **QR pada contoh ini tidak dapat diverifikasi**: contoh dibuat dalam transaksi yang di-rollback, sehingga
  token dan nomor tidak tersimpan di basis data (nomor asli tidak terpakai). Surat yang diterbitkan lewat
  aplikasi punya QR yang bisa diverifikasi.
- Surat **ber-QR** memuat QR + spesimen **tanda tangan dengan stempel** (`template/img/ttd-dekan/ttd-dekan-stempel.png`).
- Surat **tanpa QR** disiapkan **kosong**: tanpa QR, tanda tangan, maupun stempel — ruang di atas nama pejabat dikosongkan untuk dibubuhi manual.

> Penjelasan lengkap isi QR, tanda tangan digital, batasan, dan referensi: `documentation/penjelasan-qrcode.pdf`.

## Membuat ulang

```bash
php artisan dokumentasi:contoh-surat
```

Perintah ini tidak mengubah basis data. Untuk menyesuaikan tampilan, ubah templat di **Master Data → Jenis Surat**
(isi surat mahasiswa), `resources/views/pdf/` (kop, blok tanda tangan), atau **Pengaturan → Format Nomor** (alamat kop).

Aset kop/logo/spesimen: `template/img/`.
