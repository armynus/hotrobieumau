Set-StrictMode -Version Latest

function Get-HttpBackendPatch {
    param([string]$Main, [string]$Vhosts, [string]$Environment)

    $listen = '(?im)^[ \t]*Listen[ \t]+[^\r\n]+'
    $name = '(?im)^[ \t]*ServerName[ \t]+[^\r\n]+'
    $vhostInclude = '(?im)^[ \t]*Include[ \t]+"?conf[/\\]extra[/\\]httpd-vhosts\.conf"?[ \t]*\r?$'
    $sslInclude = '(?im)^([ \t]*)Include[ \t]+"?conf[/\\]extra[/\\]httpd-ssl\.conf"?[ \t]*\r?$'
    if ([regex]::Matches($Main, $listen).Count -ne 1) { throw 'Expected exactly one active Listen in httpd.conf. No changes made.' }
    if ([regex]::Matches($Main, $name).Count -ne 1) { throw 'Expected exactly one main ServerName. No changes made.' }
    if ([regex]::Matches($Main, $vhostInclude).Count -ne 1) { throw 'Expected the standard active httpd-vhosts.conf include.' }
    if ($Main -notmatch '(?im)^\s*LoadModule\s+headers_module\s+' -or $Main -notmatch '(?im)^\s*LoadModule\s+rewrite_module\s+') {
        throw 'mod_headers and mod_rewrite must already be loaded. No changes made.'
    }
    # Only replace the single known application vhost; refuse unfamiliar layouts.
    $blocks = [regex]::Matches($Vhosts, '(?ims)^[ \t]*<VirtualHost\s+[^>]+>.*?^[ \t]*</VirtualHost>[ \t]*')
    if ($blocks.Count -ne 1 -or $blocks[0].Value -notmatch '(?im)^\s*ServerName\s+(?:http://)?10\.142\.0\.15(?::\d+)?\s*$') {
        throw 'Expected one application VirtualHost for 10.142.0.15. Other vhosts were detected or the app was not found.'
    }
    if ($blocks[0].Value -notmatch '(?im)^\s*DocumentRoot\s+"?D:/hotrobieumau/public"?\s*$') {
        throw 'Application DocumentRoot is different from D:/hotrobieumau/public. No changes made.'
    }

    $newMain = [regex]::Replace($Main, $listen, 'Listen 127.0.0.1:8080')
    $newMain = [regex]::Replace($newMain, $name, 'ServerName 127.0.0.1:8080')
    $newMain = [regex]::Replace($newMain, $sslInclude, '#Include conf/extra/httpd-ssl.conf')
    $block = @'
<VirtualHost 127.0.0.1:8080>
    # IIS receives public HTTP on port 80 and proxies to this loopback listener.
    ServerName http://10.142.0.15:80
    UseCanonicalName On
    UseCanonicalPhysicalPort Off
    DocumentRoot "D:/hotrobieumau/public"

    # Supply the public origin to PHP without changing global ARR host settings.
    RequestHeader set Host "10.142.0.15"
    RequestHeader unset Forwarded
    RequestHeader set X-Forwarded-Host "10.142.0.15"
    RequestHeader set X-Forwarded-Proto "http"
    RequestHeader set X-Forwarded-Port "80"

    <Directory "D:/hotrobieumau/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require local
    </Directory>

    # IIS connects locally. Do not expose inherited XAMPP management aliases.
    <LocationMatch "(?i)^/(?:phpmyadmin|webalizer|xampp|php-cgi|cgi-bin|licenses|server-status|server-info)(?:/|$)">
        Require all denied
    </LocationMatch>

    ErrorLog "logs/hotrobieumau-http-error.log"
    CustomLog "logs/hotrobieumau-http-access.log" combined
</VirtualHost>
'@
    $newline = if ($Vhosts.Contains("`r`n")) { "`r`n" } else { "`n" }
    $block = ($block -replace '\r?\n', $newline)
    $match = $blocks[0]
    $newVhosts = $Vhosts.Substring(0, $match.Index) + $block + $Vhosts.Substring($match.Index + $match.Length)
    $values = [ordered]@{
        APP_URL = 'http://10.142.0.15'
        ASSET_URL = 'http://10.142.0.15'
        SESSION_SECURE_COOKIE = 'false'
        SESSION_DOMAIN = 'null'
        SESSION_SAME_SITE = 'lax'
        SESSION_PARTITIONED_COOKIE = 'false'
        SESSION_COOKIE = 'hotrobieumau_http_session'
    }
    $envNewline = if ($Environment.Contains("`r`n")) { "`r`n" } else { "`n" }
    $newEnvironment = $Environment
    foreach ($entry in $values.GetEnumerator()) {
        $pattern = '(?im)^[ \t]*' + [regex]::Escape($entry.Key) + '[ \t]*=[^\r\n]*'
        $count = [regex]::Matches($newEnvironment, $pattern).Count
        if ($count -gt 1) { throw ('Duplicate .env key: ' + $entry.Key + '. No changes made.') }
        $line = $entry.Key + '=' + $entry.Value
        if ($count -eq 1) { $newEnvironment = [regex]::Replace($newEnvironment, $pattern, $line) }
        else { $newEnvironment = $newEnvironment.TrimEnd("`r", "`n") + $envNewline + $line + $envNewline }
    }
    [pscustomobject]@{ Main = $newMain; Vhosts = $newVhosts; Environment = $newEnvironment }
}
