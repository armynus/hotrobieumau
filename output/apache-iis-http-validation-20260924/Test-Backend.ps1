$ErrorActionPreference = 'Stop'
. 'D:\hotrobieumau\output\apache-iis-http-20260924\PATCH-LIB.ps1'
$root = $PSScriptRoot.Replace('\','/')
New-Item -ItemType Directory -Path "$PSScriptRoot\public\directory" -Force | Out-Null
$main = Get-Content -LiteralPath 'D:\hotrobieumau\output\apache-http-80-20260924\conf\httpd.conf' -Raw
# Reproduce the actual server's mismatched main config, including active SSL include.
$main = $main.Replace('Listen 80', 'Listen 8080').Replace('ServerName 10.142.0.15:80', 'ServerName localhost:8080').Replace('#Include conf/extra/httpd-ssl.conf', 'Include conf/extra/httpd-ssl.conf')
$vhosts = Get-Content -LiteralPath 'D:\hotrobieumau\output\apache-http-80-20260924\conf\extra\httpd-vhosts.conf' -Raw
$fakeEnv = "APP_URL=https://10.142.0.15`r`nDB_PASSWORD=`"dummy-different-password`"`r`nAPP_KEY=dummy-key`r`nSESSION_DRIVER=file`r`nSESSION_SECURE_COOKIE=true`r`n"
$result = Get-HttpBackendPatch -Main $main -Vhosts $vhosts -Environment $fakeEnv
if ($result.Environment -notmatch [regex]::Escape('DB_PASSWORD="dummy-different-password"') -or $result.Environment -notmatch 'APP_KEY=dummy-key' -or $result.Environment -notmatch 'SESSION_DRIVER=file') { throw 'Unrelated .env values changed.' }
$second = Get-HttpBackendPatch -Main $result.Main -Vhosts $result.Vhosts -Environment $result.Environment
if ($second.Main -cne $result.Main -or $second.Vhosts -cne $result.Vhosts -or $second.Environment -cne $result.Environment) { throw 'Patch is not idempotent.' }
try {
    $null = Get-HttpBackendPatch -Main $main -Vhosts ($vhosts + "`r`n<VirtualHost *:81>`r`nServerName other.example`r`n</VirtualHost>") -Environment $fakeEnv
    throw 'FAILED: Accepted an unrelated additional vhost.'
} catch { if ($_.Exception.Message.StartsWith('FAILED:')) { throw } }
$testMain = $result.Main.Replace('Listen 127.0.0.1:8080', 'Listen 127.0.0.1:18080')
$testMain = $testMain.Replace('Include conf/extra/httpd-vhosts.conf', ('Include "' + $root + '/httpd-vhosts.conf"'))
$testMain = $testMain.Replace('ErrorLog "logs/error.log"', ('ErrorLog "' + $root + '/error.log"'))
$testMain = $testMain.Replace('CustomLog "logs/access.log" combined', ('CustomLog "' + $root + '/access.log" combined'))
$testMain += "`r`nPidFile `"$root/test-httpd.pid`"`r`n"
$testVhosts = $result.Vhosts.Replace('127.0.0.1:8080', '127.0.0.1:18080').Replace('D:/hotrobieumau/public', "$root/public").Replace('logs/hotrobieumau-http-error.log', "$root/app-error.log").Replace('logs/hotrobieumau-http-access.log', "$root/app-access.log")
[IO.File]::WriteAllText("$PSScriptRoot\httpd.conf", $testMain, [Text.Encoding]::ASCII)
[IO.File]::WriteAllText("$PSScriptRoot\httpd-vhosts.conf", $testVhosts, [Text.Encoding]::ASCII)
$probe = @'
<?php
require 'D:/hotrobieumau/vendor/autoload.php';
$request = Illuminate\Http\Request::capture();
$generator = new Illuminate\Routing\UrlGenerator(new Illuminate\Routing\RouteCollection(), $request);
if (isset($_GET['redirect'])) {
    header('Location: '.$generator->to('/login_admin'), true, 302);
    exit;
}
header('Content-Type: application/json');
echo json_encode(['host' => $request->getHost(), 'origin' => $request->getSchemeAndHttpHost(), 'url' => $generator->to('/login_admin'), 'query' => $request->query('q'), 'method' => $request->method(), 'post' => $request->input('sample')]);
'@
[IO.File]::WriteAllText("$PSScriptRoot\public\index.php", $probe, [Text.Encoding]::ASCII)
$parseTokens = $null
$parseErrors = $null
[Management.Automation.Language.Parser]::ParseFile('D:\hotrobieumau\output\apache-iis-http-20260924\SUA-APACHE.ps1', [ref]$parseTokens, [ref]$parseErrors) | Out-Null
if ($parseErrors.Count -gt 0) { throw ($parseErrors | Out-String) }
& 'D:\xampp\apache\bin\httpd.exe' -t -f "$PSScriptRoot\httpd.conf"
if ($LASTEXITCODE -ne 0) { throw 'Apache syntax check failed.' }
& 'D:\xampp\apache\bin\httpd.exe' -S -f "$PSScriptRoot\httpd.conf"
if ($LASTEXITCODE -ne 0) { throw 'Apache vhost check failed.' }
if (@([Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() | Where-Object { $_.Port -eq 18080 }).Count -gt 0) { throw 'Test port 18080 is occupied.' }
$process = $null
try {
    $process = Start-Process -FilePath 'D:\xampp\apache\bin\httpd.exe' -ArgumentList @('-X', '-f', ('"' + "$PSScriptRoot\httpd.conf" + '"')) -WindowStyle Hidden -PassThru -RedirectStandardOutput "$PSScriptRoot\stdout.txt" -RedirectStandardError "$PSScriptRoot\stderr.txt"
    Add-Type -AssemblyName System.Net.Http
    $handler = New-Object Net.Http.HttpClientHandler
    $handler.UseProxy = $false
    $handler.AllowAutoRedirect = $false
    $client = New-Object Net.Http.HttpClient($handler)
    $client.Timeout = [TimeSpan]::FromSeconds(3)
    $response = $null
    for ($n=0; $n -lt 20; $n++) {
        Start-Sleep -Milliseconds 250
        if ($process.HasExited) { throw 'Isolated Apache exited unexpectedly.' }
        try { $response = $client.GetAsync('http://127.0.0.1:18080/?q=hello%20world').GetAwaiter().GetResult(); break } catch { }
    }
    if (-not $response) { throw 'Isolated Apache did not respond.' }
    $body = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
    if ([int]$response.StatusCode -ne 200) { throw ('Probe failed: ' + $body) }
    $data = $body | ConvertFrom-Json
    if ($data.origin -ne 'http://10.142.0.15' -or $data.url -ne 'http://10.142.0.15/login_admin' -or $data.query -ne 'hello world') { throw ('Wrong public origin or query: ' + $body) }
    $form = New-Object Net.Http.StringContent('sample=posted-value', [Text.Encoding]::UTF8, 'application/x-www-form-urlencoded')
    $post = $client.PostAsync('http://127.0.0.1:18080/', $form).GetAwaiter().GetResult()
    $postData = $post.Content.ReadAsStringAsync().GetAwaiter().GetResult() | ConvertFrom-Json
    if ($postData.method -ne 'POST' -or $postData.post -ne 'posted-value') { throw 'POST body failed.' }
    $redirect = $client.GetAsync('http://127.0.0.1:18080/?redirect=1').GetAwaiter().GetResult()
    if ([int]$redirect.StatusCode -ne 302 -or $redirect.Headers.Location.ToString() -ne 'http://10.142.0.15/login_admin') { throw 'Laravel URL redirect is incorrect.' }
    $directory = $client.GetAsync('http://127.0.0.1:18080/directory').GetAwaiter().GetResult()
    if ([int]$directory.StatusCode -ne 301 -or $directory.Headers.Location.ToString() -ne 'http://10.142.0.15/directory/') { throw ('Apache directory redirect is incorrect: ' + $directory.Headers.Location) }
    foreach ($path in @('/phpmyadmin/', '/php-cgi/php-cgi.exe', '/server-status')) {
        $blocked = $client.GetAsync('http://127.0.0.1:18080' + $path).GetAwaiter().GetResult()
        if ([int]$blocked.StatusCode -ne 403) { throw ('Inherited management endpoint not blocked: ' + $path) }
    }
    Write-Output 'PASS: syntax, vhost, isolated startup, PHP request origin, Laravel URL/redirect, Apache directory redirect, GET query, POST body, management alias denial, unchanged unrelated env values, idempotent patch and extra-vhost refusal.'
} finally {
    if ($client) { $client.Dispose() }
    if ($process -and -not $process.HasExited) { Stop-Process -Id $process.Id -Force }
}
