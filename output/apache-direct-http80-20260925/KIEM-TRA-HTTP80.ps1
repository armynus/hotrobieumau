$ErrorActionPreference = 'Continue'
$report = Join-Path $PSScriptRoot 'KET-QUA-CHAY-HTTP80.txt'
& {
    'LIVE HTTP CHECK - READ ONLY - ' + (Get-Date -Format s)
    hostname
    '--- APACHE AND PORT 80 ---'
    Get-Process -Name httpd -ErrorAction SilentlyContinue | Select-Object Id,Path | Format-Table -AutoSize | Out-String
    if (Get-Command Get-NetTCPConnection -ErrorAction SilentlyContinue) {
        Get-NetTCPConnection -State Listen -LocalPort 80 -ErrorAction SilentlyContinue | Select-Object LocalAddress,LocalPort,OwningProcess | Format-Table -AutoSize | Out-String
    } else { netstat -ano -p tcp | Select-String ':80\s+.*LISTENING' }
    '--- HTTP REQUESTS; NO CREDENTIALS SENT; NO REDIRECTS FOLLOWED ---'
    foreach ($url in @('http://127.0.0.1/login_admin','http://10.142.0.15/login_admin','http://10.142.0.15/')) {
        $response = $null
        try {
            $request = [Net.HttpWebRequest]::Create($url)
            $request.Proxy = $null
            $request.AllowAutoRedirect = $false
            $request.Timeout = 10000
            $request.Method = 'GET'
            try { $response = $request.GetResponse() } catch [Net.WebException] {
                if ($_.Exception.Response) { $response = $_.Exception.Response } else { throw }
            }
            $status = [int]$response.StatusCode
            "$url -> HTTP $status"
            if ($response.Headers['Location']) { 'Location: ' + $response.Headers['Location'] }
            if ($status -ge 500) { 'Connected to HTTP server; investigate application/server error log.' }
        } catch { "$url -> CONNECTION FAILED: $($_.Exception.Message)" }
        finally { if ($response) { $response.Close() } }
    }
    '--- APACHE SYNTAX / VHOSTS ---'
    & 'D:\xampp\apache\bin\httpd.exe' -t -f 'D:/xampp/apache/conf/httpd.conf' 2>&1 | ForEach-Object { [string]$_ }
    & 'D:\xampp\apache\bin\httpd.exe' -S -f 'D:/xampp/apache/conf/httpd.conf' 2>&1 | ForEach-Object { [string]$_ }
    '--- RECENT APACHE LOG ---'
    if (Test-Path -LiteralPath 'D:\xampp\apache\logs\error.log') { Get-Content -LiteralPath 'D:\xampp\apache\logs\error.log' -Tail 25 }
    '--- RECENT WINDOWS APPLICATION ERRORS RELATED TO APACHE ---'
    Get-WinEvent -FilterHashtable @{LogName='Application'; StartTime=(Get-Date).AddHours(-4); Level=1,2} -MaxEvents 100 -ErrorAction SilentlyContinue | Where-Object { $_.Message -match 'httpd\.exe|php8apache|Apache' } | Select-Object -First 5 TimeCreated,Id,ProviderName,Message | Format-List | Out-String
    'END. No configuration or service was changed.'
} 2>&1 | ForEach-Object { [string]$_ } | Set-Content -LiteralPath $report -Encoding UTF8
Get-Content -LiteralPath $report
Write-Host ('Report: ' + $report)
