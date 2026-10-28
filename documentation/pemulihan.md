# Ketahanan dan Pemulihan SIPERSU

Apa yang terjadi bila aplikasi dibanjiri akses, mati, gagal diperbarui, atau datanya rusak, dan **bagaimana membuatnya kembali**. Untuk pengelola sistem dan Admin TU.

| Hal lain | Baca |
|---|---|
| Pemasangan, backup 3-2-1, SOP sandi backup | [../INSTALL.md](../INSTALL.md) |
| Pemulihan lengkap saat laptop server rusak total | [../INSTALL.md](../INSTALL.md) bagian 11 |
| Gambaran sistem | [dokumentasi-sistem.md](dokumentasi-sistem.md) |

---

## 1. Gambaran: apa yang sudah dijaga sistem

| Lapisan | Perlindungan | Perintah / tempat |
|---|---|---|
| Banjir akses | Pembatas per IP **sebelum sesi dan basis data disentuh** (bagian 2) | `config/sipersu.php` → `batas_laju`; `php artisan banjir:buka` |
| Deteksi dini | Pemeriksaan kesehatan tiap jam; masalah kritis dikirim ke Admin sekali per hari (bagian 3) | `php artisan sipersu:periksa` |
| Backup | ZIP terenkripsi AES-256 harian 16.30 (30 terakhir), arsip semester, Google Drive opsional | **Pengaturan → Backup**, `php artisan backup:*` |
| Pemulihan | Uji dulu di folder sementara, backup kondisi saat ini dibuat sebelum menimpa | `php artisan backup:restore` |
| Akses | Hanya jaringan lokal kampus (403 untuk IP lain) | `LAN_CIDRS` |

**Target realistis:** data yang hilang paling banyak **sehari terakhir** (backup harian 16.30; perubahan sesudahnya belum tercadangkan). Waktu pulih bila laptop utuh: menit; bila laptop pengganti: sekitar 1 sampai 2 jam bila backup, sandi backup, dan salinan kode sudah siap.

---

## 2. Banjir akses (flood)

### Apa yang dilakukan sistem

Setiap alamat IP dibatasi, dihitung per menit, **sebelum** aplikasi membuka sesi atau basis data (jadi banjir tidak mengunci SQLite):

| Kondisi dalam 1 menit | Respons |
|---|---|
| Sampai 300 permintaan | Normal |
| Lebih dari 300 | `429 Terlalu Banyak Permintaan` sampai menit berikutnya, header `Retry-After` |
| Lebih dari 600 | IP **diblokir 5 menit**; ditolak tanpa memproses apa pun |

Berlaku untuk halaman web, Livewire, dan REST API (API dijawab JSON). `/up` (pemeriksa kesehatan) dan berkas statis (CSS, gambar) tidak dibatasi. IP di luar jaringan lokal tetap mendapat 403.

Satu halaman biasa ≈ beberapa permintaan, jadi pengguna normal jauh dari batas. Pembatas ini menahan perangkat yang macet menembak server, skrip yang salah, dan percobaan membanjiri.

**Hasil uji beban** (server pengembangan, 6 pekerja, `ab -n 1000 -c 50` ke halaman login): 300 dilayani, 700 ditolak 429, server tetap hidup dan `/up` tetap 200, basis data sesi bertambah tepat 300 baris (bukan 1000), dan IP terblokir setelahnya.

### Pengaturan (`.env`)

| Variabel | Bawaan | Arti |
|---|---|---|
| `LAJU_AKTIF` | `true` | Matikan hanya untuk pengujian |
| `LAJU_PER_MENIT` | `300` | Batas per IP per menit |
| `LAJU_BLOKIR_DETIK` | `300` | Lama blokir bila lewat 2× batas |
| `LAJU_STORE` | `file` | Penyimpanan hitungan (cache berkas, **bukan** SQLite) |

Setelah mengubah: `php artisan config:cache`.

### Bila banjir terjadi

1. **Pastikan yang bermasalah benar-benar banjir.** Buka `storage\logs\laravel.log`; cari `Banjir akses: IP diblokir sementara` atau `Batas permintaan per menit terlampaui`. Isinya IP dan jalur yang dipanggil.
2. **Temukan perangkatnya** (nama perangkat di daftar DHCP router, atau tanya TI kampus). Matikan atau perbaiki perangkat itu. Biasanya aplikasi, skrip, atau peramban yang memanggil terus-menerus.
3. **Admin ikut terblokir?** Tunggu 5 menit, atau dari server:
   ```
   php artisan banjir:buka 192.168.1.77        # satu IP
   php artisan banjir:buka --semua             # semua blokir dan hitungan
   ```
4. **Server tetap lambat padahal pembatas aktif** (banjir dari banyak IP, atau jaringan padat): blokir di router/firewall kampus (batasi koneksi per IP ke port 80), lalu pantau Laragon. Pembatas aplikasi hanya melindungi lapisan aplikasi, bukan bandwidth jaringan.
5. Bila penyebabnya sah (misalnya semua mahasiswa membuka bersamaan lewat satu NAT/proksi sehingga tampak satu IP), **naikkan `LAJU_PER_MENIT`** (mis. 1200).

