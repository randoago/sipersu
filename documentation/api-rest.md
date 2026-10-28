# REST API SIPERSU (v1, baca saja)

API JSON untuk **membaca** data persuratan dari aplikasi lain (dasbor, laporan, integrasi kampus). Versi ini **tidak mengubah data**: tidak ada endpoint untuk mengajukan, memverifikasi, memaraf, atau menandatangani surat. Semua aksi itu tetap lewat aplikasi web.

Spesifikasi mesin-terbaca: [openapi.yaml](openapi.yaml) (OpenAPI 3.0, dapat dibuka di Swagger Editor atau Postman).

| Hal | Keterangan |
|---|---|
| Alamat dasar | `{APP_URL}/api/v1`, mis. `http://sipersu.ft.umbuton.ac.id/api/v1` |
| Format | JSON (UTF-8). Kirim `Accept: application/json` |
| Autentikasi | Token Bearer pribadi (Laravel Sanctum) |
| Hak akses | **Sama dengan aplikasi web**: peran, prodi, dan kerahasiaan surat |
| Jaringan | Hanya jaringan lokal fakultas (IP lain mendapat 403), seperti seluruh aplikasi |
| Batas laju | 120 permintaan/menit per pengguna; 20/menit untuk pembuatan token |
| Masa berlaku token | 7 hari (`API_TOKEN_MENIT` di `.env`, bawaan 10080) |

---

## 1. Memulai

### 1.1 Pemasangan (sekali, oleh pengelola)

REST API memakai tabel token baru. Setelah memperbarui kode, jalankan di server:

```bash
composer install
php artisan migrate        # membuat tabel personal_access_tokens
php artisan config:cache
```

Tidak ada pengaturan lain. Lakukan **backup** sebelum `migrate` seperti biasa.

### 1.2 Mendapatkan token

```bash
curl -X POST http://sipersu.ft.umbuton.ac.id/api/v1/auth/token \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"nomor_induk":"TU","password":"********","nama_perangkat":"dasbor-fakultas"}'
```

Respons `201`:

```json
{
  "token": "1|lyW18JJeAE…",
  "token_type": "Bearer",
  "kedaluwarsa_pada": "2026-11-04T16:50:33+08:00",
  "pengguna": {
    "id": 2,
    "nomor_induk": "198701012010011001",
    "nama": "Admin Tata Usaha",
    "nama_lengkap": "Admin Tata Usaha",
    "peran": ["admin_tu"],
    "prodi": null
  }
}
```

- `nomor_induk` menerima NPM, NIDN, atau username (mis. `TU`), sama seperti halaman masuk. Huruf besar-kecil username tidak dibedakan.
- `nama_perangkat` (opsional, maks 60 karakter) menamai token. Membuat token dengan nama yang sama **mengganti** token lama.
- **Token hanya ditampilkan sekali.** Simpan di tempat aman; perlakukan seperti kata sandi.
- Salah 5 kali dalam 1 menit (per akun dan alamat IP) mengembalikan `429` dengan header `Retry-After`.

### 1.3 Memakai token

```bash
curl http://sipersu.ft.umbuton.ac.id/api/v1/me \
  -H "Accept: application/json" -H "Authorization: Bearer 1|lyW18JJeAE…"
```

### 1.4 Mencabut token

```bash
curl -X DELETE http://sipersu.ft.umbuton.ac.id/api/v1/auth/token \
  -H "Accept: application/json" -H "Authorization: Bearer 1|lyW18JJeAE…"      # 204 tanpa isi
```

Token juga tidak berlaku bila akun dinonaktifkan (token dihapus pada permintaan berikutnya) atau melewati masa berlakunya. Pembuatan dan pencabutan token dicatat di **Pengaturan → Log Aktivitas** (`api_token_dibuat`, `api_token_dicabut`, `api_login_gagal`).

> API **tidak** menerima sesi/cookie aplikasi web. Hanya token Bearer.

---

## 2. Ringkasan endpoint

