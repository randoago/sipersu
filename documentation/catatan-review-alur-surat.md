# Catatan review: alur surat bebas diatur

Tanggal: 31 Oktober 2026. Cakupan: fitur alur surat yang dapat diatur per format (menu Alur Surat), mesin persetujuan pengajuan dan surat keluar, serta kode di sekitarnya.

## 1. Ringkasan

Fitur berjalan dan seluruh uji lulus (257 uji). Review ini menemukan satu masalah struktur yang utama (alur disimpan di dua tempat), satu bug yang lolos dari uji otomatis, dan beberapa pengulangan kode. Semuanya sudah direvisi kecuali yang tercatat di bagian 5.

## 2. Temuan dan revisi

| # | Temuan | Dampak | Revisi |
|---|---|---|---|
| 1 | **Dua sumber kebenaran.** Alur tersimpan di `jenis_surat.alur` dan juga di kolom lama `verifikator_role`, `perlu_paraf`, `paraf_role` yang disinkronkan dari tahap pertama. | Setiap perubahan harus menulis dua tempat; kolom lama tidak bisa menggambarkan lebih dari satu pemaraf; satu cabang kode lama (`MasterController`) menulis kolom itu tanpa memperbarui `alur`. | Migrasi `2026_10_31_100000_alur_satu_sumber`: isi `alur` dari kolom lama bagi baris yang belum punya, lalu buang ketiga kolom. `alur` kini satu-satunya sumber. `down()` memulihkan kolom dari `alur`. |
| 2 | **Tombol "Ubah alur" tidak bereaksi.** `@js(...)` ditulis di dalam atribut tag komponen `<x-kartu x-data="...">`; Blade tidak memprosesnya, sehingga teks mentah sampai ke Alpine. | Fitur tidak bisa dipakai sama sekali. Uji otomatis tidak menangkapnya karena tidak menjalankan JavaScript. | Data Alpine disusun di PHP (`$dataAlpine`) dan dikirim sebagai `:x-data`. Diverifikasi di browser sungguhan (lihat bagian 4). |
| 3 | **Baris judul kartu terpotong di layar kecil**, tombol tidak terlihat. | Tombol tak terjangkau di ponsel. | Baris menjadi kolom di layar kecil, baris di layar lebar. |
| 4 | **Halaman terlalu panjang.** 14 kartu terbuka sekaligus dengan diagram penuh. | Pengguna bingung memilih format. | Tiap alur dapat diciutkan, awalnya hanya nama; tombol Buka semua / Ciutkan semua; kartu yang gagal disimpan terbuka sendiri. |
| 5 | **Kueri berulang.** Pengecekan wewenang (`bolehVerifikasi`, `bolehParaf`, `bolehTandatangan`) masing-masing membaca tahap berjalan dari basis data, dan dasbor serta daftar memanggil ketiganya per pengajuan. | Sampai 3 kueri per pengajuan per halaman. | `AlurPengajuan::wewenang()` mengembalikan jenis tahap yang boleh diputuskan pengguna dengan satu kueri; `boleh*` mendelegasikan ke sana; dasbor, daftar pengajuan, halaman pengajuan, dan persetujuan memakainya. `siapTtd` memakai tahap yang sudah dibaca. |
| 6 | **Cadangan ke kolom lama** (`?? $jenis->verifikator_role`) pada wewenang, penerima notifikasi, dan teks tahap. | Jalur kode yang tidak pernah dipakai setelah baris persetujuan dibuat saat pengajuan. | Dihapus; teks tahap memakai `JenisSurat::peranPertama()`. |
| 7 | **Validasi nomor manual ditulis tiga kali** (`AlurPengajuan` dua kali, `AlurSuratKeluar` sekali), dengan pola dan pesan yang sama. | Perubahan aturan harus dilakukan di tiga tempat. | `NomorManual::periksa()` dipakai ketiganya. |
| 8 | **Dua kunci untuk hal yang sama** pada data surat: `paraf_role` dan `paraf_roles`. | Pembaca harus tahu keduanya. | Surat baru hanya menyimpan `paraf_roles`; `Surat::peranParaf()` tetap membaca `paraf_role` untuk draf lama. Formulir ubah draf dan tombol ajukan diperbaiki agar membaca lewat `peranParaf()`. |
| 9 | **Pembaruan berlebih.** `AlurSuratKeluar::kembalikan` memperbarui status baris persetujuan lalu langsung menghapusnya. | Satu kueri sia-sia. | Pembaruan dibuang. |
| 10 | **Pengaturan alur ada di dua menu** (Format Surat dan Alur Surat). | Membingungkan; pengaturan bisa saling menimpa. | Pengaturan alur hanya di Alur Surat; Format Surat memberi tautan. |

