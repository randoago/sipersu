@echo off
rem Dipanggil Windows Task Scheduler SETIAP MENIT. Menjalankan: antrean email, backup harian/mingguan, pemeriksaan backup.
cd /d "%~dp0.."
call scripts\_php.bat || exit /b 1
"%PHP_EXE%" artisan schedule:run >> storage\logs\scheduler.log 2>&1
exit /b 0
