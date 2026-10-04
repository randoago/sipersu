@echo off
chcp 65001 >nul
setlocal
cd /d "%~dp0.."
call scripts\_php.bat || goto :gagal
echo.
echo === PEMASANGAN SIPERSU FT-UMB ===
echo PHP: %PHP_EXE%
"%PHP_EXE%" -r "exit(version_compare(PHP_VERSION,'8.2.0','>=')?0:1);" || (echo [GALAT] Dibutuhkan PHP 8.2 atau lebih baru. & goto :gagal)
"%PHP_EXE%" -r "foreach(['pdo_sqlite','sqlite3','zip','gd','sodium','mbstring','fileinfo','openssl','intl','xml'] as $e){ if(!extension_loaded($e)){ echo '[GALAT] Ekstensi PHP belum aktif: '.$e.PHP_EOL; $g=1; } } exit($g??0);" || (echo Aktifkan ekstensi di Laragon: Menu ^> PHP ^> Extensions. & goto :gagal)

if not exist .env (
  copy /y .env.example .env >nul
  echo Berkas .env dibuat. EDIT .env sekarang ^(APP_URL, BACKUP_PASSWORD, dll.^) lalu jalankan skrip ini lagi.
  notepad .env
  pause
)

if not exist vendor\autoload.php (
  echo Memasang pustaka PHP ^(butuh internet sekali saja^)...
  "%PHP_EXE%" "%COMPOSER_PHAR%" install --no-dev --optimize-autoloader --no-interaction || goto :gagal
)

findstr /b /c:"APP_KEY=base64:" .env >nul || "%PHP_EXE%" artisan key:generate --force
set "BARU=0"
if not exist database\sipersu.sqlite ( type nul > database\sipersu.sqlite & set "BARU=1" )
for %%A in (database\sipersu.sqlite) do if %%~zA==0 set "BARU=1"

echo Membuat tabel basis data...
"%PHP_EXE%" artisan migrate --force || goto :gagal

if "%BARU%"=="1" (
  echo Mengisi data awal ^(peran, prodi, klasifikasi, jenis surat, akun contoh^)...
  "%PHP_EXE%" artisan db:seed --force || goto :gagal
)

if not exist storage\keys\ed25519.secret (
  echo Membuat kunci tanda tangan elektronik fakultas...
  "%PHP_EXE%" artisan kunci:buat || goto :gagal
)
"%PHP_EXE%" artisan kunci:publikasi
"%PHP_EXE%" artisan config:cache
"%PHP_EXE%" artisan route:cache
"%PHP_EXE%" artisan view:cache

echo.
echo === SELESAI ===
echo Buka: lihat APP_URL pada .env. Akun contoh: lihat INSTALL.md ^(kata sandi: password — SEGERA GANTI^).
echo PENTING: jalankan  php artisan kunci:cadangkan D:\kunci-sipersu  dan simpan salinannya di tempat aman.
pause
exit /b 0
:gagal
echo.
echo [GAGAL] Pemasangan berhenti. Baca pesan di atas.
pause
exit /b 1
