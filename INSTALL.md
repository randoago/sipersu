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
| Akses pengguna | Dari perangkat lain di jaringan kampus: `http://192.168.1.10` (contoh) |
| Internet | Hanya dipakai untuk: pemasangan awal, email, Google Drive (backup mingguan), dan verifikasi QR publik |
| Cadangan | Harian ke HDD eksternal, mingguan ke Google Drive |

**Yang disiapkan sebelum mulai**

- ☐ Laptop/PC server dengan akun Windows **Administrator**
- ☐ Folder proyek `sipersu` (dari flashdisk / GitHub / ZIP)
- ☐ HDD eksternal atau flashdisk besar (≥ 16 GB) khusus backup
- ☐ Akun Google fakultas (untuk backup mingguan) — opsional tetapi sangat disarankan
- ☐ Akun email untuk pengirim notifikasi (contoh: Gmail fakultas) — opsional
- ☐ Akun Cloudflare gratis dan sebuah domain (contoh: `umbuton.ac.id`) — untuk QR publik
- ☐ Akun GitHub gratis — untuk halaman verifikasi offline

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
APP_PUBLIC_URL=https://verifikasi.umbuton.ac.id   # alamat PUBLIK untuk QR (bagian 7)
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
   - membuat berkas `verifikasi-offline\verifikasi.html`.
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

### 5.3 Alamat nama lokal: `sipersu.ft.umbuton.ac.id`

Agar pengguna cukup mengetik `http://sipersu.ft.umbuton.ac.id` (bukan angka IP), nama itu harus mengarah ke IP server **hanya di jaringan lokal**. Pilih salah satu:

**A. Disarankan: catatan DNS internal.** Minta pengelola jaringan/DNS kampus (mis. UPT TIK) menambahkan catatan **A**:

| Nama | Tipe | Nilai |
|---|---|---|
| `sipersu.ft.umbuton.ac.id` | A | `192.168.1.10` (IP statis server) |

Catatan ini cukup ada di DNS **internal** kampus; tidak perlu dibuka ke internet (alamat IP lokal memang tidak dapat dipakai dari luar).

**B. Cadangan, tanpa pengelola DNS: berkas `hosts` di tiap komputer pengguna.**
1. Buka Notepad **sebagai Administrator**, lalu buka `C:\Windows\System32\drivers\etc\hosts`.
2. Tambahkan satu baris di paling bawah, lalu simpan:
   ```
   192.168.1.10   sipersu.ft.umbuton.ac.id
   ```

**Lalu di server:**
1. Isi `.env`: `APP_URL=http://sipersu.ft.umbuton.ac.id`, kemudian `php artisan config:cache`.
2. Uji dari komputer lain di jaringan yang sama: buka `http://sipersu.ft.umbuton.ac.id` → muncul halaman masuk. Alamat `http://192.168.1.10` tetap berfungsi.

> Alamat ini **hanya `http`** dan **hanya untuk jaringan lokal**; aplikasi tetap menolak akses dari luar jaringan lokal. Alamat publik untuk QR adalah hal terpisah (`verifikasi.umbuton.ac.id`, bagian 7).

### 5.4 Nonaktifkan sleep

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
| Jumat **17.30** | Backup ke Google Drive via rclone (simpan 12 terakhir; dilewati bila belum dikonfigurasi) |

Uji: jalankan `scripts\backup-sekarang.bat` lalu lihat di **Pengaturan → Backup** pada aplikasi.

---

## 7. Verifikasi QR dari internet (Cloudflare Tunnel)

Server ada di jaringan lokal dan **tidak bisa** diakses dari luar. Dengan **Cloudflare Tunnel**, **hanya** alamat `/v/…` (halaman verifikasi QR) yang dibuka ke internet; halaman lain tetap hanya untuk jaringan lokal. Ada dua lapis pengaman: aturan di Cloudflare dan pemeriksaan di dalam aplikasi.

1. Daftar/masuk ke <https://dash.cloudflare.com> dan siapkan domain untuk alamat publik verifikasi, yaitu `verifikasi.umbuton.ac.id`.
   > **Koordinasikan dengan pengelola DNS `umbuton.ac.id`** (mis. UPT TIK universitas). Agar hostname tunnel bisa dibuat, domain/zone-nya harus aktif di Cloudflare: mintalah pengelola DNS menambahkan `umbuton.ac.id` ke akun Cloudflare, atau menyiapkan subdomain `verifikasi` sesuai kebijakan mereka. Bila tidak memungkinkan, pakai domain lain milik fakultas dan cukup ubah `APP_PUBLIC_URL`.
2. Buka **Zero Trust → Networks → Tunnels → Create a tunnel → Cloudflared**. Beri nama `sipersu`.
3. Pilih **Windows**, salin dan jalankan perintah yang ditampilkan di *Command Prompt (Administrator)*. Perintah ini memasang **cloudflared sebagai Windows Service** sehingga berjalan otomatis.
4. Tab **Public Hostname → Add**:
   - Subdomain/Domain: `verifikasi` + `umbuton.ac.id`
   - **Path:** `^/(v/.*|css/.*|fonts/.*|images/.*|js/.*)$`   *(hanya ini yang boleh lewat)*
   - Service: **HTTP** → `localhost:80`
5. (Disarankan) Tambahkan satu aturan lagi di bawahnya: hostname yang sama, path kosong, Service **HTTP Status → 404**, agar jalur lain ditolak oleh Cloudflare.
6. Isi `.env`:

```ini
APP_PUBLIC_URL=https://verifikasi.umbuton.ac.id
```
   lalu jalankan `php artisan config:cache`.