| Metode | Path | Siapa | Fungsi |
|---|---|---|---|
| POST | `/auth/token` | publik (dengan kata sandi) | Membuat token |
| DELETE | `/auth/token` | pemilik token | Mencabut token yang sedang dipakai |
| GET | `/me` | semua | Identitas dan peran pemilik token |
| GET | `/jenis-surat` | semua | Katalog format surat aktif |
| GET | `/jenis-surat/{kode}` | semua | Rincian format: isian dan syarat berkas |
| GET | `/pengajuan` | semua | Daftar pengajuan surat yang boleh dilihat |
| GET | `/pengajuan/{kode}` | semua (menurut hak) | Rincian pengajuan, persetujuan, lampiran |
| GET | `/surat-keluar` | staf | Surat keluar buatan staf |
| GET | `/surat-keluar/{id}` | staf (menurut hak) | Rincian dan tahapan surat keluar |
| GET | `/surat-masuk` | Admin, Dekan, Wakil Dekan, Kaprodi | Surat masuk |
| GET | `/surat-masuk/{id}` | idem (menurut hak) | Rincian surat masuk |
| GET | `/pembukuan` | Admin, Dekan, Wakil Dekan | Buku agenda |
| GET | `/verifikasi/{token}` | semua | Memeriksa keaslian surat ber-QR |

### Siapa melihat apa

| Data | Mahasiswa | Dosen / Tendik | Kaprodi | Wakil Dekan | Dekan | Admin TU / Super Admin |
|---|---|---|---|---|---|---|
| `/pengajuan` | milik sendiri | milik sendiri | milik sendiri + prodinya | semua | semua | semua |
| `/surat-keluar` | 403 | buatan sendiri | buatan sendiri + yang ia tandatangani | semua | semua | semua |
| `/surat-masuk` | 403 | 403 | tidak rahasia | semua | semua | semua |
| `/pembukuan` | 403 | 403 | 403 | ✔ (rahasia disamarkan) | ✔ (rahasia disamarkan) | ✔ |
| `/jenis-surat` | hanya format mahasiswa | semua format | semua format | semua format | semua format | semua format |

Surat keluar yang berasal dari pengajuan mahasiswa tidak muncul di `/surat-keluar`; nomor dan statusnya ada pada objek `surat` di `/pengajuan/{kode}`.

---

## 3. Konvensi

### Paginasi

Endpoint daftar mengembalikan 15 data per halaman (maks 50; `/pembukuan` maks 100). Parameter: `page`, `per_page`.

`/pengajuan`, `/surat-keluar`, dan `/surat-masuk` memakai format Laravel:

```json
{ "data": [ … ],
  "links": { "first": "…?page=1", "last": "…?page=8", "prev": null, "next": "…?page=2" },
  "meta": { "current_page": 1, "last_page": 8, "per_page": 15, "total": 8, "from": 1, "to": 1, "path": "…", "links": [ … ] } }
```

`/pembukuan` memakai meta ringkas: `{"data":[…],"meta":{"halaman":1,"per_page":25,"total":6,"halaman_terakhir":1}}`.

### Waktu dan tanggal

Waktu memakai ISO 8601 dengan zona (`2026-10-28T16:16:47+08:00`). Tanggal surat berformat `YYYY-MM-DD`.

### Galat

Semua galat di bawah `/api` berbentuk JSON, tanpa perlu header `Accept`:

| Kode | Arti | Contoh isi |
|---|---|---|
| 401 | Token hilang, salah, dicabut, atau kedaluwarsa | `{"message":"Unauthenticated."}` |
| 403 | Peran/prodi tidak berhak, atau di luar jaringan lokal | `{"message":"Anda tidak berhak melihat pengajuan ini."}` |
| 404 | Data tidak ada, atau bukan jenis yang dilayani endpoint itu | `{"message":"Not Found."}` |
| 422 | Parameter tidak valid | `{"message":"…","errors":{"status":["…"]}}` |
| 429 | Terlalu banyak permintaan | `{"message":"…"}` + `Retry-After` |

---

## 4. Rincian endpoint

### GET `/me`

