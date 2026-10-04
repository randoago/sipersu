@echo off
chcp 65001 >nul
cd /d "%~dp0.."
call scripts\_php.bat || exit /b 1
echo ============================================================
echo  Arsip semester ke FLASHDISK (jalankan tiap akhir semester)
echo ============================================================
set /p DRIVE=Huruf drive flashdisk (contoh E): 
if "%DRIVE%"=="" (echo Dibatalkan. & pause & exit /b 1)
if not exist "%DRIVE%:\" (echo Drive %DRIVE%: tidak ditemukan. Colokkan flashdisk dulu. & pause & exit /b 1)
"%PHP_EXE%" artisan backup:run --jenis=manual --tujuan="%DRIVE%:\arsip-sipersu"
"%PHP_EXE%" artisan kunci:cadangkan "%DRIVE%:\arsip-sipersu\kunci"
echo.
echo Selesai. Simpan flashdisk di brankas Dekanat; sandi backup dicatat sesuai SOP (INSTALL.md bagian 12).
pause