## 3. Keputusan rancangan yang dipertahankan

- **Tahap disalin ke baris `persetujuan` saat pengajuan dibuat.** Mengubah alur format tidak mengganggu pengajuan yang sedang berjalan. Hanya bentuk surat (ber-QR/tanpa QR) yang dikunci selama ada pengajuan berjalan, dan penandatangan yang menunggu ikut diperbarui.
- **Tahap berjalan = baris pertama yang belum diputuskan.** Status pengajuan diturunkan dari jenis tahap itu (verifikasi → Diajukan, paraf → Diverifikasi, ttd → Disetujui).
- **Aturan alur dipusatkan di `JenisSurat::bersihkanTahap()`**: verifikasi selalu di depan paraf; hanya layanan mahasiswa yang diverifikasi (minimal satu); tanpa QR berarti tanpa paraf; paling banyak 6 tahap. Aturan ini dijalankan saat menyimpan dan saat membaca, sehingga data lama pun tetap sah.
- **Tanda tangan selalu satu dan terakhir**, dari `penandatangan_jabatan_id`; Super Admin tidak boleh menandatangani.

## 4. Pemeriksaan yang dilakukan

- Uji otomatis: 257 uji lulus.
- Browser (Chromium, Playwright), login Admin TU, halaman Alur Surat pada lebar 1280 px dan 360 px: tidak ada galat JavaScript dan tidak ada gulir horizontal; kartu awalnya tertutup; kartu dapat dibuka; tombol "Tambah verifikasi" menambah kotak (1 menjadi 2); tombol Batal menutup form. Tangkapan layar lebar 360 px menunjukkan kotak tahap tersusun benar.
- Migrasi dijalankan pada basis data lokal: 17 format tetap memiliki alur yang sama seperti sebelum migrasi.

**Belum diperiksa:** menyimpan alur lewat browser sampai halaman memuat ulang (penyimpanan hanya diuji lewat uji HTTP); perilaku pada peramban selain Chromium; pengajuan sungguhan dengan beberapa pemverifikasi pada data asli.

## 5. Catatan dan utang yang tersisa

1. **Dua mesin persetujuan.** `AlurPengajuan` dan `AlurSuratKeluar` memuat logika serupa (baris persetujuan, tahap berjalan, kembalikan, notifikasi). Perbedaannya wajar (status berbeda), tetapi sebagian dapat disatukan. Disarankan bila ada fitur ketiga yang memakai persetujuan; tidak dikerjakan sekarang karena risikonya lebih besar daripada manfaatnya.
2. **Pemetaan status dan tahap ada di dua tempat**: `AlurPengajuan::TAHAP_STATUS` dan percabangan di `teruskan()`. Keduanya harus diubah bersama.
3. **`AlurSuratKeluar::bolehParaf` masih satu kueri per pemanggilan.** Dampaknya kecil (daftar surat keluar jarang memanggilnya per baris), tetapi dapat dibuat seperti `wewenang()` bila daftar membesar.
4. **Cabang `jenis-surat` di `MasterController`** tidak terjangkau (rutenya dialihkan ke Format Surat) dan layak dihapus dalam pembersihan terpisah.
5. **Bidang API `perlu_paraf`, `verifikator`, `pemaraf`** kini diturunkan dari tahap pertama dan hanya dipertahankan demi kompatibilitas; klien baru sebaiknya memakai `tahap`.
6. **Tampilan layar sempit:** panah antartahap di editor kotak tetap tampil di samping kotak yang turun baris; tidak mengganggu fungsi, hanya kurang rapi.
7. **Uji JavaScript belum otomatis.** Pemeriksaan browser dilakukan dengan skrip sekali pakai. Bila bagian antarmuka ini sering berubah, pertimbangkan uji browser permanen.
8. **Simulasi dan perintah contoh surat** memverifikasi dengan pemverifikasi tahap pertama saja; bila format contoh diubah memiliki banyak pemverifikasi, skrip itu perlu menyesuaikan.
