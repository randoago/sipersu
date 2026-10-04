# Cara Kerja Tanda Tangan Elektronik SIPERSU

Dokumen ini menjelaskan, dengan bahasa sederhana, **bagaimana surat ber-QR dibuktikan asli**, apa yang dilindungi, apa yang **tidak**, dan bagaimana kunci harus dijaga.

> **Satu hal penting sejak awal:** yang dipakai untuk surat ber-QR adalah **tanda tangan digital (Ed25519)**, bukan "enkripsi". Isi surat **tidak dirahasiakan** — siapa pun yang memegang PDF-nya bisa membacanya. Yang dijamin adalah **keaslian**: surat ini benar-benar disahkan oleh fakultas dan datanya tidak diubah.
> Enkripsi sungguhan (AES-256) dipakai di tempat lain: **backup** (lihat bagian 10).

---

## 1. Gambaran dalam satu menit

Bayangkan **stempel yang tidak bisa dipalsukan**:

- Fakultas punya **satu pasang kunci**: **kunci privat** (rahasia, hanya ada di server) dan **kunci publik** (boleh dilihat siapa saja).
- Saat Dekan menandatangani surat, server **"membubuhkan segel"** pada ringkasan data surat memakai **kunci privat**. Segel ini disebut **tanda tangan digital** (*signature*).
- Segel itu dicetak di **kode QR** pada surat.
- Siapa pun yang memindai QR dapat **memeriksa segel memakai kunci publik**. Jika satu huruf saja pada data diubah, pemeriksaan **gagal**.
- Orang yang tidak punya kunci privat **tidak mungkin** membuat segel yang lolos pemeriksaan.

```
  KUNCI PRIVAT (rahasia, di server)        KUNCI PUBLIK (terbuka)
          │                                        │
   membuat segel                             memeriksa segel
          ▼                                        ▼
  [data surat] ──► SEGEL (64 byte) ──► QR ──► "ASLI" atau "TIDAK VALID"
```

---

## 2. Empat istilah yang sering tertukar

| Istilah | Tujuan | Bisa dibalik? | Dipakai di SIPERSU untuk |
|---|---|---|---|
| **Enkripsi** (AES-256) | Merahasiakan isi | Ya, dengan kata sandi/kunci | **Backup** ZIP (`BACKUP_PASSWORD`) |
| **Tanda tangan digital** (Ed25519) | Membuktikan **keaslian & keutuhan** | Tidak (hanya bisa diperiksa) | **QR pada surat ber-QR** |
| **Hash** (SHA-256) | "Sidik jari" data; berubah total bila data berubah | Tidak | Ringkasan isi surat, hash PDF, checksum backup |
| **Hash kata sandi** (bcrypt) | Menyimpan kata sandi tanpa bisa dibaca | Tidak | Kata sandi akun pengguna |

Jadi: **QR = tanda tangan digital + hash**, bukan enkripsi.

---

## 3. Pasangan kunci

| | Kunci privat | Kunci publik |
|---|---|---|
| Fungsi | **Membuat** tanda tangan | **Memeriksa** tanda tangan |
| Lokasi | `storage\keys\ed25519.secret` | `storage\keys\ed25519.public` dan tertanam di `verifikasi.html` |
| Ukuran | 64 byte (disimpan sebagai base64, 88 karakter) | 32 byte (44 karakter) |
| Rahasia? | **YA, mutlak** | Tidak (aman dipublikasikan) |
| Dibuat oleh | `php artisan kunci:buat` (sekali, saat instalasi) | ikut dibuat bersamaan |

- Algoritma: **Ed25519** (bawaan PHP lewat ekstensi `sodium`/libsodium) — modern, cepat, dan dipakai luas (SSH, TLS, Signal). Tanda tangannya selalu **64 byte** (±86 karakter teks).
- Berkas kunci privat dibatasi hak akses (`chmod 600` pada sistem yang mendukung) dan **ikut dicadangkan** dalam ZIP backup terenkripsi.
- Kunci publik contoh dari server pengembangan: `Zds45eiDOsPj9RdUfsgxuT7ClMg1Hom8m4VCmoLbCuI=`
  (server fakultas Anda punya pasangan kunci sendiri yang **berbeda**).

---

## 4. Apa yang terjadi saat Dekan menekan "Setujui & Tanda Tangani"

