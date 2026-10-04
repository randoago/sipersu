// Menyalin aset dari node_modules ke folder proyek agar aplikasi 100% offline.
// Jalankan sekali di mesin pengembang: npm run assets && npm run build
// Hasilnya (public/fonts, public/js, resources/icons, public/css) di-commit.
import fs from 'fs';
import path from 'path';

const root = path.resolve(import.meta.dirname, '..');
const nm = (p) => path.join(root, 'node_modules', p);
const cp = (from, to) => {
  fs.mkdirSync(path.dirname(to), { recursive: true });
  fs.copyFileSync(from, to);
};

// 1. Font: hanya subset latin + latin-ext (cukup untuk Bahasa Indonesia).
for (const [pkg, nama] of [['plus-jakarta-sans', 'plus-jakarta-sans'], ['inter', 'inter']]) {
  for (const subset of ['latin', 'latin-ext']) {
    const f = `${nama}-${subset}-wght-normal.woff2`;
    cp(nm(`@fontsource-variable/${pkg}/files/${f}`), path.join(root, 'public/fonts', f));
  }
}

// 2. Ikon: Material Symbols (SVG) yang dipakai. Daftar = ikon di desain Stitch + tambahan.
const ikonTambahan = (fs.existsSync(path.join(root, 'scripts/ikon-tambahan.txt'))
  ? fs.readFileSync(path.join(root, 'scripts/ikon-tambahan.txt'), 'utf8') : '')
  .split(/\s+/).filter(Boolean);
const dipakai = new Set(ikonTambahan);
const stitch = path.join(root, 'stich');
if (fs.existsSync(stitch)) {
  for (const d of fs.readdirSync(stitch)) {
    const f = path.join(stitch, d, 'code.html');
    if (!fs.existsSync(f)) continue;
    for (const m of fs.readFileSync(f, 'utf8').matchAll(/material-symbols-outlined[^>]*>\s*([a-z0-9_]+)\s*</g)) dipakai.add(m[1]);
  }
}
let n = 0;
for (const nama of dipakai) {
  for (const sfx of ['', '-fill']) {
    const from = nm(`@material-symbols/svg-400/outlined/${nama}${sfx}.svg`);
    if (fs.existsSync(from)) { cp(from, path.join(root, 'resources/icons', `${nama}${sfx}.svg`)); n++; }
    else if (sfx === '') console.warn('Ikon tidak ditemukan:', nama);
  }
}
console.log(`${n} berkas ikon disalin`);

// 3. Chart.js (UMD, ringan)
cp(nm('chart.js/dist/chart.umd.js'), path.join(root, 'public/js/chart.umd.js'));

// 3b. Alpine.js mandiri (halaman publik tanpa Livewire, mis. verifikasi QR)
cp(nm('alpinejs/dist/cdn.min.js'), path.join(root, 'public/js/alpine.min.js'));

// 3c. tweetnacl (verifikasi Ed25519 di browser untuk verifikasi.html offline)
cp(nm('tweetnacl/nacl-fast.min.js'), path.join(root, 'resources/stubs/nacl-fast.min.js'));

// 4. Logo & gambar dari template/img
const img = path.join(root, 'template/img');
if (fs.existsSync(img)) {
  cp(path.join(img, 'Logo-undangan.png'), path.join(root, 'public/images/logo-umb.png'));
  cp(path.join(img, 'Header-Undangan.png'), path.join(root, 'public/images/header-undangan.png'));
  cp(path.join(img, 'tick.png'), path.join(root, 'public/images/tick.png'));
}
