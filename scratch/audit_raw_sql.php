<?php

$dir = __DIR__ . '/../app';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$rawOccurrences = [];

$patterns = [
    'DB::raw' => '/DB::raw\s*\((.*?)\)/is',
    'whereRaw' => '/->whereRaw\s*\((.*?)\)/is',
    'selectRaw' => '/->selectRaw\s*\((.*?)\)/is',
    'orderByRaw' => '/->orderByRaw\s*\((.*?)\)/is',
    'havingRaw' => '/->havingRaw\s*\((.*?)\)/is',
    'DB::statement' => '/DB::statement\s*\((.*?)\)/is',
    'DB::select' => '/DB::select\s*\((.*?)\)/is',
];

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
        $content = file_get_contents($file->getPathname());
        $relPath = str_replace('\\', '/', substr($file->getPathname(), strlen(realpath(__DIR__ . '/..')) + 1));
        
        foreach ($patterns as $type => $regex) {
            if (preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[1] as $idx => $match) {
                    $expr = trim($match[0]);
                    // Check if expression contains variable concatenation (potential SQLi risk)
                    $hasConcat = preg_match('/\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\s*\./', $expr) ||
                                 preg_match('/\.\s*\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $expr) ||
                                 preg_match('/"[\s\S]*?\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*[\s\S]*?"/', $expr);
                    
                    $rawOccurrences[] = [
                        'file' => $relPath,
                        'type' => $type,
                        'expr' => mb_substr(preg_replace('/\s+/', ' ', $expr), 0, 100),
                        'has_concat' => (bool)$hasConcat
                    ];
                }
            }
        }
    }
}

echo "=== RAW SQL EXPRESSION AUDIT IN app/ ===" . PHP_EOL;
echo "Total raw SQL calls: " . count($rawOccurrences) . PHP_EOL;
$concatCalls = array_filter($rawOccurrences, fn($r) => $r['has_concat']);
echo "Calls with string concatenation / variable interpolation: " . count($concatCalls) . PHP_EOL;
echo PHP_EOL;

foreach ($rawOccurrences as $r) {
    $warn = $r['has_concat'] ? '[CONCAT/VAR]' : '[STATIC/BINDING]';
    echo "• {$r['file']} -> {$r['type']} {$warn}: {$r['expr']}" . PHP_EOL;
}
