$ErrorActionPreference = 'Stop'
$package = 'D:\hotrobieumau\output\apache-direct-http80-20260925'
. "$package\PATCH-HTTP80.ps1"
$root = $PSScriptRoot.Replace('\','/')
New-Item -ItemType Directory -Path "$PSScriptRoot\public\directory" -Force | Out-Null
$main = Get-Content -LiteralPath 'D:\hotrobieumau\output\apache-http-80-20260924\conf\httpd.conf' -Raw
$main = $main.Replace('Listen 80', 'Listen 8080').Replace('ServerName 10.142.0.15:80', 'ServerName localhost:8080').Replace('#Include conf/extra/httpd-ssl.conf', 'Include conf/extra/httpd-ssl.conf')
$template = Get-Content -LiteralPath "$package\httpd-vhosts-http80.conf" -Raw
$fakeEnv = "APP_URL=https://10.142.0.15`r`nDB_PASSWORD=`"dummy-unchanged`"`r`nAPP_KEY=dummy-key`r`nSESSION_DRIVER=file`r`nSESSION_SECURE_COOKIE=true`r`nAPP_URL=https://old.example`r`n"
$patch = Get-DirectHttp80Patch -Main $main -Environment $fakeEnv -VhostTemplate $template
if ($patch.Environment -notmatch [regex]::Escape('DB_PASSWORD="dummy-unchanged"') -or $patch.Environment -notmatch 'APP_KEY=dummy-key' -or $patch.Environment -notmatch 'SESSION_DRIVER=file') { throw 'Unrelated environment values changed.' }
if ([regex]::Matches($patch.Environment, '(?im)^APP_URL=').Count -ne 1) { throw 'Duplicate APP_URL was not consolidated.' }
$second = Get-DirectHttp80Patch -Main $patch.Main -Environment $patch.Environment -VhostTemplate $template
if ($second.Main -cne $patch.Main -or $second.Environment -cne $patch.Environment) { throw 'Patch not idempotent.' }
$extraListen = Get-DirectHttp80Patch -Main ($main + "`r`nListen 443`r`n") -Environment $fakeEnv -VhostTemplate $template
if ([regex]::Matches($extraListen.Main, '(?im)^Listen ').Count -ne 1) { throw 'Extra active listener remains.' }
$absoluteMain = $main.Replace('conf/extra/httpd-ssl.conf', 'D:/xampp/apache/conf/extra/httpd-ssl.conf').Replace('conf/extra/httpd-vhosts.conf', '${SRVROOT}/conf/extra/httpd-vhosts.conf')
$absolute = Get-DirectHttp80Patch -Main $absoluteMain -Environment $fakeEnv -VhostTemplate $template
if ($absolute.Main -match '(?im)^Include .*httpd-ssl.conf' -or [regex]::Matches($absolute.Main,'(?im)^Include .*httpd-vhosts.conf').Count -ne 1) { throw 'Absolute/variable include handling failed.' }
foreach ($script in @('PATCH-HTTP80.ps1','SUA-HTTP80.ps1','KIEM-TRA-HTTP80.ps1')) {
    $tokens = $null
    $errors = $null
    $ast = [Management.Automation.Language.Parser]::ParseFile("$package\$script", [ref]$tokens, [ref]$errors)
    if ($errors.Count) { throw ($errors | Out-String) }
    if ($script -eq 'SUA-HTTP80.ps1') {
        # Load only the native-check helper, never the installer itself.
        $helper = $ast.Find({param($node) $node -is [Management.Automation.Language.FunctionDefinitionAst] -and $node.Name -eq 'Invoke-NativeCheck'}, $true)
        Invoke-Expression $helper.Extent.Text
    }
}
$baseMain = $patch.Main.Replace('Include "D:/xampp/apache/conf/extra/httpd-vhosts.conf"', ('Include "' + $root + '/httpd-vhosts.conf"'))
$baseMain = $baseMain.Replace('ErrorLog "logs/error.log"', ('ErrorLog "' + $root + '/error.log"')).Replace('CustomLog "logs/access.log" combined', ('CustomLog "' + $root + '/access.log" combined'))
$baseMain += "`r`nPidFile `"$root/test-httpd.pid`"`r`n"
$baseVhosts = $patch.Vhosts.Replace('D:/hotrobieumau/public', "$root/public").Replace('logs/hotrobieumau-http-error.log', "$root/app-error.log").Replace('logs/hotrobieumau-http-access.log', "$root/app-access.log")
[IO.File]::WriteAllText("$PSScriptRoot\httpd.conf", $baseMain, [Text.Encoding]::ASCII)
[IO.File]::WriteAllText("$PSScriptRoot\httpd-vhosts.conf", $baseVhosts, [Text.Encoding]::ASCII)
$httpd = 'D:\xampp\apache\bin\httpd.exe'
$syntax = Invoke-NativeCheck $httpd @('-t','-f',"$PSScriptRoot\httpd.conf")
if ($syntax.Code -ne 0) { throw ($syntax.Output -join "`n") }
$modules = Invoke-NativeCheck $httpd @('-M','-f',"$PSScriptRoot\httpd.conf")
if ($modules.Code -ne 0 -or ($modules.Output -join "`n") -notmatch '(?im)^\s*php_module\s+') { throw 'PHP module is not active.' }
$dump = Invoke-NativeCheck $httpd @('-S','-f',"$PSScriptRoot\httpd.conf")
$dumpText = $dump.Output -join "`n"
if ($dump.Code -ne 0 -or $dumpText -notmatch '(?im)^\*:80\s+10\.142\.0\.15\b' -or $dumpText -match '(?im)^[^\r\n]*:443\s+') { throw ('Direct port-80 vhost assertion failed: ' + $dumpText) }
Write-Output ($syntax.Output -join "`n")
Write-Output $dumpText
# Runtime uses a separate, free loopback port. No live server config or database is touched.
[IO.File]::WriteAllText("$PSScriptRoot\httpd.conf", $baseMain.Replace('Listen 80','Listen 127.0.0.1:18080'), [Text.Encoding]::ASCII)
[IO.File]::WriteAllText("$PSScriptRoot\httpd-vhosts.conf", $baseVhosts.Replace('<VirtualHost *:80>','<VirtualHost *:18080>'), [Text.Encoding]::ASCII)
$probe = @'
<?php
require 'D:/hotrobieumau/vendor/autoload.php';
$request = Illuminate\Http\Request::capture();
$url = new Illuminate\Routing\UrlGenerator(new Illuminate\Routing\RouteCollection(), $request);
if ($request->query('redirect')) { header('Location: '.$url->to('/login_admin'), true, 302); exit; }
header('Content-Type: application/json');
echo json_encode(['origin'=>$request->getSchemeAndHttpHost(),'url'=>$url->to('/login_admin'),'query'=>$request->query('q'),'method'=>$request->method(),'post'=>$request->input('sample')]);
'@
[IO.File]::WriteAllText("$PSScriptRoot\public\index.php", $probe, [Text.Encoding]::ASCII)
Copy-Item -LiteralPath 'D:\hotrobieumau\public\.htaccess' -Destination "$PSScriptRoot\public\.htaccess" -Force
if (@([Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners() | Where-Object { $_.Port -eq 18080 }).Count -gt 0) { throw 'Runtime test port occupied.' }
$process = $null
$client = $null
try {
    $process = Start-Process -FilePath $httpd -ArgumentList @('-X','-f',('"' + "$PSScriptRoot\httpd.conf" + '"')) -WindowStyle Hidden -PassThru -RedirectStandardOutput "$PSScriptRoot\stdout.txt" -RedirectStandardError "$PSScriptRoot\stderr.txt"
    Add-Type -AssemblyName System.Net.Http
    $handler = New-Object Net.Http.HttpClientHandler
    $handler.UseProxy = $false
    $handler.AllowAutoRedirect = $false
    $client = New-Object Net.Http.HttpClient($handler)
    $client.Timeout = [TimeSpan]::FromSeconds(3)
    $client.DefaultRequestHeaders.Host = '10.142.0.15'
    $response = $null
    for ($n=0; $n -lt 20; $n++) {
        Start-Sleep -Milliseconds 250
        if ($process.HasExited) { throw 'Isolated Apache stopped unexpectedly.' }
        try { $response = $client.GetAsync('http://127.0.0.1:18080/login_admin?q=hello%20world').GetAwaiter().GetResult(); break } catch { }
    }
    if (-not $response) { throw 'Isolated Apache did not respond.' }
    $body = $response.Content.ReadAsStringAsync().GetAwaiter().GetResult()
    if ([int]$response.StatusCode -ne 200) { throw ('PHP/htaccess route test failed: ' + $body) }
    $data = $body | ConvertFrom-Json
    if ($data.origin -ne 'http://10.142.0.15' -or $data.url -ne 'http://10.142.0.15/login_admin' -or $data.query -ne 'hello world') { throw ('Public URL failed: ' + $body) }
    $form = New-Object Net.Http.StringContent('sample=posted-value',[Text.Encoding]::UTF8,'application/x-www-form-urlencoded')
    $post = $client.PostAsync('http://127.0.0.1:18080/logins_admin',$form).GetAwaiter().GetResult()
    $postData = $post.Content.ReadAsStringAsync().GetAwaiter().GetResult() | ConvertFrom-Json
    if ($postData.method -ne 'POST' -or $postData.post -ne 'posted-value') { throw 'POST body or method was lost.' }
    $redirect = $client.GetAsync('http://127.0.0.1:18080/?redirect=1').GetAwaiter().GetResult()
    if ([int]$redirect.StatusCode -ne 302 -or $redirect.Headers.Location.ToString() -ne 'http://10.142.0.15/login_admin') { throw 'Laravel URL generation redirect failed.' }
    $directory = $client.GetAsync('http://127.0.0.1:18080/directory').GetAwaiter().GetResult()
    if ([int]$directory.StatusCode -ne 301 -or $directory.Headers.Location.ToString() -ne 'http://10.142.0.15/directory/') { throw ('Directory redirect failed: ' + $directory.Headers.Location) }
    foreach ($path in @('/phpmyadmin/','/php-cgi/php-cgi.exe','/server-status','/storage/documents/test.pdf')) {
        $blocked = $client.GetAsync('http://127.0.0.1:18080'+$path).GetAwaiter().GetResult()
        if ([int]$blocked.StatusCode -ne 403) { throw ('Blocked path is accessible: ' + $path) }
    }
    Write-Output 'PASS: direct port-80 config, native check helper, PHP module, isolated startup, original app htaccess routing, GET/POST, public HTTP URL generation/redirects, blocked management/document paths, env preservation, duplicate env consolidation, repeated patch stability, extra listener removal, absolute/variable includes.'
} finally {
    if ($client) { $client.Dispose() }
    if ($process -and -not $process.HasExited) { Stop-Process -Id $process.Id -Force }
}
