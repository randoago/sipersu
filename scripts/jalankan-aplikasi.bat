@echo off
chcp 65001 >nul
setlocal EnableExtensions
cd /d "%~dp0.."
call scripts\_php.bat || (pause & exit /b 1)
if not exist .env (echo [GALAT] .env belum ada. Jalankan scripts\pasang.bat dahulu. & pause & exit /b 1)
if not exist vendor\autoload.php (echo [GALAT] Pustaka belum terpasang. Jalankan scripts\pasang.bat dahulu. & pause & exit /b 1)

rem Alamat & port diambil dari APP_URL di .env (ubah lewat scripts\ubah-url.bat).
for /f "delims=" %%i in ('"%PHP_EXE%" scripts\ubah-url.php --tampil') do set "URL=%%i"
for /f "delims=" %%i in ('"%PHP_EXE%" scripts\ubah-url.php --port') do set "PORT=%%i"
for /f "delims=" %%i in ('"%PHP_EXE%" scripts\ubah-url.php --ip') do set "IPLOKAL=%%i"

echo.
echo === MENJALANKAN SIPERSU FT-UMB ===
echo Alamat (APP_URL) : %URL%
echo Port             : %PORT%
echo IP komputer ini  : http://%IPLOKAL%:%PORT%
echo.
echo Catatan: bila Laragon (Apache) sudah berjalan di port %PORT%, aplikasi SUDAH aktif;
echo          cukup buka alamat di atas, skrip ini tidak perlu dijalankan.
echo Tekan Ctrl+C atau tutup jendela ini untuk menghentikan.
echo.

rem Buka peramban sebentar lagi (server perlu waktu menyala).
start "" /min cmd /c "timeout /t 3 /nobreak >nul & explorer %URL%"

rem 0.0.0.0 = dapat diakses dari perangkat lain di jaringan lokal (akses dibatasi IP privat oleh aplikasi).
"%PHP_EXE%" artisan serve --host=0.0.0.0 --port=%PORT% --no-reload
echo.
echo Server berhenti. Bila "port sudah dipakai", Laragon/Apache kemungkinan sudah berjalan.
pause
