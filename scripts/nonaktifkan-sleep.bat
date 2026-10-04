@echo off
rem JALANKAN SEBAGAI ADMINISTRATOR. Server tidak boleh tidur/hibernasi saat tersambung listrik.
powercfg /change standby-timeout-ac 0
powercfg /change hibernate-timeout-ac 0
powercfg /change monitor-timeout-ac 15
powercfg /change disk-timeout-ac 0
powercfg /hibernate off
rem Tutup laptop tidak mematikan layar/tidur: tombol "lid close action" = Do nothing (AC)
powercfg /setacvalueindex SCHEME_CURRENT SUB_BUTTONS LIDACTION 0
powercfg /setactive SCHEME_CURRENT
echo Selesai. Sleep dan hibernasi dimatikan saat memakai listrik (AC).
pause
