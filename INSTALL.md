# Panduan Pemasangan SIPERSU FT-UMB di Windows

Sistem Informasi Persuratan Fakultas Teknik Universitas Muhammadiyah Buton.
Panduan ini ditulis untuk **non-programmer**. Ikuti urutannya dari atas ke bawah.
Tanda ☐ adalah kotak centang untuk Anda pakai sendiri.

---

## 0. Gambaran singkat

| Hal | Keterangan |
|---|---|
| Komputer server | 1 laptop / PC AIO Windows 10/11 yang menyala terus selama jam kerja |
| Perangkat lunak | **Laragon** (berisi Apache + PHP) — gratis. Tanpa Docker, tanpa Node.js |
| Basis data | 1 berkas SQLite: `database\sipersu.sqlite` |
| Akses pengguna | **Hanya jaringan lokal kampus (Fase 1):** `http://sipersu.ft.umbuton.ac.id`; alamat cadangan `http://192.168.1.10` (IP laptop server). Tidak ada rute yang dibuka ke internet |
| Internet | **Tidak wajib.** Hanya untuk pemasangan awal; email, Google Drive, dan halaman verifikasi GitHub Pages bersifat opsional dan otomatis dilewati bila tidak ada internet |
| Cadangan | Harian ke HDD eksternal, arsip tiap semester ke flashdisk; Google Drive opsional |

**Yang disiapkan sebelum mulai**

- ☐ Laptop/PC server dengan akun Windows **Administrator**
- ☐ Folder proyek `sipersu` (dari flashdisk / GitHub / ZIP)
- ☐ HDD eksternal (backup harian) dan flashdisk (arsip semester), masing-masing ≥ 16 GB
- ☐ Akses ke router/DNS kampus untuk membuat nama `sipersu.ft.umbuton.ac.id` (bagian 5.3), atau minta bantuan TI kampus
- ☐ Akun Google fakultas (backup Google Drive) — **opsional**, bisa menyusul saat ada internet
- ☐ Akun email pengirim notifikasi (mis. Gmail fakultas) — **opsional**
- ☐ Akun GitHub gratis — **opsional**, untuk halaman verifikasi QR di internet (bagian 7)

---

## 1. Pasang Laragon

1. Unduh **Laragon Full** dari <https://laragon.org/download/> lalu pasang di `C:\laragon` (lokasi bawaan — jangan diubah).
2. Buka Laragon → klik **Menu → PHP → Version** → pilih **PHP 8.2 atau lebih baru** (disarankan 8.3).
3. Aktifkan ekstensi yang dibutuhkan: **Menu → PHP → Extensions** lalu centang
   `zip`, `gd`, `sodium`, `pdo_sqlite`, `sqlite3`, `mbstring`, `fileinfo`, `intl`, `openssl`.
   (Sebagian besar sudah aktif secara bawaan.)
4. Klik **Menu → Apache** pastikan yang dipakai **Apache** (bukan Nginx) — berkas `.htaccess` proyek sudah siap untuk Apache.
5. Klik **Start All**. Ikon Laragon menjadi hijau.
6. Agar Laragon jalan otomatis saat Windows menyala: **Menu → Preferences → General** → centang
   **Run Laragon when Windows starts** dan **Start All automatically**.

---

## 2. Salin proyek

1. Salin folder proyek ke **`C:\laragon\www\sipersu`**.
   - Jangan ikut menyalin folder `node_modules`, berkas `.env`, atau `database\sipersu.sqlite` dari komputer lain
     (kecuali Anda sedang **memulihkan** — lihat bagian 11).
2. Atur agar alamat server langsung membuka aplikasi:
   **Menu → Preferences → General → Document Root** isi dengan `C:\laragon\www\sipersu\public` lalu **Apply**.
   Klik **Menu → Apache → Reload**.

---

## 3. Atur berkas `.env`

1. Buka `C:\laragon\www\sipersu`. Salin `.env.example` menjadi `.env` (atau biarkan skrip pemasangan melakukannya).
2. Buka `.env` dengan Notepad dan isi bagian penting berikut:

```ini
APP_URL=http://sipersu.ft.umbuton.ac.id   # alamat server di jaringan lokal (bagian 5.3)
APP_FORCE_HTTPS=false                   # true hanya bila memakai mode HTTPS (bagian 5.4)
VERIFIKASI_URL=                         # kosong = halaman verifikasi di server sendiri (bagian 7)
APP_DEBUG=false                         # JANGAN diubah ke true di server

BACKUP_PASSWORD=GantiDenganSandiPanjangYangKuat   # bagian 9 & SOP di bagian 12
BACKUP_LOCAL_PATH=D:\backup-sipersu                 # HDD eksternal (bagian 9)
```

3. Email (opsional — jika dilewati, notifikasi hanya muncul di dalam aplikasi):

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=persuratan.ft@gmail.com
MAIL_PASSWORD=sandi-aplikasi-16-karakter     # bukan sandi login Gmail biasa
MAIL_FROM_ADDRESS="persuratan.ft@gmail.com"
```

   Untuk Gmail: aktifkan verifikasi 2 langkah, lalu buat **Sandi Aplikasi** di
   <https://myaccount.google.com/apppasswords>.

---

## 4. Jalankan pemasangan otomatis

1. Buka folder `C:\laragon\www\sipersu\scripts`.
2. **Klik dua kali `pasang.bat`**. Skrip akan:
   - memeriksa versi PHP dan ekstensi,
   - memasang pustaka (perlu internet **sekali** ini saja),
   - membuat basis data dan mengisi data awal,
   - membuat **kunci tanda tangan elektronik fakultas**,
   - membuat halaman verifikasi statis `verifikasi-statis\index.html`.
3. Buka peramban: `http://localhost` → muncul halaman **masuk SIPERSU**.

