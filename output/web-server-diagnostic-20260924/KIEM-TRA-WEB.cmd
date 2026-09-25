@echo off
setlocal
set "WEB_DIAG_REPORT=%~dp0KET-QUA-KIEM-TRA.txt"

echo Dang kiem tra dich vu, cong va cau hinh web. Khong thay doi cau hinh.
echo Vui long cho...

call :collect > "%WEB_DIAG_REPORT%" 2>&1
type "%WEB_DIAG_REPORT%"
echo.
echo Da luu ket qua tai: "%WEB_DIAG_REPORT%"
echo Gui file KET-QUA-KIEM-TRA.txt de xac dinh cach sua.
if /I not "%~1"=="--no-pause" pause
exit /b 0

:collect
echo WEB SERVER DIAGNOSTIC - READ ONLY
echo Please run on the failing server 10.142.0.15.
echo Collected at: %DATE% %TIME%
echo.

echo [1] MACHINE AND IPV4
hostname
ipconfig | findstr /I "IPv4"
echo.

echo [2] IIS SERVICES
sc.exe query W3SVC
sc.exe query WAS
echo.

echo [3] LISTENERS ON PORTS 80, 443, 8000, 8080
echo If no lines appear below, none of these ports have a matching TCP listener.
netstat -ano -p tcp | findstr /R /C:":80 .*LISTENING" /C:":443 .*LISTENING" /C:":8000 .*LISTENING" /C:":8080 .*LISTENING"
echo.

echo [4] WEB SERVER PROCESSES
tasklist /FI "IMAGENAME eq httpd.exe"
tasklist /FI "IMAGENAME eq php.exe"
tasklist /FI "IMAGENAME eq w3wp.exe"
echo.

echo [5] IIS SITES, APPLICATIONS AND PROXY MODULES
if not exist "%windir%\System32\inetsrv\appcmd.exe" goto no_iis
"%windir%\System32\inetsrv\appcmd.exe" list site
"%windir%\System32\inetsrv\appcmd.exe" list app
echo URL Rewrite / ARR modules:
"%windir%\System32\inetsrv\appcmd.exe" list modules | findstr /I "Rewrite Routing"
goto after_iis
:no_iis
echo IIS appcmd.exe not found at the standard Windows location.
:after_iis
echo.

echo [6] APACHE VERSION, SYNTAX AND VIRTUAL HOSTS
if not exist "D:\xampp\apache\bin\httpd.exe" goto no_apache
"D:\xampp\apache\bin\httpd.exe" -v
"D:\xampp\apache\bin\httpd.exe" -t -f "D:/xampp/apache/conf/httpd.conf"
echo Apache syntax exit code: %ERRORLEVEL%
"D:\xampp\apache\bin\httpd.exe" -S -f "D:/xampp/apache/conf/httpd.conf"
echo Apache virtual host exit code: %ERRORLEVEL%
goto after_apache
:no_apache
echo Apache executable not found at D:\xampp\apache\bin\httpd.exe.
:after_apache
echo.

echo [7] ACTIVE MAIN CONFIGURATION REFERENCES
if exist "D:\xampp\apache\conf\httpd.conf" findstr /N /R /C:"^Listen " /C:"^ServerName " /C:"^ServerRoot " /C:"^Include " "D:\xampp\apache\conf\httpd.conf"
echo.

echo [8] RECENT APACHE ERROR LOG
powershell.exe -NoProfile -Command "if (Test-Path -LiteralPath 'D:\xampp\apache\logs\error.log') { Get-Content -LiteralPath 'D:\xampp\apache\logs\error.log' -Tail 20 } else { Write-Output 'Apache error log not found.' }"
echo.
echo END OF REPORT. No service was started/stopped and no server configuration was changed.
exit /b 0
