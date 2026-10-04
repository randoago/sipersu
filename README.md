# SIPERSU FT-UMB

Sistem Informasi Persuratan Fakultas Teknik Universitas Muhammadiyah Buton.
Laravel 12 · Livewire · Alpine.js · Tailwind (dikompilasi sekali) · SQLite — berjalan **lokal & offline** di Windows (Laragon).

- **Pasang di Windows:** baca [INSTALL.md](INSTALL.md)
- **Contoh surat & dokumentasi:** folder [documentation/](documentation/)
- **Cara kerja tanda tangan elektronik (enkripsi, kunci, QR, verifikasi):** [documentation/cara-kerja-tanda-tangan-elektronik.md](documentation/cara-kerja-tanda-tangan-elektronik.md)
- **Aset gambar kop/logo/tanda tangan:** [template/img/](template/img/)

## Pengembangan

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/sipersu.sqlite && php artisan migrate --seed
php artisan kunci:buat
php artisan serve
php artisan test            # 85 test
```

Node.js hanya dibutuhkan untuk **mengubah tampilan** (bukan untuk menjalankan aplikasi):

```bash
npm install
npm run assets   # salin font, ikon SVG, Chart.js, Alpine, logo ke folder proyek
npm run build    # kompilasi Tailwind → public/css/app.css (di-commit)
```

Seluruh aset (font Plus Jakarta Sans & Inter, ikon Material Symbols sebagai SVG inline, Chart.js, Alpine) disimpan lokal; tidak ada CDN.
Data demo untuk uji tampilan: `php artisan db:seed --class=DemoSeeder` (jangan di produksi).
