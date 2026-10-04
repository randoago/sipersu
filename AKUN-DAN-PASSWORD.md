# Akun & Kata Sandi SIPERSU FT-UMB

> ⚠️ Berkas ini hanya untuk **akun contoh/uji** (data seeder). **Jangan pernah menulis kata sandi asli di berkas ini atau di GitHub.**
> Sebelum dipakai sungguhan, ganti semua kata sandi atau nonaktifkan akun contoh (lihat bagian 3).

## 1. Akun contoh (kata sandi semuanya: `password`)

Login memakai **NPM / NIDN** + kata sandi di halaman `/login`.

| Peran | NPM / NIDN | Nama | Bisa apa |
|---|---|---|---|
| Super Admin | `0000000001` | Super Admin | Semua pengaturan, master data, format surat, backup |
| Admin TU | `198701012010011001` | Admin Tata Usaha | Verifikasi pengajuan, catat surat masuk, buat surat keluar, **Format Surat**, **unggah spesimen tanda tangan pejabat**, backup |
| Dekan | `0912038401` | Agusman, S.T., MM. | Tanda tangan surat (penandatangan "Dekan"), lihat surat masuk |
| Wakil Dekan | `0912048102` | Wakil Dekan (Contoh) | Paraf surat, lihat surat masuk |
| Kaprodi Teknik Sipil | `0912058301` | Idwan, S.T., M.Si. | Verifikasi pengajuan prodi sendiri, lihat surat masuk |
| Kaprodi Rekayasa Sistem Komputer | `0912068502` | Rando, S.Kom., M.Eng | idem (prodi RSK) |
| Kaprodi Sistem dan Teknologi Informasi | `0912078603` | Darmawan, S.Kom., M.Kom. | idem (prodi STI) |
| Dosen / Tendik | `0912088704` | Dosen Contoh | Membuat surat keluar |
| Mahasiswa | `21650012` | Muhammad Fauzan | Mengajukan & melacak surat |

Mahasiswa tambahan (hanya ada bila `DemoSeeder` dijalankan; kata sandi `password`):
`21650034` Ahmad Syahrir · `22650011` Nurul Aini · `21650077` Siti Rahmawati · `21650088` Bambang Irawan · `20650021` La Ode Rizky

Catatan: nama Wakil Dekan dan semua nomor induk di atas adalah **contoh**; ganti dengan data pejabat asli di **Master Data → Pengguna / Jabatan**.

## 2. Aturan kata sandi

- Minimal **8 karakter**, mengandung **huruf dan angka** (saat mengganti sendiri di Profil).
- Salah memasukkan 5 kali dalam 1 menit → login diblokir sementara.
- Kata sandi disimpan ter-hash; Admin tidak dapat melihatnya, hanya mengatur ulang.
- Penandatanganan surat meminta **kata sandi akun** sebagai konfirmasi.

## 3. Mengganti / mengatur ulang kata sandi

| Kebutuhan | Cara |
|---|---|
| Ganti kata sandi sendiri | **Profil Saya → Ganti Kata Sandi** (butuh kata sandi lama) |
| Lupa kata sandi (pengguna) | Hubungi Admin TU → **Master Data → Pengguna → Ubah** → isi kolom *Kata sandi* baru |
| Menonaktifkan akun contoh | **Master Data → Pengguna → Nonaktifkan** (akun nonaktif tidak bisa login) |
| Lupa kata sandi Super Admin | Di server: `php artisan tinker` lalu `App\Models\User::where('nomor_induk','0000000001')->first()->update(['password'=>'KataSandiBaru123']);` |

## 4. Kata sandi sistem (isi di KERTAS / pengelola kata sandi, bukan di berkas ini)

| Rahasia | Fungsi | Disimpan di | Pemegang |
|---|---|---|---|
| `BACKUP_PASSWORD` | Membuka semua backup ZIP terenkripsi | Amplop tertutup di brankas Dekanat + salinan terpisah | ……… |
| Kunci tanda tangan `storage\keys\ed25519.secret` | Tanda tangan elektronik fakultas | Server + salinan `kunci:cadangkan` di flashdisk/brankas | ……… |
| Sandi aplikasi email (`MAIL_PASSWORD`) | Mengirim notifikasi email | `.env` di server | ……… |
| Akun Google fakultas (rclone) | Backup mingguan ke Google Drive | Pengelola kata sandi | ……… |
| Akun Cloudflare | Tunnel verifikasi QR | Pengelola kata sandi | ……… |
| Akun GitHub | Hosting `verifikasi.html` | Pengelola kata sandi | ……… |
| Akun Windows server (Administrator) | Menjalankan Laragon & Task Scheduler | Pengelola kata sandi | ……… |

Prosedur lengkap penyimpanan sandi backup ada di **INSTALL.md → bagian 12 (SOP penyimpanan kata sandi backup)**.

## 5. Checklist sebelum dipakai sungguhan

- ☐ Semua akun contoh sudah diganti kata sandinya atau dinonaktifkan
- ☐ Data pejabat asli (Dekan, Wakil Dekan, Kaprodi) dan nomor induknya sudah diperbarui
- ☐ `BACKUP_PASSWORD` terisi (≥ 16 karakter acak) dan sudah dicatat sesuai SOP
- ☐ Kunci tanda tangan sudah dicadangkan (`php artisan kunci:cadangkan D:\kunci-sipersu`)
- ☐ `APP_DEBUG=false` di `.env`
