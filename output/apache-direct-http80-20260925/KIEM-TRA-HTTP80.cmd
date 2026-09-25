@echo off
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0KIEM-TRA-HTTP80.ps1"
echo.
pause
