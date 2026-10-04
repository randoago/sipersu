@echo off
chcp 65001 >nul
cd /d "%~dp0.."
call scripts\_php.bat || exit /b 1
set /p BERKAS=Tarik berkas backup .zip ke jendela ini lalu tekan Enter: 
"%PHP_EXE%" artisan backup:verify %BERKAS%
"%PHP_EXE%" artisan backup:restore %BERKAS% --uji-saja
pause
