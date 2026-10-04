# Contoh Surat (PDF)

PDF di folder ini dibuat langsung oleh mesin PDF SIPERSU (kop, nomor, QR, spesimen tanda tangan Dekan),
sehingga tampilannya sama persis dengan surat yang terbit di aplikasi.

| Berkas | Jenis | Bentuk |
|---|---|---|
| `01-surat-keterangan-aktif-kuliah.pdf` | Surat Keterangan Aktif Kuliah | Ber-QR (TTE) |
| `02-surat-izin-penelitian.pdf` | Surat Izin Penelitian (dengan paraf Wakil Dekan) | Ber-QR (TTE) |
| `03-surat-pengantar-kerja-praktik.pdf` | Surat Pengantar Kerja Praktik (diverifikasi Kaprodi) | Ber-QR (TTE) |
| `04-surat-cuti-akademik.pdf` | Surat Cuti Akademik | Ber-QR (TTE) |
| `05-surat-rekomendasi-beasiswa.pdf` | Surat Rekomendasi Beasiswa | Ber-QR (TTE) |
| `06-surat-keterangan-aktif-kuliah-TANPA-QR.pdf` | Surat Keterangan Aktif Kuliah | **Tanpa QR** — dicetak, tanda tangan basah + cap |
| `07-surat-keluar-undangan-ber-QR.pdf` | Surat keluar umum (undangan) | Ber-QR (TTE) |
| `08-surat-keluar-undangan-TANPA-QR.pdf` | Surat keluar umum (undangan) | **Tanpa QR** |
| `09-surat-tugas-dari-format.pdf` | Surat Tugas — dibuat dari **format buatan TU** (Format Surat → Buat Surat → isi formulir) | Ber-QR (TTE) |

## Catatan

- **Ukuran kertas A4** (210 × 297 mm) untuk semua berkas.
- **Surat ber-QR terdiri dari 2 halaman**: halaman 1 = surat (kop header hijau, isi, QR + spesimen tanda tangan); halaman 2 = **Lembar Riwayat Dokumen** (diajukan, diverifikasi, diparaf, ditandatangani, beserta pelaksana dan waktu, alamat verifikasi, dan ringkasan SHA-256). Surat **tanpa QR** hanya 1 halaman karena disahkan dengan tanda tangan basah dan cap.

- Semua data (nama mahasiswa, nomor, tanggal) adalah **data contoh**. Pejabat penandatangan memakai data seeder.
- **QR pada contoh ini tidak dapat diverifikasi**: contoh dibuat dalam transaksi yang di-rollback, sehingga
  token dan nomor tidak tersimpan di basis data (nomor asli tidak terpakai). Surat yang diterbitkan lewat
  aplikasi punya QR yang bisa diverifikasi.
- Pada surat **tanpa QR** ruang di atas nama pejabat sengaja dikosongkan untuk tanda tangan basah dan cap.

## Membuat ulang

```bash
php artisan dokumentasi:contoh-surat
```

Perintah ini tidak mengubah basis data. Untuk menyesuaikan tampilan, ubah templat di **Master Data → Jenis Surat**
(isi surat mahasiswa), `resources/views/pdf/` (kop, blok tanda tangan), atau **Pengaturan → Format Nomor** (alamat kop).

Aset kop/logo/spesimen: `template/img/`.