Identitas pemilik token (bentuk sama dengan `pengguna` pada respons token, dibungkus `data`).

### GET `/jenis-surat`

Parameter: `sasaran` = `mahasiswa` | `staf` | `masuk` (opsional; mahasiswa selalu dibatasi ke `mahasiswa`). Hanya format aktif. Respons berupa `data[]` berisi `kode`, `nama`, `deskripsi`, `kategori`, `sasaran`, `sla_hari`, `bentuk` (`qr` atau `tanpa_qr`), `perlu_paraf`, `verifikator`, `pemaraf`, `kekhususan`, `klasifikasi`.

### GET `/jenis-surat/{kode}`

Menambahkan `isian[]` (nama, label, tipe, wajib, opsi, kolom) dan `syarat_berkas[]`:

```json
{ "data": {
  "kode": "CUTI-AKADEMIK", "nama": "Surat Cuti Akademik", "kategori": "Akademik", "sasaran": "mahasiswa",
  "sla_hari": 5, "bentuk": "qr", "perlu_paraf": true, "verifikator": "kaprodi", "pemaraf": "wakil_dekan", "kekhususan": "KET",
  "klasifikasi": { "kode": "F", "nama": "Pendidikan, Penelitian, dan Latihan (Darul Arqam dsb)" },
  "isian": [ { "nama": "semester_cuti", "label": "Semester yang Dicutikan", "tipe": "teks", "wajib": true, "opsi": null, "kolom": null } ],
  "syarat_berkas": [ { "label": "Kartu Tanda Mahasiswa (KTM) Aktif", "wajib": true } ] } }
```

### GET `/pengajuan`

Parameter: `status` (`diajukan`, `diverifikasi`, `disetujui`, `ditandatangani`, `selesai`, `ditolak`), `q` (kode, nama, atau NPM pemohon), `page`, `per_page`. Urut terbaru.

```json
{ "kode": "REG-2026-000008",
  "jenis": { "kode": "KET-AKTIF", "nama": "Surat Keterangan Aktif Kuliah" },
  "status": { "kode": "ditandatangani", "label": "Ditandatangani" },
  "langkah": 3, "total_langkah": 4, "persen": 75,
  "tahap_berjalan": "Surat terbit — menunggu penyelesaian TU",
  "pemohon": { "nama": "La Ode Rizky", "nomor_induk": "20650021", "prodi": "Rekayasa Sistem Komputer" },
  "diajukan_pada": "2026-10-28T16:16:47+08:00", "batas_waktu": "2026-10-30T16:16:47+08:00",
  "alasan_tolak": null,
  "surat": { "id": 4, "nomor": "002/KET/II.3.AU/UMB-06/F/2026", "status": "ditandatangani", "ditandatangani_pada": "2026-10-28T16:16:47+08:00" } }
```

`surat` bernilai `null` sampai surat terbit bernomor.

### GET `/pengajuan/{kode}`

`{kode}` adalah nomor registrasi, mis. `REG-2026-000008`. Selain bidang di atas, memuat:

| Bidang | Isi |
|---|---|
| `isian` | Jawaban formulir (objek, kuncinya nama isian) |
| `catatan` | Catatan revisi terakhir, bila ada |
| `persetujuan[]` | Tahap `verifikasi`, `paraf`, `ttd`: `peran`, `status` (`menunggu`, `disetujui`, `ditolak`, `revisi`), `oleh`, `catatan`, `diputuskan_pada` |
| `lampiran[]` | Metadata berkas persyaratan: `label`, `nama`, `mime`, `ukuran` (byte). **Isi berkas tidak dapat diunduh lewat API** |

### GET `/surat-keluar` dan `/surat-keluar/{id}`

Parameter daftar: `status` (`draf`, `menunggu_paraf`, `menunggu_ttd`, `ditandatangani`, `batal`), `q` (perihal, nomor, tujuan), `page`, `per_page`.

Bidang: `id`, `arah`, `nomor` (kosong sampai terbit), `perihal`, `sifat`, `tujuan`, `tgl_surat`, `status`, `bentuk` (`qr` atau `tanpa_qr`), `klasifikasi`, `format`, `dibuat_oleh`, `penandatangan`, `ditandatangani_pada`, `dibatalkan_pada`, `alasan_batal`.