### Akun contoh (kata sandi semuanya `password`)

| Peran | NPM/NIDN | Nama |
|---|---|---|
| Super Admin | `0000000001` | Super Admin |
| Admin TU | `198701012010011001` | Admin Tata Usaha |
| Dekan | `0912038401` | Agusman, S.T., MM. |
| Wakil Dekan | `0912048102` | Wakil Dekan (Contoh) |
| Kaprodi Teknik Sipil | `0912058301` | Idwan, S.T., M.Si. |
| Kaprodi Rekayasa Sistem Komputer | `0912068502` | Rando, S.Kom., M.Eng |
| Kaprodi Sistem dan Teknologi Informasi | `0912078603` | Darmawan, S.Kom., M.Kom. |
| Dosen/Tendik | `0912088704` | Dosen Contoh |
| Mahasiswa | `21650012` | Muhammad Fauzan |

> ⚠️ **Wajib dilakukan sebelum dipakai sungguhan**
> - ☐ Masuk sebagai **Super Admin → Profil → Ganti Kata Sandi** untuk **semua** akun, atau nonaktifkan akun contoh di **Master Data → Pengguna**.
> - ☐ Perbarui **nama & nomor induk pejabat asli**, **Master Data → Jabatan** (pejabat aktif & periode), **Klasifikasi Surat**, dan **Program Studi**.
> - ☐ Unggah spesimen pejabat: **Admin TU / Super Admin** di **Master Data → Spesimen TTD** (untuk Dekan, Wakil Dekan, Kaprodi), atau pejabat sendiri di **Profil**. Ada dua gambar: *tanda tangan + stempel* (dipakai surat ber-QR) dan *tanda tangan saja* (cadangan). PNG/JPG, maks. 2 MB.
> - ☐ **Pengaturan → Format Nomor**: periksa pola nomor, kota surat, serta isi **kop surat** (baris alamat pada header hijau; alamat, e-mail, dan laman pada footer hijau di bawah halaman).
> - ☐ Simpan salinan kunci: buka *Command Prompt* di folder proyek lalu jalankan
>   `php artisan kunci:cadangkan D:\kunci-sipersu` dan simpan salinannya di tempat aman (bagian 12).

---

## 5. IP statis, firewall, dan anti-sleep

### 5.1 IP statis (agar alamat server tidak berubah)

1. **Settings → Network & Internet → Wi-Fi/Ethernet → *nama jaringan* → IP assignment → Edit → Manual**.
2. Aktifkan **IPv4** dan isi, misalnya:
   - IP address: `192.168.1.10`
   - Subnet mask: `255.255.255.0`
   - Gateway: `192.168.1.1` (alamat router kampus)
   - DNS: `8.8.8.8` dan `1.1.1.1`
3. Tanyakan ke admin jaringan agar `192.168.1.10` **dikecualikan dari DHCP** (supaya tidak dipakai perangkat lain).
4. Atur alamat nama `sipersu.ft.umbuton.ac.id` agar mengarah ke IP ini (bagian 5.3), pastikan `APP_URL` di `.env` memakai nama itu, lalu jalankan di folder proyek:
   `php artisan config:cache`.

### 5.2 Firewall

1. **Klik kanan `scripts\buka-firewall.bat` → Run as administrator.**
   Port 80 dibuka **hanya untuk jaringan lokal**.
2. Pastikan jaringan diatur sebagai **Private**: *Settings → Network → Properties → Network profile type → Private*.
3. Uji dari HP/laptop lain di Wi-Fi yang sama: buka `http://192.168.1.10` (atau `http://sipersu.ft.umbuton.ac.id` setelah bagian 5.3).

### 5.3 Alamat nama lokal: `sipersu.ft.umbuton.ac.id` (split DNS di router)

Pada Fase 1, aplikasi **hanya** dipakai dari LAN/Wi-Fi fakultas. Pengguna cukup mengetik `http://sipersu.ft.umbuton.ac.id`. Nama itu **diarahkan ke IP lokal laptop server oleh DNS statis di router** (*split DNS*: nama ini hanya dikenal di dalam jaringan kampus; di internet nama tersebut tidak mengarah ke mana pun). **Alamat cadangan:** `http://<IP-laptop>` (mis. `http://192.168.1.10`), yang selalu berfungsi walau DNS bermasalah.

**A. MikroTik** (Winbox → *New Terminal*, atau WebFig → *Terminal*). Pastikan router dipakai sebagai DNS klien (DHCP membagikan IP router sebagai DNS):

```
/ip dns set allow-remote-requests=yes
/ip dns static add name=sipersu.ft.umbuton.ac.id address=192.168.1.10 comment="SIPERSU FT-UMB"
/ip dns cache flush
```

Periksa: `/ip dns static print`. Bila klien memakai DNS selain router (mis. 8.8.8.8), paksa semua DNS klien ke router:

```
/ip firewall nat add chain=dstnat protocol=udp dst-port=53 in-interface-list=LAN action=redirect to-ports=53 comment="Paksa DNS ke router"
/ip firewall nat add chain=dstnat protocol=tcp dst-port=53 in-interface-list=LAN action=redirect to-ports=53
```

