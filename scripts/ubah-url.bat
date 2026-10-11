@echo off
chcp 65001 >nul
setlocal EnableExtensions
cd /d "%~dp0.."
call scripts\_php.bat || goto :selesai
if not exist .env (
  if exist .env.example (copy /y .env.example .env >nul) else (echo [GALAT] Berkas .env.example tidak ada. & goto :selesai)
)

:menu
for /f "delims=" %%i in ('"%PHP_EXE%" scripts\ubah-url.php --tampil') do set "SEKARANG=%%i"
for /f "delims=" %%i in ('"%PHP_EXE%" scripts\ubah-url.php --ip') do set "IPLOKAL=%%i"
echo.
echo ============================================================
echo   UBAH ALAMAT (URL) LOKAL SIPERSU
echo ============================================================
echo   Alamat sekarang      : %SEKARANG%
echo   IP komputer ini      : %IPLOKAL%
echo.
echo   Pilih alamat baru:
echo     1. Nama lokal      : sipersu.ft.umbuton.ac.id   (perlu DNS router / berkas hosts)
echo     2. IP komputer ini : http://%IPLOKAL%           (paling mudah, tanpa DNS)
echo     3. Hanya komputer ini : http://localhost
echo     4. Ketik sendiri   (mis. sipersu.local, 192.168.1.10, https://nama.kampus.ac.id)
echo     0. Batal
echo.
set "PILIH="
set /p "PILIH=Pilihan [0-4]: "
set "ALAMAT="
if "%PILIH%"=="1" set "ALAMAT=sipersu.ft.umbuton.ac.id"
if "%PILIH%"=="2" set "ALAMAT=%IPLOKAL%"
if "%PILIH%"=="3" set "ALAMAT=localhost"
if "%PILIH%"=="4" set /p "ALAMAT=Ketik alamat: "
if "%PILIH%"=="0" goto :selesai
if not defined ALAMAT (echo Pilihan tidak dikenal. & goto :menu)

echo.
"%PHP_EXE%" scripts\ubah-url.php --set="%ALAMAT%" || (echo. & echo [GAGAL] Alamat tidak diubah. & goto :menu)

echo.
echo Menerapkan pengaturan...
"%PHP_EXE%" artisan config:clear >nul
if exist bootstrap\cache\routes-v7.php "%PHP_EXE%" artisan route:clear >nul
"%PHP_EXE%" artisan config:cache >nul
"%PHP_EXE%" artisan route:cache >nul

rem Nama (bukan IP / localhost): tawarkan menambah baris ke berkas hosts komputer INI (perlu Administrator).
set "ALAMAT_BERSIH=%ALAMAT:http://=%"
set "ALAMAT_BERSIH=%ALAMAT_BERSIH:https://=%"
for /f "tokens=1 delims=:/" %%h in ("%ALAMAT_BERSIH%") do set "HOST=%%h"
echo %HOST%| findstr /r "^[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*$" >nul && goto :ringkas
if /i "%HOST%"=="localhost" goto :ringkas
echo.
echo Nama "%HOST%" harus diarahkan ke IP %IPLOKAL% agar bisa dibuka:
echo   - Semua perangkat : tambahkan di DNS router (INSTALL.md bagian 5.3), atau
echo   - Komputer ini saja : tambahkan ke berkas hosts.
set "HOSTS="
set /p "HOSTS=Tambahkan ke berkas hosts komputer ini sekarang? (perlu Administrator) [y/N]: "
if /i not "%HOSTS%"=="y" goto :ringkas
findstr /i /c:"%HOST%" "%SystemRoot%\System32\drivers\etc\hosts" >nul 2>&1 && (echo Sudah ada di berkas hosts; dilewati. & goto :ringkas)
>>"%SystemRoot%\System32\drivers\etc\hosts" echo %IPLOKAL%   %HOST%
if errorlevel 1 (echo [GAGAL] Tidak bisa menulis berkas hosts. Jalankan skrip ini sebagai Administrator.) else (echo Ditambahkan: %IPLOKAL%   %HOST%)

:ringkas
echo.
echo ============================================================
echo   SELESAI. Buka di peramban:
for /f "delims=" %%i in ('"%PHP_EXE%" scripts\ubah-url.php --tampil') do echo     %%i
echo   Perangkat lain di Wi-Fi/LAN yang sama juga memakai alamat ini.
echo   Pastikan firewall terbuka: scripts\buka-firewall.bat
echo ============================================================
:selesai
echo.
pause
exit /b 0