Rincian menambahkan `tahapan[]` (garis waktu: `judul`, `status` = `selesai` | `sekarang` | `menunggu` | `ditolak`, `waktu`, `oleh`, `catatan`), `lampiran[]`, serta untuk surat terbit: `pdf_sha256` dan, pada surat ber-QR, `url_verifikasi`. **Isi surat (HTML) tidak disertakan.**

### GET `/surat-masuk` dan `/surat-masuk/{id}`

Parameter daftar: `q` (perihal, asal, nomor agenda, nomor asal), `tahun`, `sifat` (`biasa`, `penting`, `segera`, `rahasia`), `page`, `per_page`.

```json
{ "data": { "id": 5, "arah": "masuk", "nomor": "0451/LL9/TU/2026", "no_agenda": "AGD-2026/X/0001",
  "perihal": "Undangan Rapat Koordinasi Penjaminan Mutu & Akreditasi Program Studi", "sifat": "penting",
  "asal": "Lembaga Layanan Pendidikan Tinggi Wilayah IX (LLDIKTI)", "tgl_surat": "2026-10-22", "tgl_diterima": "2026-10-23",
  "status": "tercatat", "klasifikasi": { "kode": "A", "nama": "Umum dan Tata Usaha" },
  "format": { "kode": "SM-UNDANGAN", "nama": "Undangan (Surat Masuk)" }, "dibuat_oleh": "Admin Tata Usaha",
  "lampiran": [ { "label": "Pindaian surat", "nama": "pindaian-surat.pdf", "mime": "application/pdf", "ukuran": 153220 } ] } }
```

`nomor` adalah nomor surat dari pengirim. Surat bersifat `rahasia` hanya muncul bagi Admin, Dekan, dan Wakil Dekan; bagi peran lain tidak ada di daftar dan rincian mengembalikan 403.

### GET `/pembukuan`

Parameter: `arah` (`masuk`, `keluar`, `lain`), `tahun`, `sumber` (`aplikasi`, `buku`), `q`, `page`, `per_page` (maks 100).

```json
{ "data": [ { "sumber": "aplikasi", "arah": "keluar", "nomor": "002/KET/II.3.AU/UMB-06/F/2026", "no_agenda": null,
  "tgl_surat": "2026-10-28", "tgl_diterima": null, "pihak": "La Ode Rizky (20650021)",
  "perihal": "Surat Keterangan Aktif Kuliah a.n. La Ode Rizky", "sifat": "biasa", "jenis": null, "keterangan": "ditandatangani" } ],
  "meta": { "halaman": 1, "per_page": 25, "total": 6, "halaman_terakhir": 1 } }
```

`sumber` `aplikasi` = surat dari aplikasi; `pembukuan` = catatan manual atau impor CSV. Bagi Dekan dan Wakil Dekan, `perihal` surat rahasia diganti `"(rahasia)"` (sama dengan ekspor CSV di aplikasi web).

### GET `/verifikasi/{token}`

`{token}` adalah token surat (43 karakter) pada alamat verifikasi `…/v/{token}`. Server menyusun ulang data surat dari basis data lalu **memeriksa tanda tangan Ed25519** dengan kunci publik fakultas.

```json
{ "data": {
  "tanda_tangan_sah": true, "berlaku": true, "status": "ditandatangani",
  "nomor": "001/II.3.AU/UMB-06/F/2026", "perihal": "Surat Izin Penelitian a.n. Muhammad Fauzan", "tgl_surat": "2026-10-28",
  "penandatangan": { "nama": "Agusman, S.T., MM.", "jabatan": "Dekan Fakultas Teknik" },
  "ditandatangani_pada": "2026-10-28T16:16:45+08:00", "dibatalkan_pada": null, "alasan_batal": null,
  "pdf_sha256": "e878d612e843a59c899a85c573776b945274a90757261aa3745c60ebd3c29c96" } }
```