**B. Router lain (TP-Link, Tenda, ASUS, Ubiquiti, pfSense, OpenWrt, dll.).** Namanya berbeda-beda, cari menu salah satu dari: *Local DNS*, *DNS Host / Static DNS*, *Host Override*, *Hostname Mapping*, atau *DNS Rewrite* di pengaturan **LAN/DHCP/DNS**. Isi:

| Kolom | Nilai |
|---|---|
| Hostname / Domain | `sipersu.ft.umbuton.ac.id` |
| IP address | `192.168.1.10` (IP statis server) |

Pada **OpenWrt**: *Network → DHCP and DNS → Hostnames*. Pada **pfSense**: *Services → DNS Resolver → Host Overrides*. Bila router tidak punya fitur ini, minta TI kampus menambahkannya di DNS internal kampus (catatan **A** ke IP server), **atau** gunakan cadangan **C**.

**C. Cadangan tanpa akses router: berkas `hosts` di tiap komputer.**
1. Buka Notepad **sebagai Administrator**, buka `C:\Windows\System32\drivers\etc\hosts`.
2. Tambahkan baris di paling bawah lalu simpan: `192.168.1.10   sipersu.ft.umbuton.ac.id`

**Di server:** isi `.env` `APP_URL=http://sipersu.ft.umbuton.ac.id`, lalu `php artisan config:cache`.

**Uji** dari laptop/HP lain di Wi-Fi yang sama: buka `http://sipersu.ft.umbuton.ac.id` → muncul halaman masuk. Di komputer Windows: `nslookup sipersu.ft.umbuton.ac.id` harus menjawab `192.168.1.10`.

#### Bila nama tidak terbuka padahal DNS router sudah benar

Perangkat yang memakai **DNS terenkripsi** akan melewati DNS router sehingga nama lokal tidak dikenal. Matikan di perangkat pengguna:

| Perangkat | Cara |
|---|---|
| **Android** (*Private DNS*) | *Setelan → Jaringan & internet → DNS pribadi (Private DNS)* → pilih **Mati** atau **Otomatis** (bukan nama host penyedia DNS). |
| **Chrome / Edge** (*Secure DNS*) | *Setelan → Privasi dan keamanan → Keamanan → Gunakan DNS aman* → **matikan**, atau pilih "Dengan penyedia layanan Anda saat ini". |
| **Firefox** (*DNS over HTTPS*) | *Setelan → Privasi & Keamanan → DNS over HTTPS* → **Nonaktif**. |
| **iPhone** | Lepas profil VPN/DNS pihak ketiga (*Setelan → Umum → VPN & Manajemen Perangkat*). |
| **VPN / aplikasi pemblokir iklan** | Matikan saat mengakses SIPERSU. |

Selama itu belum beres, pakai **alamat cadangan** `http://<IP-laptop>`.

> Akses dibatasi di dalam aplikasi: hanya IP privat (10.x, 172.16–31.x, 192.168.x), `127.0.0.1`, dan Tailscale (100.64.0.0/10, opsional untuk admin jarak jauh) yang diterima. IP lain mendapat **403**. Tidak ada tunnel atau VPS.

### 5.4 Mode HTTP atau HTTPS (opsional)

| Mode | Kapan | Pengaturan |
|---|---|---|
| **HTTP lokal** (bawaan) | Cukup untuk Fase 1: lalu lintas hanya di LAN/Wi-Fi fakultas | `APP_URL=http://sipersu.ft.umbuton.ac.id`, `APP_FORCE_HTTPS=false` |
| **HTTPS** | Bila ingin gembok di peramban dan sandi terenkripsi di jaringan | Sertifikat Let's Encrypt (di bawah), `APP_URL=https://sipersu.ft.umbuton.ac.id`, `APP_FORCE_HTTPS=true` |

**HTTPS dengan Let's Encrypt (validasi DNS-01, tanpa membuka server ke internet):**

Server tidak perlu terlihat dari internet karena Let's Encrypt cukup memastikan Anda **mengendalikan domain** lewat sebuah record **TXT**.