1. **Siapa yang boleh?** Hanya **pemegang jabatan penandatangan** yang aktif (mis. Dekan). Super Admin dan Admin TU **tidak bisa** menandatangani. Dekan harus memasukkan **kata sandi akun** sebagai konfirmasi (ini kontrol akses, bukan bagian dari perhitungan kriptografi).
2. **Transaksi basis data dimulai** — semua langkah di bawah berhasil bersama atau dibatalkan bersama.
3. **Nomor surat terbit** (`001/II.1.AK/FT-UMB/X/2026`) — hanya saat ini, tidak pernah lebih awal, tidak pernah ganda.
4. **Token acak** dibuat: 43 karakter (`PR8pNDuDVCSF4gmXzcQNnqy38FfiSS7DSIol8R2LpC7`) — bukan nomor berurutan, tidak bisa ditebak.
5. **Payload** disusun (lihat bagian 5) lalu **ditandatangani dengan kunci privat** → *signature* 64 byte.
6. **QR dibuat** berisi: alamat verifikasi + payload + signature (bagian 6).
7. **PDF dirender** (kop, isi, QR, spesimen tanda tangan + stempel, lembar riwayat).
8. **Hash SHA-256 PDF final** dihitung dan disimpan di basis data.
9. Transaksi selesai (`COMMIT`). Aksi dicatat di **Log Aktivitas**.

Surat **tanpa QR** melewati langkah 1–3, 7, dan 8: nomor tetap terbit dan PDF tetap dibuat, tetapi **tidak ada token, signature, maupun QR**; PDF dibiarkan kosong untuk tanda tangan dan stempel manual.

---

## 5. Apa persisnya yang ditandatangani (payload)

Payload adalah data ringkas berformat JSON. Ini contoh **nyata** dari sistem pengembangan:

```json
{"v":1,
 "n":"001/II.1.AK/FT-UMB/X/2026",
 "p":"Surat Keterangan Aktif Kuliah a.n. La Ode Rizky",
 "s":"Agusman, S.T., MM.",
 "j":"Dekan Fakultas Teknik",
 "t":"2026-10-04",
 "h":"5e9cb409ca706be8940df51c001fd2e1fecbd43dc0095bfe4ba74ad22ea70ebb"}
```

| Kunci | Isi | Fungsi |
|---|---|---|
| `v` | Versi format (1) | Kompatibilitas di masa depan |
| `n` | Nomor surat | Mengikat tanda tangan ke **nomor ini** |
| `p` | Perihal | Mengikat ke perihal |
| `s` | Nama penanda tangan | Siapa yang mengesahkan |
| `j` | Jabatan | Dalam kapasitas apa |
| `t` | Tanggal surat | Kapan |
| `h` | **SHA-256 isi surat** | Mengikat tanda tangan ke **isi/teks surat** |

`h` dihitung dari gabungan: nomor + perihal + tanggal + **seluruh teks isi surat** (tanpa format HTML). Artinya, mengubah satu kata pada isi surat membuat `h` berbeda, sehingga tanda tangan tidak lagi cocok.

Yang ditandatangani adalah **byte persis dari teks JSON di atas**. Mengubah satu karakter di mana pun membuat pemeriksaan gagal.

---

## 6. Isi QR (anatomi tautan)

```
https://verifikasi.umbuton.ac.id/v/PR8pNDuDVCSF4gmXzcQNnqy38FfiSS7DSIol8R2LpC7#eyJ2IjoxLCJu…IiwidCI6…ifQ.C0hioENjzZTKOz_NYM0It…
└───────────────┬──────────────┘└──────────────┬───────────────────────────┘ └────────┬────────┘ └─────────┬────────┘
  alamat publik (APP_PUBLIC_URL)         token acak 43 karakter             payload (base64url)     signature (base64url)
```

- Bagian sebelum `#` dibaca **server** (untuk verifikasi online).
- Bagian setelah `#` (**fragment**) **tidak pernah dikirim browser ke server** — dipakai untuk verifikasi **offline** dan tidak muncul di log server.
- Panjang total ±460 karakter; kode QR berukuran sedang, tetap terbaca ponsel.

---

## 7. Dua cara memeriksa

### A. Verifikasi online — `https://…/v/{token}`

Dipakai bila server menyala dan Cloudflare Tunnel aktif.

