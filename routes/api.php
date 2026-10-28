<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JenisSuratController;
use App\Http\Controllers\Api\PembukuanController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\SuratKeluarController;
use App\Http\Controllers\Api\SuratMasukController;
use App\Http\Controllers\Api\VerifikasiSuratController;
use Illuminate\Support\Facades\Route;

/*
| REST API v1 (baca saja). Autentikasi: token Bearer pribadi (Laravel Sanctum) yang diperoleh dari POST /api/v1/auth/token.
| Hak akses sama dengan aplikasi web (peran, prodi, kerahasiaan surat). Hanya dapat dipanggil dari jaringan lokal (AksesJalur).
| Dokumentasi: documentation/api-rest.md dan documentation/openapi.yaml
*/
Route::prefix('v1')->name('api.')->group(function () {
    Route::post('auth/token', [AuthController::class, 'token'])->middleware('throttle:20,1')->name('token.buat');

    Route::middleware(['auth:sanctum', \App\Http\Middleware\AkunApiAktif::class, 'throttle:120,1'])->group(function () {
        Route::delete('auth/token', [AuthController::class, 'cabut'])->name('token.cabut');
        Route::get('me', [AuthController::class, 'saya'])->name('saya');

        Route::get('jenis-surat', [JenisSuratController::class, 'index'])->name('jenis-surat.index');
        Route::get('jenis-surat/{jenis:kode}', [JenisSuratController::class, 'show'])->name('jenis-surat.show');

        Route::get('pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
        Route::get('pengajuan/{pengajuan:kode}', [PengajuanController::class, 'show'])->name('pengajuan.show');

        Route::get('surat-keluar', [SuratKeluarController::class, 'index'])->name('surat-keluar.index');
        Route::get('surat-keluar/{surat}', [SuratKeluarController::class, 'show'])->whereNumber('surat')->name('surat-keluar.show');

        Route::get('surat-masuk', [SuratMasukController::class, 'index'])->name('surat-masuk.index');
        Route::get('surat-masuk/{surat}', [SuratMasukController::class, 'show'])->whereNumber('surat')->name('surat-masuk.show');

        Route::get('pembukuan', [PembukuanController::class, 'index'])->name('pembukuan.index');

        Route::get('verifikasi/{token}', [VerifikasiSuratController::class, 'show'])->name('verifikasi.show');
    });
});