| Bidang | Arti |
|---|---|
| `tanda_tangan_sah` | Tanda tangan digital cocok dengan data surat. `false` bila data diubah setelah ditandatangani |
| `berlaku` | `tanda_tangan_sah` **dan** surat tidak dibatalkan |
| `pdf_sha256` | Hash SHA-256 PDF yang diterbitkan. Cocokkan dengan berkas yang Anda pegang: `shasum -a 256 surat.pdf` |

Untuk surat `rahasia`, `perihal` dan `alasan_batal` bernilai `null`. Token tidak dikenal mengembalikan 404. Setiap pemeriksaan dicatat di Log Aktivitas.

Contoh pemeriksaan berkas:

```bash
HASH=$(shasum -a 256 surat.pdf | cut -d' ' -f1)
curl -s http://sipersu.ft.umbuton.ac.id/api/v1/verifikasi/$TOKEN -H "Authorization: Bearer $T" -H "Accept: application/json" \
  | python3 -c "import sys,json; d=json.load(sys.stdin)['data']; print('COCOK' if d['berlaku'] and d['pdf_sha256']=='$HASH' else 'TIDAK COCOK')"
```

---

## 5. Contoh klien

### JavaScript (Node 18+ atau peramban di jaringan kampus)

```js
const BASE = "http://sipersu.ft.umbuton.ac.id/api/v1";
const { token } = await (await fetch(`${BASE}/auth/token`, {
  method: "POST",
  headers: { "Content-Type": "application/json", Accept: "application/json" },
  body: JSON.stringify({ nomor_induk: "TU", password: process.env.SIPERSU_SANDI, nama_perangkat: "dasbor" }),
})).json();

const res = await fetch(`${BASE}/pengajuan?status=diajukan&per_page=50`, {
  headers: { Authorization: `Bearer ${token}`, Accept: "application/json" },
});
const { data, meta } = await res.json();
console.log(`${meta.total} pengajuan menunggu verifikasi`);
```

### Python

```python
import os, requests
BASE = "http://sipersu.ft.umbuton.ac.id/api/v1"
tok = requests.post(f"{BASE}/auth/token", json={"nomor_induk": "TU", "password": os.environ["SIPERSU_SANDI"], "nama_perangkat": "laporan"}).json()["token"]
h = {"Authorization": f"Bearer {tok}", "Accept": "application/json"}

halaman, semua = 1, []
while True:
    r = requests.get(f"{BASE}/pembukuan", params={"tahun": 2026, "per_page": 100, "page": halaman}, headers=h).json()
    semua += r["data"]
    if halaman >= r["meta"]["halaman_terakhir"]:
        break
    halaman += 1
print(len(semua), "catatan")
```

---

## 6. Keamanan dan batasan

- **Gunakan akun khusus** (mis. akun TU atau akun pembaca) untuk integrasi, bukan akun Dekan. Hak akses token = hak akses akun.
- Kata sandi tidak perlu disimpan: simpan **token** dan perbarui sebelum 7 hari.
- API dilayani lewat HTTP biasa di jaringan lokal (kecuali HTTPS diaktifkan; lihat [INSTALL.md](../INSTALL.md) bagian 5.4). Token dan data terbaca oleh pihak yang dapat menyadap jaringan; jangan pakai di jaringan yang tidak dipercaya.
- Tidak ada endpoint tulis, unduh PDF, atau unduh lampiran. Unduhan tetap lewat aplikasi web.
- Rute `/api` hanya menerima alamat IP jaringan lokal (`LAN_CIDRS`). Tailscale (100.64.0.0/10) termasuk yang diizinkan.
- Nonaktifkan akun di **Master Data → Pengguna** untuk memutus semua tokennya sekaligus.

## 7. Uji

```bash
php artisan test tests/Feature/ApiV1Test.php
```

Uji mencakup pembuatan dan pencabutan token, pemblokiran percobaan berulang, pembatasan data per peran dan prodi, kerahasiaan surat, penyamaran Pembukuan, dan pemeriksaan tanda tangan.