1. Server mencari surat berdasarkan **token**.
2. Server **menyusun ulang payload dari basis data** lalu **memeriksa signature** dengan kunci publik.
   → Bila data surat di basis data diubah diam-diam, **pemeriksaan gagal** ("Tanda Tangan Tidak Cocok").
3. Menampilkan: **Dokumen Asli**, atau **TIDAK BERLAKU** (bila surat dibatalkan), plus metadata (nomor, perihal, penanda tangan, waktu) dan **Riwayat Surat**.
4. Form **Cek Integritas Berkas PDF**: unggah PDF → server menghitung SHA-256 dan membandingkannya dengan hash PDF final yang tersimpan → **cocok / tidak cocok**.
5. Setiap pemindaian **dicatat** di Log Aktivitas. Surat berklasifikasi *rahasia* hanya menampilkan metadata minimal.

### B. Verifikasi offline — `verifikasi.html`

Dipakai bila server/internet fakultas mati.

1. Berkas statis (HTML + JavaScript murni), dihosting gratis (mis. GitHub Pages). **Kunci publik tertanam** di dalamnya.
2. Pengguna menempelkan tautan QR. Peramban membaca bagian setelah `#`, memeriksa signature **di komputer pengguna sendiri** — tidak ada data yang dikirim ke mana pun.
3. Hasil: **Dokumen Asli** (dengan data dari payload) atau **Tidak Valid**.
4. Halaman menampilkan **sidik jari kunci publik** (SHA-256). Fakultas sebaiknya mengumumkan sidik jari ini; bila berbeda, jangan percaya hasil halaman itu.

| | Online | Offline |
|---|---|---|
| Butuh server fakultas | Ya | **Tidak** |
| Tahu surat **dibatalkan** | **Ya** | Tidak |
| Cek PDF dengan unggah | **Ya** | Tidak |
| Privasi | Pemindaian tercatat di server | Tidak ada data keluar |

---

## 8. Dua "hash" yang berbeda (dan alasannya)

| Hash | Dihitung dari | Disimpan di | Membuktikan |
|---|---|---|---|
| `h` (di dalam payload/QR) | **Isi/teks surat** + nomor + perihal + tanggal | Dalam QR (ikut ditandatangani) | Isi surat yang disahkan tidak diubah |
| `pdf_hash` | **Berkas PDF final** (semua byte) | Basis data | PDF yang Anda pegang identik dengan PDF yang diterbitkan |

Mengapa tidak satu hash saja? Karena **QR berada di dalam PDF**. PDF final tidak bisa menyimpan hash-nya sendiri di dalam QR-nya (hash berubah begitu QR ditambahkan — pola "telur dan ayam"). Solusinya: QR memuat hash **isi**, sedangkan hash **PDF final** disimpan di server dan diperiksa lewat unggah di halaman verifikasi online.

---

## 9. Apa yang dilindungi dan apa yang TIDAK

**Terdeteksi (pemeriksaan gagal):**
- Nomor, perihal, penanda tangan, jabatan, tanggal, atau **isi surat** diubah.
- Payload atau signature pada QR diubah/dirusak.
- Data surat di basis data diubah tanpa tanda tangan ulang.
- Tanda tangan dibuat pihak lain tanpa kunci privat fakultas.
- PDF diubah (lewat fitur **Cek Integritas Berkas PDF**).

**TIDAK dijamin / perlu kewaspadaan:**
- **Kerahasiaan isi.** Isi PDF tidak dienkripsi.
- **Menyalin QR asli ke surat palsu.** QR asli tetap "asli" — tetapi halaman verifikasi menampilkan **data surat yang sebenarnya**. Pemeriksa harus **mencocokkan nomor, perihal, dan nama** pada surat yang dipegang dengan yang tampil di halaman verifikasi, dan sebaiknya mengunggah PDF-nya untuk uji hash.
- **Pembatalan saat offline.** Surat yang sudah dibatalkan tetap lolos pemeriksaan offline; hanya halaman online yang menampilkan "TIDAK BERLAKU".
- **Waktu tanda tangan.** Yang ditandatangani hanya **tanggal** (`t`); jam pada halaman verifikasi berasal dari basis data.
- **Bocornya kunci privat.** Siapa pun yang memegangnya dapat menerbitkan tanda tangan palsu yang tampak sah (lihat bagian 11).
- **Hasil cetak/foto/pindaian.** Tidak ada perlindungan bila surat dicetak lalu dipindai ulang dengan isi diubah — selalu verifikasi lewat QR.

