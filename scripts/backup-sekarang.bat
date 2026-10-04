@echo off
chcp 65001 >nul
cd /d "%~dp0.."
call scripts\_php.bat || exit /b 1
"%PHP_EXE%" artisan backup:run --jenis=harian
"%PHP_EXE%" artisan backup:list --n=5
pause
