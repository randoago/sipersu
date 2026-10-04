@echo off
rem Mencari php.exe milik Laragon secara otomatis (versi terbaru yang terpasang).
rem Bila Laragon tidak di C:\laragon, ubah LARAGON_DIR di bawah ini.
set "LARAGON_DIR=C:\laragon"
set "PHP_EXE="
for /d %%d in ("%LARAGON_DIR%\bin\php\php-*") do set "PHP_EXE=%%d\php.exe"
if not defined PHP_EXE (
  echo [GALAT] php.exe Laragon tidak ditemukan di %LARAGON_DIR%\bin\php
  echo         Pasang Laragon dahulu, atau ubah LARAGON_DIR pada scripts\_php.bat
  exit /b 1
)
rem Composer bawaan Laragon
set "COMPOSER_PHAR="
for /d %%d in ("%LARAGON_DIR%\bin\composer") do set "COMPOSER_PHAR=%%d\composer.phar"
exit /b 0
