# Format Impor Pengguna (CSV)

Menambah banyak pengguna sekaligus: **Master Data → Pengguna → Impor CSV**.
Berkas contoh siap pakai: [contoh-impor-pengguna.csv](contoh-impor-pengguna.csv) (atau tombol **Unduh Templat CSV** di halaman impor).

## Aturan berkas

| Hal | Ketentuan |
|---|---|
| Ekstensi | `.csv` (atau `.txt`) — bukan `.xlsx` |
| Ukuran / jumlah | Maksimal **1 MB** dan **500 baris data** per berkas |
| Baris pertama | **Nama kolom** (huruf kecil, persis seperti tabel di bawah) |
| Pemisah kolom | `,` (koma) atau `;` (titik koma) atau Tab — dikenali otomatis |
| Pengkodean | UTF-8 (dengan/tanpa BOM) atau Windows-1252 (ANSI) |
| Teks berkoma | Apit dengan tanda kutip ganda: `"Jl. Betoambari No. 5, Baubau"` |
| Baris kosong | Diabaikan |

**Dari Excel:** *File → Save As → **CSV UTF-8 (Comma delimited)***.
**Dari Google Sheets:** *File → Download → Comma-separated values (.csv)*.

## Kolom

| Kolom | Wajib | Isi & contoh |
|---|---|---|
| `nomor_induk` | **Ya** | NPM / NIDN; huruf, angka, titik, strip; maks 30. Dipakai login & harus unik. `22650101` |
| `nama` | **Ya** | Nama **tanpa gelar**. `Ahmad Fauzi` |
| `peran` | **Ya** | Satu atau lebih, dipisah `\|`. `dosen_tendik\|kaprodi` |
| `prodi` | Tidak | Kode prodi: `TS` (Teknik Sipil), `RSK` (Rekayasa Sistem Komputer), `STI` (Sistem dan Teknologi Informasi). Nama prodi juga diterima. |
| `email` | Tidak | Unik. `ahmad@mhs.umbuton.ac.id` |
| `no_hp` | Tidak | `081234567890` |
| `angkatan` | Tidak | 4 angka. `2022` |
| `tempat_lahir` | Tidak | `Baubau` |
| `tanggal_lahir` | Tidak | `2003-08-17` atau `17/08/2003` |
| `alamat` | Tidak | Maks 300 karakter |
| `gelar_depan` | Tidak | `Dr.` |
| `gelar_belakang` | Tidak | `"S.T., M.T."` (beri tanda kutip bila berkoma) |
| `password` | Tidak | Minimal 8 karakter. **Kosong = dibuatkan kata sandi acak** (ditampilkan sekali setelah impor). |
| `aktif` | Tidak | `ya` / `tidak` (kosong = ya) |

Nama kolom alternatif yang juga dikenali: `nim`, `nidn`, `nip`, `nama_lengkap`, `role`, `program_studi`, `kata_sandi`, `hp`, `telepon`, `tgl_lahir`, `status`.
Kolom lain yang tidak dikenal **diabaikan** (dengan pemberitahuan).

### Nilai `peran`

| Nilai | Peran |
|---|---|
| `mahasiswa` | Mahasiswa |
| `dosen_tendik` | Dosen / Tendik (alias: `dosen`, `tendik`) |
| `kaprodi` | Kaprodi |
| `wakil_dekan` | Wakil Dekan (alias: `wadek`) |
| `dekan` | Dekan |
| `admin_tu` | Admin Tata Usaha (alias: `tu`) |
| `super_admin` | Super Admin — **hanya dapat diberikan oleh Super Admin** |

Satu akun boleh punya lebih dari satu peran: `dosen_tendik|kaprodi`.

## Contoh isi berkas

```csv
nomor_induk,nama,peran,prodi,email,no_hp,angkatan,tempat_lahir,tanggal_lahir,alamat,gelar_depan,gelar_belakang,password,aktif
22650101,Ahmad Fauzi,mahasiswa,STI,ahmad.fauzi@mhs.umbuton.ac.id,081234567890,2022,Baubau,2003-08-17,"Jl. Betoambari No. 5, Baubau",,,,ya
0912099001,Siti Aisyah,dosen_tendik,TS,siti.aisyah@umbuton.ac.id,,,Makassar,1985-02-11,,,"S.T., M.T.",,ya
0912099002,Budi Santoso,dosen_tendik|kaprodi,RSK,,,,,,,,"S.Kom., M.Kom.",,ya
```

Versi minimal (hanya kolom wajib) juga sah:

```csv
nomor_induk,nama,peran
22650102,Nur Aini,mahasiswa
22650103,La Ode Rizky,mahasiswa
```

## Alur impor (2 langkah)

1. **Unggah & Periksa** — sistem membaca seluruh berkas dan menampilkan status tiap baris. **Belum ada data yang disimpan.**
   | Status | Arti |
   |---|---|
   | **Baru** | Akan dibuat |
   | **Perbarui** | Nomor induk sudah ada & opsi *Perbarui* dicentang → data diperbarui |
   | **Dilewati** | Nomor induk sudah ada & opsi *Perbarui* tidak dicentang |
   | **Galat** | Ada kesalahan (alasan ditampilkan per baris); baris ini **tidak diimpor** |
2. **Konfirmasi** — hanya baris *Baru* dan *Perbarui* yang disimpan, dalam satu transaksi. Baris bergalat dapat diperbaiki lalu diunggah ulang.

Saat **memperbarui**: kolom yang dikosongkan **tidak mengubah** data lama (termasuk `password`), `peran` menggantikan peran lama, dan kolom `aktif` hanya berpengaruh bila diisi.

## Pesan galat yang umum

| Pesan | Perbaikan |
|---|---|
| `Kolom wajib tidak ada pada baris judul` | Pastikan baris pertama berisi `nomor_induk`, `nama`, `peran` (unduh templat) |
| `peran "..." tidak dikenal` | Pakai nilai pada tabel peran di atas |
| `prodi "..." tidak ditemukan` | Pakai kode prodi (`TS`, `RSK`, `STI`) |
| `nomor_induk sama dengan baris N` | Ada nomor induk ganda di dalam berkas |
| `email sudah dipakai pengguna lain` | Email harus unik |
| `tanggal_lahir tidak valid` | Pakai `2003-08-17` atau `17/08/2003` |
| `Berkas bukan teks CSV` | Berkas `.xlsx` diganti nama; simpan ulang sebagai CSV |

## Keamanan

- Hanya **Super Admin** dan **Admin TU** yang dapat mengimpor. Admin TU tidak dapat memberi peran `super_admin` atau mengubah akun Super Admin.
- Kata sandi disimpan ter-hash. Kata sandi acak hasil impor **hanya ditampilkan sekali** (dapat diunduh sebagai CSV); minta pemilik akun segera menggantinya di *Profil → Ganti Kata Sandi* dan **hapus berkas unduhan** setelah dibagikan.
- Jangan menyimpan berkas CSV berisi kata sandi di folder bersama atau di GitHub.
- Setiap impor tercatat di **Pengaturan → Log Aktivitas** (jumlah dibuat/diperbarui, tanpa kata sandi).