7. **Uji:**
   - Dari HP dengan **data seluler** (bukan Wi-Fi kampus), buka `https://verifikasi.umbuton.ac.id/login` → harus **404**.
   - Terbitkan satu surat ber-QR, pindai QR-nya dengan HP (data seluler) → halaman **Dokumen Asli** tampil.
8. Perhatikan: hanya surat yang diterbitkan **setelah** `APP_PUBLIC_URL` benar yang QR-nya menuju alamat publik. QR pada surat lama tidak berubah sendiri.

> Bila internet/server mati, QR tidak bisa dibuka — gunakan **verifikasi offline** (bagian 8).

---

## 8. Verifikasi offline (GitHub Pages, gratis)

> Ingin memahami cara kerja tanda tangan elektronik, kunci, dan QR? Baca `documentation/cara-kerja-tanda-tangan-elektronik.md`.

Setiap QR memuat tanda tangan digital (Ed25519). Berkas `verifikasi.html` (kunci publik sudah tertanam) dapat memeriksanya **tanpa server fakultas**.

1. Pastikan berkas ada: `C:\laragon\www\sipersu\verifikasi-offline\verifikasi.html`
   (dibuat otomatis oleh `pasang.bat`; ulangi dengan `php artisan kunci:publikasi`).
2. Di <https://github.com> buat repositori **publik** baru, misalnya `verifikasi-ft-umb`.
3. Klik **Add file → Upload files**, unggah `verifikasi.html` dan ubah namanya menjadi **`index.html`** (agar alamat lebih pendek).
4. **Settings → Pages → Source: Deploy from a branch → Branch: main / (root) → Save**.
5. Setelah ±1 menit alamatnya menjadi `https://NAMA-ANDA.github.io/verifikasi-ft-umb/`.
6. Isi `.env`: `VERIFIKASI_OFFLINE_URL=https://NAMA-ANDA.github.io/verifikasi-ft-umb/` lalu `php artisan config:cache`.
7. **Uji:** salin tautan QR sebuah surat (hasil pindai), tempel di halaman itu → **Dokumen Asli**. Ubah satu huruf → **Tidak Valid**.

> Berkas ini hanya berisi **kunci publik** — aman dipublikasikan. **Kunci privat** (`storage\keys\ed25519.secret`) tidak pernah boleh keluar dari server dan cadangan terenkripsi.
> Bila kunci dibuat ulang (`kunci:buat --force`), `verifikasi.html` harus diunggah ulang dan QR lama **tidak lagi valid secara offline**.

---

## 9. Backup (aturan 3-2-1)

3 salinan data · 2 media berbeda · 1 di luar lokasi.
**Salinan 1** = data aktif di server, **2** = HDD eksternal (harian), **3** = Google Drive (mingguan).

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

### 9.2 Google Drive dengan rclone (backup mingguan)

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

### 9.3 Perintah backup

| Perintah | Fungsi |
|---|---|
| `php artisan backup:run --jenis=harian` | Backup sekarang ke HDD eksternal |
| `php artisan backup:run` | Backup manual ke folder internal (`storage\backups`) |
| `php artisan backup:list` | Riwayat backup |
| `php artisan backup:verify [berkas.zip]` | Periksa checksum + coba buka ZIP |
| `php artisan backup:restore berkas.zip --uji-saja` | **Uji pemulihan** (tidak menimpa data aktif) |
| `php artisan backup:restore berkas.zip` | Pulihkan (ke folder uji → konfirmasi → backup kondisi saat ini → timpa) |

Menu di aplikasi: **Pengaturan → Backup** (hanya Super Admin & Admin TU): tombol *Backup Sekarang*, unduh, riwayat, status.

### 9.4 Uji pemulihan bulanan ⚠️

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
   Jalankan juga `php artisan kunci:publikasi` — hasilnya **harus sama** dengan `verifikasi.html` yang sudah ada di GitHub Pages
   (kunci publik identik karena kunci lama ikut dipulihkan). Bila berbeda, jangan lanjut: pulihkan ulang dengan backup yang benar.
8. ☐ Atur ulang: **IP statis, firewall, anti-sleep** (bagian 5), **Task Scheduler** (bagian 6), HDD eksternal & rclone (bagian 9).
9. ☐ **Cloudflare Tunnel**: pasang ulang `cloudflared` di laptop baru dengan token tunnel yang sama (Zero Trust → Tunnels → *sipersu* → Configure → perintah pemasangan Windows).
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
| QR dibuka dari internet tidak jalan | Cek layanan `cloudflared` berjalan (Services.msc), `APP_PUBLIC_URL`, dan aturan path di Cloudflare (bagian 7). |
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
| `kunci:publikasi` | Membuat `verifikasi-offline\verifikasi.html` |
| `backup:run / list / verify / restore / periksa` | Lihat bagian 9.3 |
| `schedule:run` | Dipanggil Task Scheduler tiap menit |
| `config:cache` | Terapkan perubahan `.env` |

## Lampiran B — Keamanan yang sudah diterapkan

- Login NPM/NIDN + kata sandi (di-hash), pembatasan 5 percobaan/menit, sesi di basis data.
- Peran & kewenangan diperiksa di server (Super Admin tidak dapat menandatangani; Kaprodi hanya prodi sendiri).
- Unggahan hanya PDF/JPG/PNG ≤ 2 MB, disimpan di folder **privat** (tidak bisa dibuka lewat URL langsung).
- Aplikasi hanya terbuka untuk IP jaringan lokal; dari internet **hanya** `/v/*`.
- `APP_DEBUG=false` untuk produksi. Semua aksi penting dan setiap pemindaian QR tercatat di **Pengaturan → Log Aktivitas**.