1. Unduh **win-acme** (<https://www.win-acme.com>) dan ekstrak ke `C:\win-acme`.
2. Jalankan `wacs.exe` sebagai Administrator → **N** (buat sertifikat, opsi penuh) → **Manual input** → host `sipersu.ft.umbuton.ac.id`.
3. Pada pertanyaan metode validasi pilih **[manual] Create records manually (DNS-01)**. win-acme menampilkan record yang harus dibuat, misalnya:
   `_acme-challenge.sipersu.ft.umbuton.ac.id  TXT  "kode-acak-dari-win-acme"`
4. **Kirim record itu ke TI kampus** (pengelola DNS publik `umbuton.ac.id`) untuk ditambahkan, tunggu sampai terlihat (`nslookup -type=TXT _acme-challenge.sipersu.ft.umbuton.ac.id 8.8.8.8`), lalu lanjutkan di win-acme.
5. Pada langkah penyimpanan pilih **PEM files** (mis. folder `C:\laragon\etc\ssl\sipersu`) lalu pasang di Apache Laragon (*Menu → Apache → SSL*; arahkan `SSLCertificateFile` dan `SSLCertificateKeyFile` ke berkas tersebut) dan aktifkan port 443 (bagian 5.2: tambahkan aturan firewall 443 hanya untuk jaringan lokal).
6. Isi `.env`: `APP_URL=https://sipersu.ft.umbuton.ac.id` dan `APP_FORCE_HTTPS=true`, lalu `php artisan config:cache`.
7. Sertifikat berlaku 90 hari. Karena DNS-01 manual, **perpanjangan juga memerlukan record TXT baru dari TI kampus**: catat tanggal kedaluwarsa dan minta TI memakai skrip DNS atau API bila tersedia (win-acme mendukung banyak penyedia DNS). Bila TI tidak bisa, tetap gunakan mode HTTP.

> Tanda "tidak aman" pada mode HTTP di jaringan lokal wajar; yang penting server tidak terbuka ke internet.

### 5.5 Nonaktifkan sleep

**Klik kanan `scripts\nonaktifkan-sleep.bat` → Run as administrator.**
Setelah itu laptop tidak tidur/hibernasi saat tersambung listrik, termasuk saat layar laptop ditutup.
Biarkan laptop tersambung charger.

> Tips: Setel BIOS/UEFI *"Restore on AC power loss = Power On"* agar server menyala sendiri setelah listrik padam.

---

## 6. Task Scheduler (antrean email, backup otomatis)

Aplikasi **tidak** memakai program terpisah yang harus dijaga. Semua tugas berkala dijalankan lewat satu tugas Windows yang berjalan **tiap menit**.

**Cara cepat:** klik kanan `scripts\daftarkan-scheduler.bat` → **Run as administrator**.

**Cara manual** (jika perlu):

1. Buka **Task Scheduler** → **Create Task…**
2. Tab **General**: Name `SIPERSU Scheduler`; pilih **Run whether user is logged on or not**; centang **Run with highest privileges**.
3. Tab **Triggers → New**: *Begin the task:* **On a schedule** → **Daily**; centang **Repeat task every: 1 minute** *for a duration of:* **Indefinitely**.
4. Tab **Actions → New**: *Program/script:* `C:\laragon\www\sipersu\scripts\jalankan-scheduler.bat`
5. Tab **Settings**: centang **Run task as soon as possible after a scheduled start is missed**; *If the task is already running:* **Do not start a new instance**.

Yang dikerjakan scheduler:

| Jadwal | Tugas |
|---|---|
| Tiap menit | Mengirim email yang antre |
| Harian 08.00 | Memeriksa kesehatan backup → email ke Admin bila bermasalah |
| Harian **16.30** | Backup ke `BACKUP_LOCAL_PATH` (simpan 30 terakhir) |
| Jumat **17.30** | Backup ke Google Drive via rclone — **opsional**; dilewati otomatis bila rclone belum diatur atau **tidak ada internet** |

Uji: jalankan `scripts\backup-sekarang.bat` lalu lihat di **Pengaturan → Backup** pada aplikasi.

---

## 7. Verifikasi QR (Fase 1: jaringan lokal)

> Ingin memahami cara kerja tanda tangan elektronik, kunci, dan QR? Baca `documentation/cara-kerja-tanda-tangan-elektronik.md` `documentation/penjelasan-qrcode.pdf`, serta slide presentasi `documentation/presentasi-qr-persuratan.pdf`.

Setiap surat ber-QR memuat tanda tangan digital (Ed25519). QR berisi alamat **halaman verifikasi statis** diikuti `#payload.signature`. Bagian setelah `#` tidak pernah dikirim ke server; halaman statis memeriksa tanda tangan **di peramban** memakai kunci publik fakultas yang tertanam di dalamnya.

| Halaman | Alamat | Untuk siapa | Fungsi |
|---|---|---|---|
| **Verifikasi statis** | `VERIFIKASI_URL` (bawaan: `http://sipersu.ft.umbuton.ac.id/verifikasi`) | Siapa pun yang memindai QR | Memeriksa tanda tangan di peramban; tanpa server/basis data. Menampilkan: *"Untuk salinan PDF asli, hubungi TU Fakultas Teknik UM Buton."* |
| **Verifikasi lengkap** | `http://sipersu.ft.umbuton.ac.id/v/{token}` | Petugas TU (jaringan lokal) | Status batal, riwayat dokumen, dan **cocokkan hash PDF** (unggah berkas). Dibuka dari tombol pada halaman surat/pengajuan |

Pembuatan halaman statis (dilakukan otomatis oleh `pasang.bat`; ulangi bila kunci berubah):

```
php artisan kunci:publikasi
```

Hasilnya `verifikasi-statis\index.html` (satu berkas, tanpa server, tanpa internet). Selama `VERIFIKASI_URL` kosong, berkas ini dilayani aplikasi di `/verifikasi` sehingga QR dapat dipindai dari perangkat di jaringan lokal fakultas. **Uji:** pindai QR surat dengan HP yang tersambung Wi-Fi fakultas → halaman verifikasi terbuka → **Dokumen Asli**. Ubah satu huruf pada tautan → **Tidak Valid**.

> Berkas ini hanya berisi **kunci publik** — aman dibagikan. **Kunci privat** (`storage\keys\ed25519.secret`) tidak pernah boleh keluar dari server dan cadangan terenkripsi. Bila kunci dibuat ulang (`kunci:buat --force`), jalankan `kunci:publikasi` lagi dan QR lama **tidak lagi valid**.

---

## 8. Halaman verifikasi di internet (opsional, GitHub Pages)

**Tidak diperlukan pada Fase 1.** Bila nanti QR ingin dapat dipindai dari luar kampus (tanpa membuka server ke internet), unggah **hanya** halaman statis ke hosting gratis:

1. Di <https://github.com> buat repositori **publik** baru, misalnya `verifikasi-ft-umb`.
2. **Add file → Upload files**: unggah `verifikasi-statis\index.html`.
3. **Settings → Pages → Source: Deploy from a branch → Branch: main / (root) → Save**. Setelah ±1 menit alamatnya menjadi `https://NAMA-ANDA.github.io/verifikasi-ft-umb/`.
4. Isi `.env`: `VERIFIKASI_URL=https://NAMA-ANDA.github.io/verifikasi-ft-umb/` lalu `php artisan config:cache`.
5. Hanya surat yang diterbitkan **setelah** `VERIFIKASI_URL` diubah yang QR-nya memakai alamat baru; QR lama tetap menuju alamat lama.

Halaman itu tidak memuat data surat apa pun selain yang ada di QR, jadi aman dipublikasikan. Server SIPERSU tetap tertutup dari internet.

---

## 9. Backup (aturan 3-2-1)

3 salinan data · 2 media berbeda · 1 di luar lokasi.
**Salinan 1** = data aktif di server, **2** = HDD eksternal (harian), **3** = flashdisk arsip semester yang disimpan di brankas Dekanat. Google Drive (mingguan) adalah salinan tambahan **opsional** yang dilewati bila tidak ada internet.

Isi tiap backup: salinan SQLite yang konsisten (`VACUUM INTO`), folder `storage\app` (berkas & PDF), `storage\keys` (kunci), dan `.env`.
Dibungkus **ZIP terenkripsi AES-256** dengan sandi `BACKUP_PASSWORD`, beserta berkas checksum SHA-256. Contoh nama: `sipersu-2026-10-04-1630.zip`.

### 9.1 HDD eksternal

1. Colokkan HDD. Buka **Disk Management** (klik kanan Start → *Disk Management*).
2. Klik kanan partisi HDD → **Change Drive Letter and Paths…** → pilih huruf tetap, misalnya **D:**
   (agar huruf drive tidak berubah ketika dicabut-colok).
3. Buat folder `D:\backup-sipersu`.
4. Isi `.env`: `BACKUP_LOCAL_PATH=D:\backup-sipersu` lalu `php artisan config:cache`.
5. **Jangan cabut HDD** pada jam 16.30. Bila HDD tidak tercolok / penuh / backup lebih dari 2 hari tidak berhasil, **banner merah** muncul di dasbor Admin dan **email** dikirim.
6. Rutin (mingguan): bawa HDD pulang bergantian dengan HDD kedua bila ada, supaya salinan tidak ikut rusak bersama laptop.

### 9.2 Flashdisk arsip semester

Tiap **akhir semester** (dan sebelum perubahan besar), simpan arsip ke flashdisk yang disimpan terpisah dari laptop server:

1. Colokkan flashdisk, klik dua kali `scripts\arsip-semester.bat`, ketik huruf drive (mis. `E`).
2. Skrip membuat backup terenkripsi ke `E:\arsip-sipersu` dan menyalin **kunci tanda tangan** ke `E:\arsip-sipersu\kunci`.
3. Beri label *"Arsip SIPERSU — Semester Ganjil/Genap 20xx"*, simpan di brankas Dekanat. Catat sandi backup sesuai SOP (bagian 12).
4. Uji sekali setahun: pulihkan arsip dengan `scripts\uji-pemulihan.bat`.

### 9.3 Google Drive dengan rclone (opsional, perlu internet)

> Lewati bagian ini bila belum ada internet. Backup ke Google Drive **dilewati tanpa galat** bila rclone belum diatur atau internet terputus; backup HDD dan flashdisk tetap berjalan.

1. Unduh **rclone** dari <https://rclone.org/downloads/> (Windows, 64-bit). Ekstrak ke `C:\rclone` lalu tambahkan `C:\rclone` ke **PATH**
   (*Start → "Edit the system environment variables" → Environment Variables → Path → New*). Tutup-buka ulang Command Prompt.
2. Di Command Prompt: `rclone config`
   - `n` (new remote) → name: **gdrive** → Storage: ketik **drive** (Google Drive)
   - `client_id` & `client_secret`: **kosongkan** (Enter)
   - `scope`: pilih **1** (full access) → Enter sampai pertanyaan *Use web browser to automatically authenticate?* → **y**
   - Peramban terbuka: **masuk dengan akun Google fakultas** → izinkan.
   - *Configure as Shared Drive?* **n** → *Keep this remote?* **y** → `q`.
3. Uji: `rclone lsd gdrive:` (harus tanpa galat) lalu `rclone mkdir gdrive:sipersu-backup`.
4. Isi `.env`: `BACKUP_RCLONE_REMOTE=gdrive:sipersu-backup`, lalu `php artisan config:cache`.
5. Uji: `php artisan backup:run --jenis=mingguan` → berkas muncul di Google Drive.
6. Konfigurasi rclone tersimpan per akun Windows. Karena tugas terjadwal berjalan sebagai **SYSTEM**,
   salin `%APPDATA%\rclone\rclone.conf` ke `C:\Windows\System32\config\systemprofile\AppData\Roaming\rclone\` **atau**
   ubah tugas "SIPERSU Scheduler" agar berjalan sebagai akun Windows yang sama dengan yang memasang rclone.
7. Bila rclone belum ada/diatur, backup mingguan **dilewati tanpa galat**.

### 9.4 Perintah backup

| Perintah | Fungsi |
|---|---|
| `php artisan backup:run --jenis=harian` | Backup sekarang ke HDD eksternal |
| `php artisan backup:run` | Backup manual ke folder internal (`storage\backups`) |
| `php artisan backup:run --tujuan=E:\arsip-sipersu` | Arsip ke flashdisk (atau pakai `scripts\arsip-semester.bat`) |
| `php artisan backup:list` | Riwayat backup |
| `php artisan backup:verify [berkas.zip]` | Periksa checksum + coba buka ZIP |
| `php artisan backup:restore berkas.zip --uji-saja` | **Uji pemulihan** (tidak menimpa data aktif) |
| `php artisan backup:restore berkas.zip` | Pulihkan (ke folder uji → konfirmasi → backup kondisi saat ini → timpa) |

Menu di aplikasi: **Pengaturan → Backup** (hanya Super Admin & Admin TU): tombol *Backup Sekarang*, unduh, riwayat, status.

### 9.5 Uji pemulihan bulanan ⚠️

Backup yang tidak pernah diuji belum tentu bisa dipulihkan. Dasbor Admin menampilkan pengingat **"Lakukan uji pemulihan backup"** tiap 30 hari.
Cara: klik dua kali `scripts\uji-pemulihan.bat`, tarik berkas backup ke jendelanya, Enter. Hasil "Uji pemulihan selesai" = aman.

---

## 10. Pemakaian sehari-hari (ringkas)

- **Mahasiswa**: login dengan NPM → *Pengajuan Surat* → isi formulir → unggah berkas (PDF/JPG/PNG, maks 2 MB) → *Lacak Status* → unduh PDF.
- **Admin TU / Kaprodi**: *Layanan Mahasiswa* → periksa → **Verifikasi** atau **Tolak** (alasan wajib).
- **Wakil Dekan**: *Persetujuan & TTD* → **Paraf** (bila jenis surat memerlukan).
- **Dekan / penandatangan**: *Persetujuan & TTD* → masukkan kata sandi akun → **Setujui & Tanda Tangani** → nomor surat terbit otomatis.
- **Tanggal surat**: pada formulir Surat Keluar ada pemilih **Tanggal surat**. Bila tidak diubah = hari ini (dan mengikuti hari penandatanganan); bila dipilih, **tanggal Hijriah-nya langsung tampil** dan dipakai pada surat, QR, serta **nomor surat** (bulan romawi dan tahun mengikuti tanggal itu). Batas: 30 hari ke belakang sampai 90 hari ke depan.
- **Surat keluar umum**: *Surat Keluar → Buat Surat* → pilih **bentuk surat**:
  - **Surat ber-QR (TTE)** — PDF memuat QR beserta spesimen **tanda tangan + stempel** pejabat, bisa diverifikasi publik.
  - **Surat tanpa QR** — nomor tetap otomatis, PDF disiapkan **kosong** (tanpa QR, tanda tangan, maupun stempel); **dicetak lalu ditandatangani dan dicap secara manual**.
  Untuk surat mahasiswa, bentuk surat diatur per jenis di **Master Data → Jenis Surat → Bentuk surat**.
- **Format Surat (menu khusus Admin TU/Super Admin)**: TU menentukan *surat untuk apa* tanpa menulis kode:
  1. **Format Surat → Tambah Format Surat**, isi nama dan kegunaan, pilih **Dibuat oleh**: *Staf* (muncul di Surat Keluar) atau *Mahasiswa* (muncul di katalog e-Layanan).
  2. **Isian yang harus diisi**: klik *Tambah Isian*, beri nama (contoh "Nama Dosen"), pilih jenis (teks, teks panjang, tanggal, angka, pilihan), wajib/tidak. Urutan bisa digeser.
  3. **Isi surat (templat)**: tulis isi surat, lalu klik tombol nama isian untuk menyisipkannya. Klik *Lihat Pratinjau* untuk melihat hasil dengan data contoh.
  4. Pilih klasifikasi (kode nomor), penandatangan, bentuk surat (ber-QR / tanpa QR), format tanggal ("Dikeluarkan di…" atau tanggal **Hijriah + Masehi**), dan apakah perlu paraf. **Simpan**.
     Jenis isian khusus: **Daftar** (satu per baris, mis. Tembusan → dicetak bernomor) dan **Tabel** (baris berulang dengan kolom yang Anda tentukan, mis. Nama | Program Studi). Tombol **✍ Tanda tangan di sini** menaruh bagian templat *setelah* blok tanda tangan (mis. Tembusan), dan **Blok bersyarat** menyembunyikan teks bila isiannya kosong. Koreksi tanggal Hijriah (±1 hari) ada di Pengaturan → Format Nomor.
  5. Petugas membuat surat lewat **Surat Keluar → Buat Surat → pilih format → isi formulir → Simpan Draf → Ajukan**. Format yang dinonaktifkan tidak lagi tampil; format dapat disalin untuk dijadikan dasar format baru.
- **Menambah banyak pengguna** (Admin TU): **Master Data → Pengguna → Impor CSV** → *Unduh Templat CSV* → isi di Excel → simpan sebagai CSV UTF-8 → unggah → **Periksa** → **Impor**. Format kolom lengkap: `documentation/format-csv-pengguna.md`.
- **Surat masuk** (Admin TU): **Surat Masuk → Catat Surat Masuk** → pilih jenis (format) → isi nomor surat asal, pengirim, tanggal surat/diterima, perihal, sifat, serta kolom khusus jenis itu → unggah pindaian (PDF/JPG/PNG, maks 2 MB) → **Simpan**. **Nomor agenda terbit otomatis** (contoh `AGD-2026/X/0148`, reset tiap tahun; pola dapat diubah di **Pengaturan → Format Nomor**). Surat yang sama (nomor asal + pengirim + tanggal) tidak dapat dicatat dua kali. Dekan, Wakil Dekan, dan Kaprodi dapat melihat daftar (surat *rahasia* hanya TU, Dekan, Wakil Dekan). Kolom tambahan per jenis surat masuk diatur di **Format Surat → tab Surat Masuk**.
- Nomor surat **hanya terbit saat ditandatangani**. Surat yang dibatalkan **tidak** memakai ulang nomornya; QR-nya menampilkan **TIDAK BERLAKU**.

---

## 11. Pemulihan saat laptop rusak (langkah demi langkah)

Siapkan: (a) berkas backup `.zip` terbaru (+ `.sha256`) dari HDD/Google Drive, (b) **kata sandi backup** (bagian 12), (c) salinan folder proyek `sipersu` (tanpa data) — dari GitHub/flashdisk, (d) laptop/PC pengganti.

> Backup berisi **data** (database, berkas, kunci, `.env`), bukan kode aplikasi. Simpan salinan kode proyek terpisah dari data.

1. ☐ Pasang **Laragon** di laptop baru (bagian 1), termasuk PHP 8.2+ dan ekstensinya.
2. ☐ Salin folder proyek ke `C:\laragon\www\sipersu`. Atur **Document Root** (bagian 2).
3. ☐ Salin berkas backup ke laptop baru, misalnya `D:\backup-sipersu\sipersu-2026-10-02-1630.zip`
   (bersama berkas `.sha256`-nya di folder yang sama).
4. ☐ Jalankan `scripts\pasang.bat` sampai selesai (mengisi `.env` baru — **isi `BACKUP_PASSWORD` dengan sandi lama**, memasang pustaka, membuat basis data kosong).
   Data kosong dan kunci baru dari langkah ini **akan ditimpa** oleh pemulihan di langkah 6.
5. ☐ Uji dulu tanpa menimpa apa pun:
   ```
   php artisan backup:restore D:\backup-sipersu\sipersu-2026-10-02-1630.zip --uji-saja --password=SANDI_BACKUP
   ```
   Pastikan tabel ringkasan menampilkan jumlah pengguna/pengajuan/surat yang wajar.
6. ☐ Pulihkan **sungguhan** (termasuk `.env` dan kunci):
   ```
   php artisan backup:restore D:\backup-sipersu\sipersu-2026-10-02-1630.zip --termasuk-env --password=SANDI_BACKUP
   ```
   Ketik `yes` saat diminta konfirmasi. Sistem otomatis membuat backup kondisi saat ini sebelum menimpa.
7. ☐ Sesuaikan `.env` bila alamat berubah (`APP_URL` = IP statis laptop baru). Lalu `php artisan config:cache`.
   Jalankan juga `php artisan kunci:publikasi` — kunci publik di `verifikasi-statis\index.html` **harus sama** dengan sebelumnya
   (kunci lama ikut dipulihkan; bandingkan sidik jarinya dengan halaman verifikasi lama atau GitHub Pages bila dipakai). Bila berbeda, jangan lanjut: pulihkan ulang dengan backup yang benar.
8. ☐ Atur ulang: **IP statis, firewall, anti-sleep** (bagian 5), **Task Scheduler** (bagian 6), HDD eksternal (bagian 9). Bila IP laptop baru berbeda, **ubah catatan DNS statis di router** (bagian 5.3) agar `sipersu.ft.umbuton.ac.id` menuju IP baru.
9. ☐ (Opsional) rclone / Google Drive bila internet tersedia.
10. ☐ Verifikasi hasil:
    - login sebagai Admin TU, buka beberapa pengajuan & surat lama, unduh satu PDF;
    - pindai QR surat **lama** → harus **Dokumen Asli** (membuktikan kunci tanda tangan ikut pulih);
    - **Pengaturan → Backup** → *Backup Sekarang* berhasil.
11. ☐ Catat tanggal pemulihan di buku log fakultas.

---

## 12. SOP penyimpanan kata sandi backup

Kata sandi backup (`BACKUP_PASSWORD`) adalah **kunci semua cadangan**. Tanpa sandi ini backup **tidak bisa dibuka** — oleh siapa pun, termasuk pembuat aplikasi. Karena `.env` ikut tersimpan di dalam backup, sandi **tidak boleh hanya ada di `.env`** (itu seperti menyimpan kunci di dalam brankas yang terkunci).

1. Buat sandi **panjang (≥ 16 karakter)**, acak, mis. 4–5 kata acak + angka. Jangan memakai tanggal lahir/nama fakultas.
2. Tulis sandi di kertas, masukkan **amplop tertutup**, tanda tangan di lipatan, simpan di **brankas Dekanat**.
3. Buat **satu salinan lagi** di tempat berbeda (misal: Wakil Dekan II atau pimpinan universitas) — **minimal 2 orang berbeda** mengetahui lokasi salinan.
4. Simpan juga di **pengelola kata sandi** milik fakultas (mis. Bitwarden) dengan 2 pemilik akun.
5. **Jangan** mengirim sandi lewat WhatsApp/email biasa, dan **jangan** menempelnya di komputer server.
6. Salinan kunci tanda tangan (`kunci:cadangkan`) disimpan dengan cara yang sama, **terpisah dari** sandi backup.
7. Mengganti sandi: ubah `BACKUP_PASSWORD` di `.env` → backup baru memakai sandi baru. **Backup lama tetap memakai sandi lama** — jangan buang catatan sandi lama selama backup lamanya masih disimpan. Catat tanggal pergantian pada amplop.
8. Setiap **uji pemulihan bulanan**, pastikan sandi di amplop **benar-benar bisa membuka** backup (bukan sandi dari ingatan).
9. Saat pejabat pemegang amplop berganti jabatan, serahkan amplop dengan berita acara dan ganti sandi.

---

## 13. Pemecahan masalah

| Gejala | Penyebab & solusi |
|---|---|
| Halaman putih / error 500 | Lihat `storage\logs\laravel.log`. Pastikan `.env` ada, `storage\` bisa ditulis, jalankan `php artisan config:clear`. |
| HP/laptop lain tidak bisa membuka | Cek IP server (`ipconfig`), firewall (`buka-firewall.bat`), perangkat satu jaringan, profil jaringan **Private**. |
| "Aplikasi ini hanya dapat diakses dari jaringan lokal" | IP perangkat di luar rentang privat. Atur `LAN_CIDRS` di `.env` (mis. `10.20.0.0/16`) lalu `php artisan config:cache`. |
| Banner merah backup | Cek HDD tercolok & huruf drive sama dengan `BACKUP_LOCAL_PATH`; lihat **Pengaturan → Backup → Riwayat**. |
| "BACKUP_PASSWORD belum diisi" | Isi di `.env` (≥ 8 karakter) lalu `php artisan config:cache`. |
| Email tidak terkirim | Pastikan `MAIL_*` benar dan tugas scheduler berjalan (cek `storage\logs\scheduler.log`). Gmail butuh *Sandi Aplikasi*. |
| `sipersu.ft.umbuton.ac.id` tidak terbuka, tetapi IP terbuka | DNS statis di router belum benar, atau perangkat memakai Private DNS/Secure DNS (bagian 5.3). Pakai alamat IP sementara. |
| Muncul 403 "hanya dapat diakses dari jaringan lokal" | Perangkat berada di luar jaringan lokal (mis. data seluler atau VPN). Sambungkan ke Wi-Fi fakultas. |
| QR tidak membuka halaman verifikasi | Perangkat harus berada di jaringan fakultas (selama `VERIFIKASI_URL` kosong), atau terbitkan halaman statis di internet (bagian 8). |
| QR menampilkan "Tidak ditemukan" | Surat berstatus draf/tanpa QR, atau QR dari server lain. Surat **tanpa QR** memang tidak punya halaman verifikasi. |
| Setelah mengubah `.env` tidak berpengaruh | Jalankan `php artisan config:cache` (atau `config:clear`). |

---

## 14. Memperbarui aplikasi

1. Lakukan **Backup Sekarang** (bagian 9).
2. Ganti isi folder proyek dengan versi baru — **jangan** menimpa `.env`, `database\sipersu.sqlite`, `storage\app`, `storage\keys`.
3. Jalankan `scripts\pasang.bat` lagi (aman diulang: hanya menjalankan migrasi baru, tidak menimpa data).

---

## Lampiran A — Daftar perintah artisan

| Perintah | Fungsi |
|---|---|
| `kunci:buat [--force]` | Membuat pasangan kunci Ed25519 (**`--force` membuat QR lama tidak valid offline**) |
| `kunci:cadangkan <folder>` | Menyalin kunci ke folder lain (flashdisk/brankas) |
| `kunci:publikasi` | Membuat `verifikasi-statis\index.html` (halaman verifikasi QR) |
| `dokumentasi:qrcode` | Membuat ulang `documentation\penjelasan-qrcode.pdf` (penjelasan QR + referensi) |
| `dokumentasi:presentasi-qr` | Membuat ulang `documentation\presentasi-qr-persuratan.pdf` (15 slide) |
| `dokumentasi:contoh-surat` | Membuat ulang 14 PDF contoh surat |
| `backup:run / list / verify / restore / periksa` | Lihat bagian 9.3 |
| `schedule:run` | Dipanggil Task Scheduler tiap menit |
| `config:cache` | Terapkan perubahan `.env` |

## Lampiran B — Keamanan yang sudah diterapkan

- Login NPM/NIDN + kata sandi (di-hash), pembatasan 5 percobaan/menit, sesi di basis data.
- Peran & kewenangan diperiksa di server (Super Admin tidak dapat menandatangani; Kaprodi hanya prodi sendiri).
- Unggahan hanya PDF/JPG/PNG ≤ 2 MB, disimpan di folder **privat** (tidak bisa dibuka lewat URL langsung).
- Aplikasi hanya terbuka untuk IP privat/loopback/Tailscale; **semua** IP lain ditolak 403 (tanpa pengecualian, tanpa Cloudflare Tunnel/VPS). Header proksi diabaikan sehingga tidak dapat dipalsukan.
- `APP_DEBUG=false` untuk produksi. Semua aksi penting dan setiap pemindaian QR tercatat di **Pengaturan → Log Aktivitas**.
