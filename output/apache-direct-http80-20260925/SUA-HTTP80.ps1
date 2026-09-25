# Apply only on server 10.142.0.15, with Apache stopped and TCP port 80 free.
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
. (Join-Path $PSScriptRoot 'PATCH-HTTP80.ps1')
$report = Join-Path $PSScriptRoot 'KET-QUA-HTTP80.txt'
$backup = $null
$changed = $false
$cacheExisted = $false
$byteEncoding = [Text.Encoding]::GetEncoding(28591)
function Write-Result([string]$Message) {
    Write-Host $Message
    Add-Content -LiteralPath $report -Value $Message -Encoding UTF8
}
function Read-Original([string]$Path) { $byteEncoding.GetString([IO.File]::ReadAllBytes($Path)) }
function Write-Original([string]$Path, [string]$Text) { [IO.File]::WriteAllBytes($Path, $byteEncoding.GetBytes($Text)) }
function Invoke-NativeCheck([string]$Executable, [string[]]$Arguments) {
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & $Executable @Arguments 2>&1
        $code = $LASTEXITCODE
    } finally { $ErrorActionPreference = $oldPreference }
    [pscustomobject]@{ Output = @($output); Code = $code }
}

try {
    Set-Content -LiteralPath $report -Value ('Direct Apache HTTP port 80 - ' + (Get-Date -Format s)) -Encoding UTF8
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { throw 'Right-click SUA-HTTP80.cmd and choose Run as administrator.' }
    $addresses = @([Net.NetworkInformation.NetworkInterface]::GetAllNetworkInterfaces() | ForEach-Object { $_.GetIPProperties().UnicastAddresses } | ForEach-Object { $_.Address.ToString() })
    if ($addresses -notcontains '10.142.0.15') { throw 'This is not server 10.142.0.15. No server configuration was changed.' }
    if (Get-Process -Name httpd -ErrorAction SilentlyContinue) { throw 'Apache is running. Stop Apache in XAMPP, then run this file again.' }
    $listeners = @([Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() | Where-Object { $_.Port -eq 80 })
    if ($listeners.Count -gt 0) {
        Write-Result 'TCP port 80 is occupied. Current listeners:'
        foreach ($listener in $listeners) { Write-Result $listener.ToString() }
        if (Get-Command Get-NetTCPConnection -ErrorAction SilentlyContinue) {
            Get-NetTCPConnection -State Listen -LocalPort 80 -ErrorAction SilentlyContinue | ForEach-Object { Write-Result ('PID ' + $_.OwningProcess + ' at ' + $_.LocalAddress + ':' + $_.LocalPort) }
        }
        throw 'Port 80 is not free. No configuration was changed. Send KET-QUA-HTTP80.txt.'
    }

    $main = 'D:\xampp\apache\conf\httpd.conf'
    $vhosts = 'D:\xampp\apache\conf\extra\httpd-vhosts.conf'
    $envFile = 'D:\hotrobieumau\.env'
    $httpd = 'D:\xampp\apache\bin\httpd.exe'
    $php = 'D:\xampp\php\php.exe'
    $artisan = 'D:\hotrobieumau\artisan'
    $cache = 'D:\hotrobieumau\bootstrap\cache\config.php'
    $template = Join-Path $PSScriptRoot 'httpd-vhosts-http80.conf'
    foreach ($path in @($main, $envFile, $httpd, $php, $artisan, $template, 'D:\hotrobieumau\public\index.php')) {
        if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw ('Required file missing: ' + $path) }
    }
    $vhostsExisted = Test-Path -LiteralPath $vhosts -PathType Leaf
    if (-not (Test-Path -LiteralPath 'D:\xampp\apache\conf\extra' -PathType Container)) { throw 'Apache conf/extra directory was not found.' }
    $patch = Get-DirectHttp80Patch -Main (Read-Original $main) -Environment (Read-Original $envFile) -VhostTemplate (Read-Original $template)
    # This backup contains secrets from .env. It stays outside public/ and must not be shared.
    $backup = Join-Path 'D:\xampp\apache\conf' ('backup-direct-http80-' + (Get-Date -Format 'yyyyMMdd-HHmmss-fff'))
    New-Item -ItemType Directory -Path $backup -ErrorAction Stop | Out-Null
    Copy-Item -LiteralPath $main -Destination (Join-Path $backup 'httpd.conf')
    if ($vhostsExisted) { Copy-Item -LiteralPath $vhosts -Destination (Join-Path $backup 'httpd-vhosts.conf') }
    Copy-Item -LiteralPath $envFile -Destination (Join-Path $backup 'laravel.env')
    $cacheExisted = Test-Path -LiteralPath $cache -PathType Leaf
    if ($cacheExisted) { Copy-Item -LiteralPath $cache -Destination (Join-Path $backup 'config-cache.php') }
    Write-Result ('Backup saved: ' + $backup)
    $changed = $true
    Write-Original $main $patch.Main
    Write-Original $vhosts $patch.Vhosts
    Write-Original $envFile $patch.Environment

    $syntax = Invoke-NativeCheck $httpd @('-t', '-f', $main)
    foreach ($line in $syntax.Output) { Write-Result ([string]$line) }
    if ($syntax.Code -ne 0) { throw 'Apache syntax test failed.' }
    $modules = Invoke-NativeCheck $httpd @('-M', '-f', $main)
    if ($modules.Code -ne 0 -or ($modules.Output -join "`n") -notmatch '(?im)^\s*php_module\s+') {
        throw 'The expected XAMPP PHP module is not loaded. Restoring configuration to avoid serving PHP source.'
    }
    $dump = Invoke-NativeCheck $httpd @('-S', '-f', $main)
    foreach ($line in $dump.Output) { Write-Result ([string]$line) }
    if ($dump.Code -ne 0) { throw 'Apache virtual host test failed.' }
    $dumpText = $dump.Output -join "`n"
    if ($dumpText -notmatch '(?im)^\*:80\s+10\.142\.0\.15\b' -or $dumpText -match '(?im)^[^\r\n]*:443\s+') {
        throw 'The active virtual hosts are not the expected single HTTP port-80 site. Review the vhost dump above.'
    }

    Push-Location 'D:\hotrobieumau'
    try {
        $clear = Invoke-NativeCheck $php @($artisan, 'config:clear', '--no-interaction')
        if ($clear.Code -ne 0) { throw 'Laravel config:clear failed. Check the Laravel log on this server. Application error output is omitted from this shareable report.' }
    } finally { Pop-Location }
    $changed = $false
    Write-Result 'OK - APACHE HTTP 80 CONFIGURED.'
    Write-Result 'Listen 80; VirtualHost *:80; DocumentRoot D:/hotrobieumau/public; SSL include disabled.'
    Write-Result 'APP_URL and HTTP cookies updated. APP_KEY, database and session driver settings preserved.'
    Write-Result 'NEXT: In XAMPP set Apache Main Port to 80 if it is still 8080, then Start Apache.'
    Write-Result 'OPEN: http://10.142.0.15 (do not add :8080 or :8000).'
    Write-Result 'Run KIEM-TRA-HTTP80.cmd AFTER starting Apache for the live connection result.'
    Write-Result 'No IIS configuration or service startup type was changed. Keep IIS from taking port 80 while Apache serves it.'
    exit 0
} catch {
    $reason = $_.Exception.Message
    if ($changed -and $backup) {
        try {
            Copy-Item -LiteralPath (Join-Path $backup 'httpd.conf') -Destination $main -Force
            if ($vhostsExisted) { Copy-Item -LiteralPath (Join-Path $backup 'httpd-vhosts.conf') -Destination $vhosts -Force }
            elseif (Test-Path -LiteralPath $vhosts -PathType Leaf) { Remove-Item -LiteralPath $vhosts }
            Copy-Item -LiteralPath (Join-Path $backup 'laravel.env') -Destination $envFile -Force
            if ($cacheExisted) { Copy-Item -LiteralPath (Join-Path $backup 'config-cache.php') -Destination $cache -Force }
            Write-Result 'Original Apache and Laravel configuration restored.'
        } catch { Write-Result ('ROLLBACK ERROR: ' + $_.Exception.Message + '. Restore files manually from ' + $backup) }
    }
    Write-Result ('FAILED: ' + $reason)
    Write-Result 'No IIS service or website was started/stopped by this script.'
    exit 1
}