---

## 3. Deteksi dini: pemeriksaan kesehatan

```
php artisan sipersu:periksa
```

Memeriksa dan memberi kode keluar 1 bila ada masalah **kritis**:

| Pemeriksaan | Kritis bila | Peringatan bila |
|---|---|---|
| Basis data | Tidak terbuka atau `PRAGMA quick_check` melaporkan kerusakan | – |
| Migrasi | Ada migrasi yang belum dijalankan | – |
| Kunci tanda tangan | Kunci hilang atau privat dan publik tidak berpasangan (uji tanda tangan sungguhan) | – |
| Folder tulis | `storage`, cache, atau `bootstrap/cache` tidak dapat ditulis | – |
| Ruang disk | Sisa kurang dari 512 MB | Sisa kurang dari 2 GB |
| Antrean email | – | Lebih dari 20 tugas gagal |
| Pengaturan produksi | `APP_DEBUG=true` di produksi | Pembatas banjir dimatikan di produksi |
| Backup | – | Folder backup tidak ada, backup terakhir lebih dari 2 hari atau gagal, uji pemulihan lebih dari 30 hari |

Perintah ini **dijadwalkan tiap jam** (`sipersu:periksa --kirim`): bila ada masalah kritis, Admin TU dan Super Admin mendapat notifikasi di aplikasi (maksimal sekali per hari). Jalankan manual setelah setiap pembaruan atau pemulihan.

> Pemeriksaan ini hanya berjalan selama aplikasi dan scheduler hidup. Bila server mati total, tidak ada yang mengirim peringatan; karena itu pastikan Task Scheduler dan "Run Laragon when Windows starts" aktif ([INSTALL.md](../INSTALL.md) bagian 1 dan 6) dan pantau secara manual lewat `http://alamat-server/up`.

---

## 4. Skenario dan cara kembali

### 4.1 Aplikasi tidak terbuka

1. Buka `http://localhost/up` **di server**. Jawaban 200 berarti aplikasi hidup; masalahnya di jaringan atau nama (lihat 4.1a).
2. Tidak ada jawaban: buka Laragon, klik **Start All**. Ikon harus hijau.
3. Laragon tidak mau menyala: lihat log di `C:\laragon\bin\apache\...\logs\error.log` dan `storage\logs\laravel.log`. Penyebab umum: port 80 dipakai program lain (IIS, Skype), PHP salah versi, atau berkas `.env` rusak.
4. Server hidup tetapi halaman galat 500: `php artisan sipersu:periksa` biasanya langsung menunjuk sebabnya (migrasi, disk penuh, folder tidak dapat ditulis).
5. Setelah pulih: login sebagai Admin, lihat **Pengaturan → Log Aktivitas**, jalankan **Backup Sekarang**.

**4.1a Nama tidak terbuka tetapi `/up` hidup:** pakai alamat IP cadangan; lihat [INSTALL.md](../INSTALL.md) bagian 5.3 dan [panduan-pengguna.md](panduan-pengguna.md) bagian 10.

**4.1b Laptop mati karena listrik padam:** nyalakan; Laragon dan scheduler otomatis jalan bila sudah diatur. Setel BIOS *Restore on AC power loss = Power On* agar menyala sendiri. Jalankan `php artisan sipersu:periksa`.

### 4.2 Pembaruan gagal: cara kembali (rollback)

**Sebelum memperbarui, selalu:**

1. **Pengaturan → Backup → Backup Sekarang**, pastikan status *Sukses*.
2. Salin folder proyek lama utuh: `C:\laragon\www\sipersu` → `C:\laragon\www\sipersu-lama` (kode dan `.env`, tanpa menjalankannya).
3. Catat jumlah data di dasbor (untuk pembandingan).

**Memperbarui:** ganti kode, lalu `composer install`, `php artisan migrate --force`, `php artisan config:cache`, kemudian `php artisan sipersu:periksa`.

**Bila gagal (galat, data tidak sesuai, halaman rusak):**

1. Hentikan pemakaian (umumkan ke TU).
2. Kembalikan **kode**: ganti nama `sipersu` → `sipersu-gagal`, dan `sipersu-lama` → `sipersu`. Jalankan `php artisan config:clear`.
3. Kembalikan **data** bila migrasi sudah mengubah basis data (bila ragu, lakukan):
   ```
   php artisan backup:restore D:\backup-sipersu\sipersu-TANGGAL-JAM.zip --uji-saja        # periksa dulu
   php artisan backup:restore D:\backup-sipersu\sipersu-TANGGAL-JAM.zip                    # pulihkan (ketik yes)
   ```
   Sistem membuat cadangan kondisi saat ini sebelum menimpa (folder `storage\backups`), sehingga langkah ini sendiri dapat dibatalkan.
4. `php artisan sipersu:periksa` harus tanpa masalah kritis. Buka beberapa surat lama dan pindai satu QR lama (harus **Dokumen Asli**).

Data yang dimasukkan antara backup dan rollback **hilang**; karena itu perbarui di luar jam kerja.

### 4.3 Basis data rusak atau data terhapus

