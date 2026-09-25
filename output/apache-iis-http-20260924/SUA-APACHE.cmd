@echo off
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0SUA-APACHE.ps1"
echo.
echo Ket qua: "%~dp0KET-QUA-SUA-APACHE.txt"
echo.
pause
