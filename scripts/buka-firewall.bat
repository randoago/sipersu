@echo off
rem JALANKAN SEBAGAI ADMINISTRATOR (klik kanan ^> Run as administrator).
rem Membuka port 80 HANYA untuk jaringan lokal (LocalSubnet), bukan internet.
netsh advfirewall firewall delete rule name="SIPERSU HTTP (LAN)" >nul 2>&1
netsh advfirewall firewall add rule name="SIPERSU HTTP (LAN)" dir=in action=allow protocol=TCP localport=80 profile=private,domain remoteip=LocalSubnet
echo.
echo Aturan firewall dibuat. Pastikan profil jaringan Wi-Fi/LAN diatur sebagai "Private".
pause