---

## 10. Enkripsi dan penyamaran di bagian lain sistem

| Hal | Cara | Catatan |
|---|---|---|
| **Backup** | ZIP **AES-256** dengan `BACKUP_PASSWORD` + checksum SHA-256 | Kehilangan sandi = backup **tidak bisa dibuka** siapa pun (SOP: INSTALL.md bagian 12) |
| **Kata sandi akun** | **bcrypt** (hash satu arah) | Admin tidak dapat melihatnya, hanya mengatur ulang |
| **Lampiran & PDF** | Disimpan di folder **privat** (tidak bisa diakses lewat URL langsung) | **Tidak dienkripsi** di disk — lindungi server (lihat saran BitLocker di bawah) |
| **Lalu lintas jaringan** | Akses publik `/v/*` lewat **HTTPS** Cloudflare Tunnel; di jaringan lokal memakai HTTP biasa | Pertimbangkan HTTPS lokal bila jaringan tidak dipercaya |

---

## 11. Menjaga kunci (inilah "nyawa" sistem ini)

**Aturan emas**
1. **Kunci privat tidak boleh keluar dari server** kecuali dalam backup terenkripsi atau salinan `kunci:cadangkan` yang disimpan aman.
2. Cadangkan segera setelah instalasi: `php artisan kunci:cadangkan D:\kunci-sipersu` → simpan di brankas, **terpisah** dari sandi backup.
3. **Jangan** menjalankan `php artisan kunci:buat --force` kecuali benar-benar perlu: kunci baru membuat **semua QR lama tidak valid secara offline**.
4. Aktifkan **BitLocker** (enkripsi disk Windows) pada laptop server agar berkas kunci tidak terbaca bila laptop dicuri.
5. Batasi akun Administrator Windows; kunci privat dapat dibaca siapa pun yang menguasai server.

**Bila kunci diduga bocor**
1. Hentikan penerbitan surat ber-QR sementara.
2. `php artisan kunci:buat --force` (membuat pasangan baru).
3. `php artisan kunci:publikasi` lalu unggah ulang `verifikasi.html` ke GitHub Pages; umumkan sidik jari kunci baru.
4. Surat lama: verifikasi **online** tetap menunjukkan data sebenarnya dari basis data, tetapi pemeriksaan tanda tangan terhadap kunci baru akan gagal untuk surat yang ditandatangani kunci lama. Tentukan kebijakan (mis. terbitkan ulang surat penting) sebelum melakukannya.

**Pemulihan saat laptop rusak:** pulihkan dari backup dengan `--termasuk-env` → kunci ikut kembali, sehingga **QR lama tetap valid**. Lihat INSTALL.md bagian 11.

---

## 12. Status hukum — penting dibaca

Tanda tangan ini memakai **kunci milik fakultas sendiri**. Ini **bukan** tanda tangan elektronik tersertifikasi oleh Penyelenggara Sertifikasi Elektronik (mis. **BSrE/BSSN**). Artinya:

- Sistem ini membuktikan **keaslian secara teknis** (siapa pun dapat memeriksanya), tetapi **tidak otomatis** memiliki kekuatan hukum yang sama dengan TTE tersertifikasi.
- Untuk dokumen yang menuntut keabsahan hukum formal (kontrak, ijazah, dokumen yang diserahkan ke instansi pemerintah dengan syarat TTE tersertifikasi), tanyakan ke bagian hukum/universitas — mungkin diperlukan **sertifikat BSrE** atau tanda tangan basah + cap.
- Karena itu SIPERSU menyediakan **dua bentuk**: **ber-QR** (digital) dan **tanpa QR** (dicetak, tanda tangan basah + stempel manual).

---

## 13. Coba sendiri (belajar)

**a. Dari server** (di folder proyek), tempel tautan QR sebuah surat:

```
php artisan tte:periksa "https://…/v/TOKEN#PAYLOAD.SIGNATURE"
```

Contoh hasil (surat asli):

