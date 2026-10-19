<?php

$dir = __DIR__ . '/../tests';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$methods = 0;
$testFilesCount = 0;

foreach ($iterator as $f) {
    if ($f->isFile() && str_ends_with($f->getFilename(), 'Test.php')) {
        $testFilesCount++;
        $c = file_get_contents($f->getPathname());
        preg_match_all('/function\s+(test[a-zA-Z0-9_]*)/i', $c, $m);
        $methods += count($m[1]);
    }
}
echo "Total PHP Test files: {$testFilesCount}" . PHP_EOL;
echo "Total PHP test_* methods declared: {$methods}" . PHP_EOL;

$jsFiles = glob(__DIR__ . '/../tests/js/*.test.mjs');
echo "Total JS test files: " . count($jsFiles) . PHP_EOL;
foreach ($jsFiles as $jf) {
    echo "  - " . basename($jf) . PHP_EOL;
}
