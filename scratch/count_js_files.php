<?php

$dir = __DIR__ . '/../public/js';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$allJs = [];
$counts = ['user' => 0, 'admin' => 0, 'shared' => 0, 'root' => 0, 'vendor' => 0, 'other' => 0];

foreach ($iterator as $f) {
    if ($f->isFile() && str_ends_with($f->getFilename(), '.js')) {
        $full = str_replace('\\', '/', $f->getPathname());
        $allJs[] = $full;
        if (str_contains($full, '/public/js/user/')) {
            $counts['user']++;
        } elseif (str_contains($full, '/public/js/admin/')) {
            $counts['admin']++;
        } elseif (str_contains($full, '/public/js/shared/')) {
            $counts['shared']++;
        } elseif (str_contains($full, '/public/js/demo/') || str_contains($full, '/public/js/ajax/')) {
            $counts['vendor']++;
        } else {
            $counts['root']++;
        }
    }
}

echo "Total .js files under public/js: " . count($allJs) . PHP_EOL;
echo "JS files in user/: " . $counts['user'] . PHP_EOL;
echo "JS files in admin/: " . $counts['admin'] . PHP_EOL;
echo "JS files in shared/: " . $counts['shared'] . PHP_EOL;
echo "Total in user + admin + shared: " . ($counts['user'] + $counts['admin'] + $counts['shared']) . PHP_EOL;
echo "Vendor / Demo JS files: " . $counts['vendor'] . PHP_EOL;
echo "Root / other JS files: " . $counts['root'] . PHP_EOL;