```
Payload (isi yang ditandatangani):
  {"v":1,"n":"001/II.1.AK/FT-UMB/X/2026","p":"Surat Keterangan Aktif Kuliah a.n. La Ode Rizky",…}
Signature: 64 byte (86 karakter base64url)
Kunci publik: Zds45eiDOsPj9RdUfsgxuT7ClMg1Hom8m4VCmoLbCuI=
✔ DOKUMEN ASLI — tanda tangan cocok dengan kunci publik fakultas.
```

**b. Uji kerusakan:** ubah **satu huruf** pada tautan lalu jalankan lagi →
`✘ TIDAK VALID — tanda tangan tidak cocok`

**c. Dari peramban, tanpa server:** buka `verifikasi.html` (lokal atau GitHub Pages), tempel tautan QR, klik **Periksa Keaslian**. Ubah satu huruf dan lihat hasilnya berubah menjadi **Tidak Valid**.

**d. Dari kode PHP** (inti pemeriksaannya hanya satu baris):

```php
sodium_crypto_sign_verify_detached($signature, $payloadJson, $kunciPublik); // true / false
```

---

## 14. Tanya-jawab

**Apakah surat ber-QR "terenkripsi"?** Tidak. Isinya terbaca; yang dijamin adalah keaslian dan keutuhannya.

**Mengapa Dekan perlu memasukkan kata sandi saat menandatangani?** Supaya orang lain yang kebetulan memakai komputer/sesi Dekan tidak bisa menandatangani. Kata sandi tidak ikut dalam perhitungan tanda tangan; ia hanya "gerbang".

**Bisakah Admin TU memalsukan tanda tangan Dekan?** Dengan sistem berjalan normal: tidak — hanya pemegang jabatan penandatangan yang boleh, dan setiap tanda tangan tercatat di log. Namun siapa pun yang menguasai **server** (akses Administrator Windows) secara teknis dapat membaca kunci privat — karena itu keamanan fisik server, BitLocker, dan log sangat penting.

**Apa bedanya tanda tangan elektronik dengan gambar tanda tangan (spesimen) di surat?** Spesimen hanyalah **gambar** (bisa disalin siapa saja). Yang membuktikan keaslian adalah **QR dengan signature**. Spesimen + stempel dicetak untuk tampilan resmi; QR untuk pembuktian.

**Jika QR rusak/tidak terbaca?** Verifikasi lewat nomor surat ke Tata Usaha, atau unggah PDF di halaman verifikasi (butuh token — minta TU).

**Bagaimana jika Dekan berganti?** Ganti pemegang jabatan di *Master Data → Jabatan & Pejabat* dan unggah spesimen pejabat baru di *Master Data → Spesimen TTD*. Kunci fakultas **tidak** berubah; nama dan jabatan penanda tangan tercatat pada tiap surat saat ditandatangani.

---

## 15. Glosarium

| Istilah | Arti |
|---|---|
| **Enkripsi** | Mengacak data agar tak terbaca tanpa kunci |
| **Hash / SHA-256** | "Sidik jari" data; satu karakter berbeda → sidik jari berbeda total |
| **Kunci privat / publik** | Pasangan kunci: privat untuk membuat tanda tangan, publik untuk memeriksa |
| **Tanda tangan digital / signature** | Hasil perhitungan dengan kunci privat yang membuktikan data asli & utuh |
| **Ed25519** | Algoritma tanda tangan digital modern (signature 64 byte) |
| **Payload** | Data ringkas yang ditandatangani (nomor, perihal, penanda tangan, jabatan, tanggal, hash isi) |
| **base64url** | Cara menulis data biner sebagai teks yang aman untuk tautan |
| **Fragment (`#…`)** | Bagian tautan yang hanya dibaca peramban, tidak dikirim ke server |
| **Token** | Kode acak panjang pada tautan verifikasi |
| **BSrE** | Balai Sertifikasi Elektronik (BSSN): penyedia TTE tersertifikasi nasional |
| **Spesimen** | Gambar tanda tangan (dan stempel) pejabat yang dicetak pada surat |

---

*Kode terkait:* `app/Services/KunciTte.php` (kunci & tanda tangan), `app/Services/TandaTanganService.php` (alur penandatanganan, payload, QR, PDF),
`app/Http/Controllers/VerifikasiController.php` (verifikasi online), `resources/stubs/verifikasi.html` (verifikasi offline),
`app/Console/Commands/TtePeriksa.php` (`tte:periksa`), `app/Services/Backup.php` (enkripsi backup).
