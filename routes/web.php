<?php

use App\Http\Controllers\LampiranController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\PersetujuanController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\VerifikasiController;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dasbor');

// Verifikasi publik — satu-satunya rute yang dibuka ke internet (Cloudflare Tunnel).
Route::get('/v/{token}', [VerifikasiController::class, 'tampil'])->name('verifikasi.show')->middleware('throttle:60,1');
Route::post('/v/{token}/cek', [VerifikasiController::class, 'cekBerkas'])->name('verifikasi.cek')->middleware('throttle:20,1');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::view('/bantuan', 'bantuan')->name('bantuan');

Route::middleware('auth')->group(function () {
    Route::post('/keluar', function () {
        \App\Models\LogAktivitas::catat('logout', 'Keluar dari sistem');
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('keluar');

    Route::get('/dasbor', \App\Http\Controllers\DasborController::class)->name('dasbor');

    // Konversi tanggal Masehi → Hijriah (dipakai pemilih tanggal surat)
    Route::get('/tanggal/hijriah', function (\Illuminate\Http\Request $r) {
        $d = $r->validate(['tgl' => ['required', 'date_format:Y-m-d']]);
        $c = \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $d['tgl']);

        return ['hijriah' => \App\Support\TanggalHijriah::format($c), 'masehi' => $c->translatedFormat('l, j F Y')];
    })->name('tanggal.hijriah');

    // e-Layanan mahasiswa
    Route::get('/layanan', [PengajuanController::class, 'katalog'])->name('layanan.katalog');
    Route::get('/layanan/lacak', [PengajuanController::class, 'lacak'])->name('layanan.lacak');
    Route::get('/layanan/riwayat', [PengajuanController::class, 'riwayat'])->name('layanan.riwayat');
    Route::get('/layanan/{jenis:kode}/ajukan', \App\Livewire\AjukanSurat::class)->name('layanan.ajukan');

    // Pengajuan (lacak untuk pemohon, verifikasi untuk petugas)
    Route::get('/pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
    Route::get('/pengajuan/{pengajuan}', [PengajuanController::class, 'show'])->name('pengajuan.show');
    Route::post('/pengajuan/{pengajuan}/verifikasi', [PengajuanController::class, 'verifikasi'])->name('pengajuan.verifikasi');
    Route::post('/pengajuan/{pengajuan}/tolak', [PengajuanController::class, 'tolak'])->name('pengajuan.tolak');
    Route::post('/pengajuan/{pengajuan}/selesai', [PengajuanController::class, 'selesai'])->name('pengajuan.selesai');

    // Persetujuan: paraf & tanda tangan
    Route::get('/persetujuan', [PersetujuanController::class, 'index'])->name('persetujuan.index');
    Route::get('/persetujuan/{pengajuan}', [PersetujuanController::class, 'show'])->name('persetujuan.show');
    Route::post('/persetujuan/{pengajuan}/paraf', [PersetujuanController::class, 'paraf'])->name('persetujuan.paraf');
    Route::post('/persetujuan/{pengajuan}/tandatangani', [PersetujuanController::class, 'tandatangani'])->name('persetujuan.tandatangani');
    Route::post('/persetujuan/{pengajuan}/kembalikan', [PersetujuanController::class, 'kembalikan'])->name('persetujuan.kembalikan');
    Route::post('/persetujuan/{pengajuan}/tolak', [PersetujuanController::class, 'tolak'])->name('persetujuan.tolak');

    // Pengaturan & Master Data — hanya Super Admin dan Admin TU
    Route::middleware('role:super_admin|admin_tu')->group(function () {
        // Format Surat: TU menentukan surat untuk apa, isiannya apa, dan templatnya
        $fs = \App\Http\Controllers\FormatSuratController::class;
        Route::get('/format-surat', [$fs, 'index'])->name('format-surat.index');
        Route::get('/format-surat/buat', [$fs, 'buat'])->name('format-surat.buat');
        Route::post('/format-surat', [$fs, 'simpan'])->name('format-surat.simpan');
        Route::post('/format-surat/pratinjau', [$fs, 'pratinjau'])->name('format-surat.pratinjau');
        Route::get('/format-surat/{format}/ubah', [$fs, 'ubah'])->name('format-surat.ubah');
        Route::put('/format-surat/{format}', [$fs, 'simpan'])->name('format-surat.perbarui');
        Route::post('/format-surat/{format}/aktif', [$fs, 'aktif'])->name('format-surat.aktif');
        Route::post('/format-surat/{format}/salin', [$fs, 'salin'])->name('format-surat.salin');
        Route::redirect('/master/jenis-surat', '/format-surat');

        Route::redirect('/pengaturan', '/pengaturan/nomor')->name('pengaturan.index');
        Route::get('/pengaturan/nomor', [\App\Http\Controllers\PengaturanController::class, 'nomor'])->name('pengaturan.nomor');
        Route::post('/pengaturan/nomor', [\App\Http\Controllers\PengaturanController::class, 'simpanNomor'])->name('pengaturan.nomor.simpan');
        Route::get('/pengaturan/log', [\App\Http\Controllers\PengaturanController::class, 'log'])->name('pengaturan.log');
        Route::get('/pengaturan/backup', [\App\Http\Controllers\BackupController::class, 'index'])->name('pengaturan.backup');
        Route::post('/pengaturan/backup', [\App\Http\Controllers\BackupController::class, 'jalankan'])->name('backup.jalankan');
        Route::get('/pengaturan/backup/{riwayat}/unduh', [\App\Http\Controllers\BackupController::class, 'unduh'])->name('backup.unduh');
    });

    Route::middleware('role:super_admin|admin_tu')->prefix('master')->name('master.')->group(function () {
        Route::redirect('/', '/master/pengguna')->name('index');
        $sp = \App\Http\Controllers\SpesimenController::class;
        Route::get('/spesimen', [$sp, 'index'])->name('spesimen');
        Route::post('/spesimen/{user}', [$sp, 'simpan'])->name('spesimen.simpan');
        Route::delete('/spesimen/{user}/{jenis}', [$sp, 'hapus'])->name('spesimen.hapus');
        Route::get('/spesimen/{user}/lihat', [$sp, 'lihat'])->name('spesimen.lihat');
        $ip = \App\Http\Controllers\ImporPenggunaController::class;
        Route::get('/pengguna/impor', [$ip, 'form'])->name('impor');
        Route::get('/pengguna/impor/templat', [$ip, 'templat'])->name('impor.templat');
        Route::post('/pengguna/impor/periksa', [$ip, 'periksa'])->name('impor.periksa');
        Route::post('/pengguna/impor/proses', [$ip, 'proses'])->name('impor.proses');
        Route::get('/pengguna/impor/hasil', [$ip, 'hasil'])->name('impor.hasil');
        $e = ['entitas' => 'pengguna|prodi|jabatan|klasifikasi'];
        Route::get('/{entitas}', [\App\Http\Controllers\MasterController::class, 'daftar'])->where($e)->name('daftar');
        Route::get('/{entitas}/buat', [\App\Http\Controllers\MasterController::class, 'buat'])->where($e)->name('buat');
        Route::post('/{entitas}', [\App\Http\Controllers\MasterController::class, 'simpan'])->where($e)->name('simpan');
        Route::get('/{entitas}/{id}/ubah', [\App\Http\Controllers\MasterController::class, 'ubah'])->where($e)->name('ubah');
        Route::put('/{entitas}/{id}', [\App\Http\Controllers\MasterController::class, 'simpan'])->where($e)->name('perbarui');
        Route::post('/{entitas}/{id}/aktif', [\App\Http\Controllers\MasterController::class, 'aktif'])->where($e)->name('aktif');
    });

    Route::get('/profil', [\App\Http\Controllers\ProfilController::class, 'tampil'])->name('profil');
    Route::post('/profil/kata-sandi', [\App\Http\Controllers\ProfilController::class, 'kataSandi'])->name('profil.sandi');
    Route::post('/profil/spesimen', [\App\Http\Controllers\ProfilController::class, 'spesimen'])->name('profil.spesimen');
    Route::get('/profil/spesimen', [\App\Http\Controllers\ProfilController::class, 'lihatSpesimen'])->name('profil.spesimen.lihat');

    Route::get('/notifikasi', [\App\Http\Controllers\NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::post('/notifikasi/baca-semua', [\App\Http\Controllers\NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{notifikasi}', [\App\Http\Controllers\NotifikasiController::class, 'buka'])->name('notifikasi.buka');

    // Surat masuk
    $sm = \App\Http\Controllers\SuratMasukController::class;
    Route::get('/surat-masuk', [$sm, 'index'])->name('surat-masuk.index');
    Route::get('/surat-masuk/catat', [$sm, 'pilih'])->name('surat-masuk.buat');
    Route::get('/surat-masuk/catat/{jenis:kode}', [$sm, 'isi'])->name('surat-masuk.isi');
    Route::post('/surat-masuk/catat/{jenis:kode}', [$sm, 'simpan'])->name('surat-masuk.simpan');
    Route::get('/surat-masuk/{surat}', [$sm, 'show'])->name('surat-masuk.show');
    Route::get('/surat-masuk/{surat}/ubah', [$sm, 'ubah'])->name('surat-masuk.ubah');
    Route::put('/surat-masuk/{surat}', [$sm, 'perbarui'])->name('surat-masuk.perbarui');

    // Surat keluar
    $sk = \App\Http\Controllers\SuratKeluarController::class;
    Route::get('/surat-keluar', [$sk, 'index'])->name('surat-keluar.index');
    Route::get('/surat-keluar/buat', [$sk, 'buat'])->name('surat-keluar.buat');
    Route::get('/surat-keluar/buat/bebas', [$sk, 'bebas'])->name('surat-keluar.bebas');
    Route::get('/surat-keluar/format/{jenis:kode}', [$sk, 'isi'])->name('surat-keluar.isi');
    Route::post('/surat-keluar/format/{jenis:kode}', [$sk, 'simpanFormat'])->name('surat-keluar.simpan-format');
    Route::post('/surat-keluar', [$sk, 'simpan'])->name('surat-keluar.simpan');
    Route::post('/surat-keluar/pratinjau', [$sk, 'pratinjau'])->name('surat-keluar.pratinjau');
    Route::get('/surat-keluar/{surat}', [$sk, 'show'])->name('surat-keluar.show');
    Route::get('/surat-keluar/{surat}/ubah', [$sk, 'ubah'])->name('surat-keluar.ubah');
    Route::put('/surat-keluar/{surat}', [$sk, 'perbarui'])->name('surat-keluar.perbarui');
    Route::delete('/surat-keluar/{surat}', [$sk, 'hapus'])->name('surat-keluar.hapus');
    Route::post('/surat-keluar/{surat}/ajukan', [$sk, 'ajukan'])->name('surat-keluar.ajukan');
    Route::post('/surat-keluar/{surat}/paraf', [$sk, 'paraf'])->name('surat-keluar.paraf');
    Route::post('/surat-keluar/{surat}/tandatangani', [$sk, 'tandatangani'])->name('surat-keluar.tandatangani');
    Route::post('/surat-keluar/{surat}/kembalikan', [$sk, 'kembalikan'])->name('surat-keluar.kembalikan');
    Route::post('/surat-keluar/{surat}/batalkan', [$sk, 'batalkan'])->name('surat-keluar.batalkan');

    Route::get('/lampiran/{lampiran}', [LampiranController::class, 'unduh'])->name('lampiran.unduh');
    Route::get('/surat/{surat}/pdf', [SuratController::class, 'pdf'])->name('surat.pdf');
});
