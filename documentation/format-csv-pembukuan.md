# Format CSV Impor Pembukuan Surat

Pembukuan (buku agenda) mencatat surat **masuk**, **keluar**, atau **lainnya** berdasarkan **nomor surat**. Surat yang dibuat di
aplikasi tampil otomatis di buku; CSV dipakai untuk memasukkan **surat lama** (mis. sebelum aplikasi dipakai) dalam jumlah banyak.

Menu: **Pembukuan → Impor CSV** (Admin TU / Super Admin). Contoh berkas: [contoh-impor-pembukuan.csv](contoh-impor-pembukuan.csv).

## Kolom

| Kolom | Wajib | Isi |
|---|---|---|
| `arah` | **Ya** | `masuk`, `keluar`, atau `lain` (SK, nota dinas, dll.). Dikenali juga: `surat masuk`, `sm`, `surat keluar`, `sk`, `lainnya` |
| `nomor` | **Ya** | Nomor surat lengkap apa adanya (surat masuk: nomor dari pengirim). Contoh `045/II.3.AU/FT-UMB/I/2026`. Huruf, angka, spasi, `/ . - ( ) _ ,` |
| `tanggal_surat` | **Ya** | `2026-01-05` atau `05/01/2026` |
| `perihal` | **Ya** | Hal surat (maks 255 karakter) |
| `pihak` | Tidak | Surat masuk: asal. Surat keluar: tujuan |
| `lampiran` | Tidak | Mis. `2 berkas` |
| `sifat` | Tidak | `biasa`, `penting`, `segera`, `rahasia` (kosong = biasa) |
| `jenis` | Tidak | Mis. Surat Tugas, SK Dekan, Nota Dinas |
| `tanggal_diterima` | Tidak | Surat masuk; kosong = sama dengan tanggal surat |
| `no_agenda` | Tidak | Nomor agenda surat masuk bila sudah ada |
| `keterangan` | Tidak | Catatan bebas |

Nama kolom alternatif yang juga dikenali: `nomor_surat`, `no_surat`, `tgl_surat`, `tanggal`, `asal`, `tujuan`, `dari`, `kepada`, `hal`, `tgl_diterima`, `nomor_agenda`, `catatan`.
Pemisah `,` `;` atau Tab dikenali otomatis. Dari Excel: *Save As → CSV UTF-8 (Comma delimited)*. Maksimal **1000 baris** dan 2 MB per berkas.

## Aturan

1. **Periksa dulu, simpan kemudian**: berkas diperiksa (belum ada yang disimpan), lalu dikonfirmasi.
2. **Nomor kembar dilewati, bukan ditimpa.** Surat keluar kembar bila nomornya sama dengan catatan lain di pembukuan atau surat yang terbit di aplikasi. Surat masuk kembar bila nomor, pengirim, dan tanggal surat sama.
3. Baris bergalat (arah/tanggal salah, nomor kosong) **tidak diimpor**; baris valid tetap bisa diimpor.

## Penghitung nomor otomatis

Untuk surat **keluar** yang nomornya mengikuti pola penomoran (Pengaturan → Format Nomor, bawaan `{urut}/{klasifikasi}/FT-UMB/{bulan_romawi}/{tahun}`)
dan klasifikasinya ada di Master Data, aplikasi dapat **menyesuaikan penghitung**: nomor otomatis berikutnya melanjutkan nomor urut tertinggi di CSV.
Contoh: CSV berakhir `045/II.3.AU/…/2026` → surat berikutnya yang dibuat aplikasi bernomor `046/II.3.AU/…/2026`.
Pilihan ini ada (tercentang) pada langkah konfirmasi. Bila tidak dicentang, aplikasi tetap **melewati nomor yang sudah tercatat** saat menerbitkan nomor otomatis.
Nomor yang diketik TU secara mandiri juga ditolak bila bentrok dengan nomor di pembukuan.

## Ekspor

**Pembukuan → Ekspor CSV** mengunduh buku (sesuai filter) dalam format yang sama. Perihal bersifat rahasia disamarkan untuk pengguna selain Admin TU.
