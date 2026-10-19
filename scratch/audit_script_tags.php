<?php

$dir = __DIR__ . '/../resources/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$scriptRefs = [];
$withFilemtime = 0;
$withoutFilemtime = 0;
$inlineScripts = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        $relPath = str_replace('\\', '/', substr($file->getPathname(), strlen(realpath(__DIR__ . '/..')) + 1));
        
        // Find all <script ...> tags
        if (preg_match_all('/<script\b([^>]*)>(.*?)<\/script>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $attrs = $m[1];
                $body = trim($m[2]);
                
                if (stripos($attrs, 'src=') !== false) {
                    $hasFm = str_contains($attrs, 'filemtime');
                    if ($hasFm) {
                        $withFilemtime++;
                    } else {
                        $withoutFilemtime++;
                    }
                    $scriptRefs[] = [
                        'file' => $relPath,
                        'type' => 'src',
                        'attrs' => trim(preg_replace('/\s+/', ' ', $attrs)),
                        'filemtime' => $hasFm
                    ];
                } else {
                    $inlineScripts++;
                    $scriptRefs[] = [
                        'file' => $relPath,
                        'type' => 'inline',
                        'len' => strlen($body),
                        'preview' => mb_substr(preg_replace('/\s+/', ' ', $body), 0, 60)
                    ];
                }
            }
        }
    }
}

echo "=== SCRIPT TAG REFERENCES AUDIT (FIXED) ===" . PHP_EOL;
echo "Total script tags found: " . count($scriptRefs) . PHP_EOL;
echo "External script tags WITH filemtime: " . $withFilemtime . PHP_EOL;
echo "External script tags WITHOUT filemtime: " . $withoutFilemtime . PHP_EOL;
echo "Inline script tags: " . $inlineScripts . PHP_EOL;
echo PHP_EOL;

echo "--- Breakdown of external scripts without filemtime ---" . PHP_EOL;
$vendorCount = 0;
$appCount = 0;
foreach ($scriptRefs as $ref) {
    if ($ref['type'] === 'src' && !$ref['filemtime']) {
        if (str_contains($ref['attrs'], 'vendor/') || str_contains($ref['attrs'], 'sweetalert') || str_contains($ref['attrs'], 'chart')) {
            $vendorCount++;
        } else {
            $appCount++;
            echo "App script: {$ref['file']} -> {$ref['attrs']}" . PHP_EOL;
        }
    }
}
echo "Total vendor scripts without filemtime: {$vendorCount}" . PHP_EOL;
echo "Total app scripts without filemtime: {$appCount}" . PHP_EOL;
