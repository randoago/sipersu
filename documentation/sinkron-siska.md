# Sinkronisasi Master Data dari SISKA

Menarik **Program Studi** dan **Pengguna (mahasiswa)** dari SISKA tanpa input manual. Satu arah (SISKA → SIPERSU), dibaca langsung dari database SISKA (bukan NeoFeeder). Dosen/tendik **tidak** disinkronkan (menunggu API SIMPEG).

## Pengaturan awal

1. **Di SISKA** (sekali): buat kunci API lewat **Pengaturan Akademik → tab Integrasi Client** (Super Admin): Tambah Klien → nama `sipersu`, scope `prodi:read` + `mahasiswa:read`, IP opsional. Di tab yang sama kunci dapat dinonaktifkan, diputar, dan dihapus. Kunci asli hanya tampil sekali; yang tersimpan di database SISKA hanya hash SHA-256-nya. Alternatif lewat CLI:
   ```
   docker exec siska-backend-1 php artisan integrasi:buat-kunci sipersu
   # opsi: --scope=prodi:read,mahasiswa:read  --ip=172.16.0.0/12
   docker exec siska-backend-1 php artisan integrasi:cabut sipersu     # mencabut
   ```
   Menjalankan `integrasi:buat-kunci` lagi untuk klien yang sama = memutar (mengganti) kunci.
2. **Di SIPERSU**: Master Data → **Koneksi SISKA** (khusus Super Admin).
   - Alamat server: `http://host.docker.internal:8090` bila SISKA berjalan di komputer yang sama (SIPERSU di Docker).
   - Tempel kunci API (disimpan terenkripsi dengan `APP_KEY`; tidak pernah ditampilkan lagi).
   - Simpan, lalu pilih **program studi** SISKA yang termasuk Fakultas Teknik dan **status mahasiswa** (bawaan: AKTIF). Klik **Uji koneksi** bila perlu.
3. Master Data → **Program Studi** → **Sinkron dari SISKA**, lalu Master Data → **Pengguna → Mahasiswa** → **Sinkron dari SISKA** (Super Admin & Admin TU).

## Aturan sinkronisasi

| Data | Aturan |
|---|---|
| Program studi | Dicocokkan dengan `siska_id`, atau nama + jenjang (prodi lokal yang ada tetap dipakai). `kode` dan `kode_unit` lokal tidak ditimpa. Prodi yang belum ada dibuat dengan kode dari SISKA. |
| Mahasiswa | Kunci `nomor_induk` = NIM. Baru: peran `mahasiswa`, kata sandi acak (atur ulang oleh Admin TU / "lupa kata sandi"). Ada: hanya nama, email, HP, prodi, angkatan, tempat/tanggal lahir, alamat diperbarui (nilai kosong dari SISKA tidak menghapus data lokal). `username`, peran, kata sandi, spesimen, status aktif lokal tidak disentuh. |
| Dilewati | NIM kosong/tidak valid, NIM ganda di SISKA (baris pertama dipakai), NIM yang sudah dipakai akun non-mahasiswa. Email bentrok/tidak valid dikosongkan (mahasiswa tetap disinkronkan). |

Catatan hasil (maks. 30) tampil di bawah tab; ringkasan juga tercatat di Log Aktivitas (`sinkron_siska`). Dua sinkronisasi tidak dapat berjalan bersamaan.

## Keamanan

- Endpoint SISKA `/api/integrasi/v1/{prodi,mahasiswa}`: baca-saja, header `X-Api-Key`, batas 60 permintaan/menit, daftar IP opsional, setiap akses (dan penolakan) tercatat di log SISKA.
- Kolom yang dikirim dibatasi: NIM, nama, jenis kelamin, prodi, angkatan, status, email, HP, tempat/tanggal lahir, alamat ringkas. **NIK, NPWP, data orang tua/wali tidak dikirim.**
