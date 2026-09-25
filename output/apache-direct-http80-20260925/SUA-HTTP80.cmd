@echo off
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0SUA-HTTP80.ps1"
echo.
echo Ket qua: "%~dp0KET-QUA-HTTP80.txt"
pause
