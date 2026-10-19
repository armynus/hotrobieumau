<?php

$dir = __DIR__ . '/../resources/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$inlineFiles = [];
$totalBlocks = 0;
$totalBytes = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        if (preg_match_all('/<script\b(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/is', $content, $matches)) {
            $blocks = [];
            foreach ($matches[1] as $block) {
                $trimmed = trim($block);
                if ($trimmed !== '') {
                    $blocks[] = [
                        'bytes' => strlen($trimmed),
                        'lines' => substr_count($trimmed, "\n") + 1,
                        'preview' => mb_substr(preg_replace('/\s+/', ' ', $trimmed), 0, 70),
                    ];
                    $totalBytes += strlen($trimmed);
                }
            }
            if (!empty($blocks)) {
                $totalBlocks += count($blocks);
                $relPath = str_replace('\\', '/', substr($file->getPathname(), strlen(realpath(__DIR__ . '/..')) + 1));
                $inlineFiles[$relPath] = $blocks;
            }
        }
    }
}

echo "=== INLINE SCRIPT AUDIT ===" . PHP_EOL;
echo "Files with inline scripts: " . count($inlineFiles) . PHP_EOL;
echo "Total inline script blocks: " . $totalBlocks . PHP_EOL;
echo "Total inline script bytes: " . number_format($totalBytes) . " bytes (" . round($totalBytes / 1024, 2) . " KB)" . PHP_EOL;
echo PHP_EOL;

foreach ($inlineFiles as $path => $blocks) {
    $sizes = implode(', ', array_map(fn($b) => $b['bytes'] . 'B (' . $b['lines'] . 'L)', $blocks));
    echo "• {$path} [" . count($blocks) . " blocks]: {$sizes}" . PHP_EOL;
    foreach ($blocks as $idx => $b) {
        echo "    #{$idx}: {$b['preview']}..." . PHP_EOL;
    }
}
