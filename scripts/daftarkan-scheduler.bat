@echo off
rem JALANKAN SEBAGAI ADMINISTRATOR. Membuat tugas "SIPERSU Scheduler" yang berjalan tiap menit, juga saat tidak ada yang login.
set "SKRIP=%~dp0jalankan-scheduler.bat"
schtasks /Create /TN "SIPERSU Scheduler" /TR "\"%SKRIP%\"" /SC MINUTE /MO 1 /RU SYSTEM /RL HIGHEST /F
if errorlevel 1 (echo [GAGAL] Jalankan sebagai Administrator. & pause & exit /b 1)
echo Tugas "SIPERSU Scheduler" dibuat. Periksa: Task Scheduler ^> Task Scheduler Library.
pause