Tanda: galat "database disk image is malformed", `sipersu:periksa` melaporkan *SQLite melaporkan kerusakan*, atau data hilang.

1. Hentikan pemakaian. **Jangan menjalankan backup baru** di atas kerusakan sebelum menyalin berkas lama: salin `database\sipersu.sqlite` ke tempat lain untuk bahan analisis.
2. Pilih backup terbaru yang sehat: `php artisan backup:list`, lalu `php artisan backup:verify <berkas.zip>`.
3. `php artisan backup:restore <berkas.zip> --uji-saja`, periksa jumlah pengguna/pengajuan/surat, lalu pulihkan tanpa `--uji-saja`.
4. Dari catatan fakultas (buku agenda kertas, surat yang dicetak), masukkan lagi surat yang terbit sesudah backup. Surat lama dapat dicatat lewat **Pembukuan → Catat Surat / Impor CSV**.

### 4.4 Kunci tanda tangan hilang atau rusak

Gejala: `sipersu:periksa` melaporkan *Kunci tanda tangan* kritis; penandatanganan gagal.

1. **Pulihkan kunci yang sama** dari salinan: folder hasil `php artisan kunci:cadangkan` (flashdisk), atau dari backup (`backup:restore`). Letakkan `ed25519.secret` dan `ed25519.public` di `storage\keys`.
2. `php artisan kunci:publikasi`, lalu pindai QR surat lama: harus **Dokumen Asli**.
3. **Jangan** menjalankan `kunci:buat --force` kecuali kunci benar-benar tidak mungkin dipulihkan: kunci baru membuat **semua QR surat lama tidak valid** (kunci publik di halaman verifikasi berganti). Bila terpaksa, umumkan dan terbitkan ulang surat yang masih dibutuhkan.

### 4.5 Disk penuh

Gejala: peringatan ruang disk, backup gagal, unggah gagal. Bebaskan ruang: backup lama di `storage\backups`, `storage\logs\*.log` yang besar, berkas sementara. Backup harian hanya menyimpan 30 terakhir. Jalankan `php artisan sipersu:periksa`.

### 4.6 Backup tidak berjalan atau HDD tidak tercolok

`php artisan backup:periksa` (dan `sipersu:periksa`) menandainya. Colokkan HDD (huruf drive harus tetap, lihat INSTALL bagian 9.1), tekan **Backup Sekarang**, dan pastikan riwayat berstatus *Sukses*. Bila folder tujuan hilang, backup harian **gagal dengan jelas** dan tercatat; tidak ada backup yang "diam-diam" kosong.

### 4.7 Sandi backup hilang

Backup **tidak dapat dibuka** tanpa `BACKUP_PASSWORD`; tidak ada jalan pintas. Jangan hanya menyimpannya di `.env` (ikut terkunci di dalam backup). Ikuti SOP penyimpanan sandi di [INSTALL.md](../INSTALL.md) bagian 12. Bila sandi hilang tetapi server masih hidup, ganti `BACKUP_PASSWORD` lalu buat backup baru **segera**.

### 4.8 Laptop server rusak total

Ikuti [INSTALL.md](../INSTALL.md) bagian 11. Syaratnya: berkas backup `.zip` terbaru, **sandi backup**, salinan kode proyek, dan laptop pengganti.

### 4.9 Percobaan masuk bertubi-tubi (tebak kata sandi)

Salah 5 kali dalam semenit memblokir login untuk kombinasi akun dan IP itu; pembatas banjir menahan sisanya. Periksa **Pengaturan → Log Aktivitas** (`login_gagal`). Bila akun tertentu diserang terus, nonaktifkan sementara di **Master Data → Pengguna**.

---

## 5. Daftar periksa rutin

| Kapan | Lakukan |
|---|---|
| Tiap hari | Dasbor Admin normal; **Pengaturan → Backup** berstatus *Sukses*; HDD terpasang |
| Tiap bulan | Uji pemulihan: `scripts\uji-pemulihan.bat`; `php artisan sipersu:periksa` tanpa kritis |
| Akhir semester | Arsip ke flashdisk (`scripts\arsip-semester.bat`), simpan di brankas Dekanat |
| Sebelum pembaruan | Bagian 4.2 (backup, salin folder lama) |
| Setelah pembaruan atau pemulihan | `php artisan sipersu:periksa`, buka satu surat lama, pindai satu QR lama |
| Tahunan | Latihan pemulihan penuh di komputer lain (INSTALL bagian 11) |

## 6. Gladi yang sudah dijalankan

Pada pengembangan fitur ini dijalankan: **uji beban banjir** (hasil di bagian 2); **backup, verifikasi, dan uji pemulihan** terhadap basis data berisi data demo (checksum cocok, SQLite valid, 14 pengguna, 8 pengajuan, 8 surat, 3 lampiran terbaca di folder uji, data aktif tidak berubah); dan uji otomatis `BanjirAksesTest`, `PemeriksaanSistemTest`, `UjiAsapWebTest`, `BackupRestoreTest` (`php artisan test`). Gladi pemulihan **penuh di Windows dengan laptop pengganti belum dilakukan** dan sebaiknya dijadwalkan.
