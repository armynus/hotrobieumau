# Run on server 10.142.0.15 only. IIS configuration is deliberately a separate step.
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
. (Join-Path $PSScriptRoot 'PATCH-LIB.ps1')
$report = Join-Path $PSScriptRoot 'KET-QUA-SUA-APACHE.txt'
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

try {
    Set-Content -LiteralPath $report -Value ('Apache HTTP backend repair - ' + (Get-Date -Format s)) -Encoding UTF8
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) { throw 'Run SUA-APACHE.cmd as administrator.' }
    $addresses = @([Net.NetworkInformation.NetworkInterface]::GetAllNetworkInterfaces() | ForEach-Object { $_.GetIPProperties().UnicastAddresses } | ForEach-Object { $_.Address.ToString() })
    if ($addresses -notcontains '10.142.0.15') { throw 'This is not server 10.142.0.15. No server configuration was changed.' }
    if (Get-Process -Name httpd -ErrorAction SilentlyContinue) { throw 'Apache is running. Stop Apache in XAMPP before applying this patch. Keep IIS running.' }
    if (@([Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() | Where-Object { $_.Port -eq 8080 }).Count -gt 0) {
        throw 'Port 8080 is already in use. No changes made.'
    }
    $main = 'D:\xampp\apache\conf\httpd.conf'
    $vhosts = 'D:\xampp\apache\conf\extra\httpd-vhosts.conf'
    $envFile = 'D:\hotrobieumau\.env'
    $httpd = 'D:\xampp\apache\bin\httpd.exe'
    $php = 'D:\xampp\php\php.exe'
    $artisan = 'D:\hotrobieumau\artisan'
    $cache = 'D:\hotrobieumau\bootstrap\cache\config.php'
    foreach ($path in @($main, $vhosts, $envFile, $httpd, $php, $artisan, 'D:\hotrobieumau\public\index.php')) {
        if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw ('Required file missing: ' + $path) }
    }
    $patch = Get-HttpBackendPatch -Main (Read-Original $main) -Vhosts (Read-Original $vhosts) -Environment (Read-Original $envFile)
    # Store backups outside the application/public directory. Never send this backup: it contains .env.
    $backup = Join-Path 'D:\xampp\apache\conf' ('backup-http-loopback-' + (Get-Date -Format 'yyyyMMdd-HHmmss-fff'))
    New-Item -ItemType Directory -Path $backup -ErrorAction Stop | Out-Null
    Copy-Item -LiteralPath $main -Destination (Join-Path $backup 'httpd.conf')
    Copy-Item -LiteralPath $vhosts -Destination (Join-Path $backup 'httpd-vhosts.conf')
    Copy-Item -LiteralPath $envFile -Destination (Join-Path $backup 'laravel.env')
    $cacheExisted = Test-Path -LiteralPath $cache -PathType Leaf
    if ($cacheExisted) { Copy-Item -LiteralPath $cache -Destination (Join-Path $backup 'config-cache.php') }
    $changed = $true
    Write-Original $main $patch.Main
    Write-Original $vhosts $patch.Vhosts
    Write-Original $envFile $patch.Environment
    # Capture native stderr without treating the normal 'Syntax OK' message as a PowerShell error.
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $syntaxOutput = & $httpd -t -f $main 2>&1
    $syntaxExit = $LASTEXITCODE
    $ErrorActionPreference = $previousPreference
    foreach ($line in $syntaxOutput) { Write-Result ([string]$line) }
    if ($syntaxExit -ne 0) { throw 'Apache syntax check failed. Restoring original files.' }
    $ErrorActionPreference = 'Continue'
    $vhostOutput = & $httpd -S -f $main 2>&1
    $vhostExit = $LASTEXITCODE
    $ErrorActionPreference = $previousPreference
    foreach ($line in $vhostOutput) { Write-Result ([string]$line) }
    if ($vhostExit -ne 0) { throw 'Apache virtual host check failed. Restoring original files.' }
    Push-Location 'D:\hotrobieumau'
    try {
        $ErrorActionPreference = 'Continue'
        $clearOutput = & $php $artisan config:clear --no-interaction 2>&1
        $clearExit = $LASTEXITCODE
        $ErrorActionPreference = $previousPreference
        # Do not place application exception output or environment values into the shareable report.
        if ($clearExit -ne 0) { throw 'Laravel config:clear failed. Restoring original files. Check the Laravel log on the server.' }
    } finally { Pop-Location }
    $changed = $false
    Write-Result ('Backup: ' + $backup)
    Write-Result 'OK: Apache config is ready for 127.0.0.1:8080. HTTP cookie settings updated; DB settings and APP_KEY preserved.'
    Write-Result 'NEXT: Start Apache using XAMPP. On this server test http://127.0.0.1:8080/login_admin.'
    Write-Result 'IIS has NOT been changed. Public http://10.142.0.15 still needs the IIS proxy step in HUONG-DAN.md.'
    exit 0
} catch {
    $reason = $_.Exception.Message
    if ($changed -and $backup) {
        try {
            Copy-Item -LiteralPath (Join-Path $backup 'httpd.conf') -Destination $main -Force
            Copy-Item -LiteralPath (Join-Path $backup 'httpd-vhosts.conf') -Destination $vhosts -Force
            Copy-Item -LiteralPath (Join-Path $backup 'laravel.env') -Destination $envFile -Force
            if ($cacheExisted) { Copy-Item -LiteralPath (Join-Path $backup 'config-cache.php') -Destination $cache -Force }
            Write-Result 'Original Apache and Laravel configuration restored.'
        } catch { Write-Result ('ROLLBACK ERROR: ' + $_.Exception.Message + '. Restore files manually from ' + $backup) }
    }
    Write-Result ('FAILED: ' + $reason)
    Write-Result 'No IIS service or website was stopped by this script.'
    exit 1
}
