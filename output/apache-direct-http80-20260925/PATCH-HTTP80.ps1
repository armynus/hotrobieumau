Set-StrictMode -Version Latest

function Get-DirectHttp80Patch {
    param([string]$Main, [string]$Environment, [string]$VhostTemplate)

    # This package intentionally configures ONE Apache website, as requested.
    # The complete previous vhost file is backed up by the caller.
    $newMain = $Main
    $listenPattern = '(?im)^[ \t]*Listen[ \t]+[^\r\n]+'
    $listeners = [regex]::Matches($Main, $listenPattern)
    if ($listeners.Count -eq 0) { throw 'No active Listen found in httpd.conf; this is not the expected XAMPP main configuration.' }
    for ($i = $listeners.Count - 1; $i -ge 0; $i--) {
        $m = $listeners[$i]
        $replacement = if ($i -eq 0) { 'Listen 80' } else { '# Extra listener disabled for direct HTTP: ' + $m.Value.Trim() }
        $newMain = $newMain.Remove($m.Index, $m.Length).Insert($m.Index, $replacement)
    }

    $namePattern = '(?im)^[ \t]*ServerName[ \t]+[^\r\n]+'
    $names = [regex]::Matches($Main, $namePattern)
    if ($names.Count -gt 1) { throw 'Multiple ServerName directives in the main file. No changes made; review this nonstandard main configuration.' }
    if ($names.Count -eq 1) { $newMain = [regex]::Replace($newMain, $namePattern, 'ServerName 10.142.0.15:80') }
    else { $newMain += "ServerName 10.142.0.15:80`r`n" }

    # Accept relative or absolute standard includes, quoted or unquoted.
    $extraPrefix = '(?:conf[/\\]extra[/\\]|(?:D:|\$\{SRVROOT\})[/\\](?:xampp[/\\]apache[/\\])?conf[/\\]extra[/\\])'
    $sslInclude = '(?im)^[ \t]*Include(?:Optional)?[ \t]+"?' + $extraPrefix + 'httpd-ssl\.conf"?[ \t]*\r?$'
    $newMain = [regex]::Replace($newMain, $sslInclude, '# SSL include disabled for direct HTTP.')
    $vhostInclude = '(?im)^[ \t]*Include(?:Optional)?[ \t]+"?' + $extraPrefix + 'httpd-vhosts\.conf"?[ \t]*\r?$'
    $includes = [regex]::Matches($newMain, $vhostInclude)
    if ($includes.Count -eq 0) { $newMain += "`r`nInclude `"D:/xampp/apache/conf/extra/httpd-vhosts.conf`"`r`n" }
    else {
        for ($i = $includes.Count - 1; $i -ge 0; $i--) {
            $m = $includes[$i]
            $replacement = if ($i -eq 0) { 'Include "D:/xampp/apache/conf/extra/httpd-vhosts.conf"' } else { '# Duplicate application virtual host include disabled.' }
            if ($m.Value.EndsWith("`r")) { $replacement += "`r" }
            $newMain = $newMain.Remove($m.Index, $m.Length).Insert($m.Index, $replacement)
        }
    }

    # Keep the server's PHP installation and other module paths. Enable rewrite if needed.
    foreach ($module in @('rewrite', 'headers')) {
        $active = '(?im)^[ \t]*LoadModule[ \t]+' + $module + '_module[ \t]+'
        if ($newMain -notmatch $active) {
            $commented = '(?im)^[ \t]*#+[ \t]*(LoadModule[ \t]+' + $module + '_module[ \t]+[^\r\n]+)'
            $found = [regex]::Matches($newMain, $commented)
            if ($found.Count -ne 1) { throw ('Cannot find the standard ' + $module + ' module declaration in httpd.conf.') }
            $newMain = [regex]::Replace($newMain, $commented, '$1')
        }
    }
    # Catch an SSL include we did not recognize, instead of leaving certificates active silently.
    if ($newMain -match '(?im)^[ \t]*Include(?:Optional)?[ \t]+[^\r\n]*httpd-ssl\.conf') {
        throw 'An unrecognized active SSL include remains in httpd.conf. No changes made.'
    }

    $values = [ordered]@{
        APP_URL = 'http://10.142.0.15'
        ASSET_URL = 'http://10.142.0.15'
        SESSION_SECURE_COOKIE = 'false'
        SESSION_DOMAIN = 'null'
        SESSION_SAME_SITE = 'lax'
        SESSION_PARTITIONED_COOKIE = 'false'
        SESSION_COOKIE = 'hotrobieumau_http_session'
    }
    $newline = if ($Environment.Contains("`r`n")) { "`r`n" } else { "`n" }
    $newEnvironment = $Environment
    foreach ($entry in $values.GetEnumerator()) {
        $pattern = '(?im)^[ \t]*' + [regex]::Escape($entry.Key) + '[ \t]*=[^\r\n]*'
        $line = $entry.Key + '=' + $entry.Value
        # Remove duplicate values for these specific HTTP settings, keeping all other keys.
        $matches = [regex]::Matches($newEnvironment, $pattern)
        if ($matches.Count -gt 0) {
            $first = $matches[0]
            for ($i = $matches.Count - 1; $i -ge 1; $i--) {
                $m = $matches[$i]
                $newEnvironment = $newEnvironment.Remove($m.Index, $m.Length)
            }
            $newEnvironment = $newEnvironment.Remove($first.Index, $first.Length).Insert($first.Index, $line)
        } else { $newEnvironment = $newEnvironment.TrimEnd("`r", "`n") + $newline + $line + $newline }
    }
    [pscustomobject]@{ Main = $newMain; Vhosts = $VhostTemplate; Environment = $newEnvironment }
}
