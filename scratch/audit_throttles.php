<?php

$content = file_get_contents(__DIR__ . '/../routes/web.php');
preg_match_all('/Route::(post|put|delete|get)\s*\(\s*[\'"]([^\'"]+)[\'"]([^\;]+);/is', $content, $matches, PREG_SET_ORDER);

$throttled = [];
$mutations = [];

foreach ($matches as $m) {
    $method = strtoupper($m[1]);
    $uri = $m[2];
    $rest = $m[3];

    $hasThrottle = preg_match('/throttle:([0-9]+,[0-9]+)/i', $rest, $tm);
    if ($hasThrottle) {
        $throttled[] = "$method $uri -> {$tm[1]}";
    }
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
        $mutations[] = [
            'method' => $method,
            'uri' => $uri,
            'throttled' => $hasThrottle ? $tm[1] : null
        ];
    }
}

echo "=== ROUTE THROTTLE AUDIT ===" . PHP_EOL;
echo "Throttled routes count: " . count($throttled) . PHP_EOL;
foreach ($throttled as $t) {
    echo "  - {$t}" . PHP_EOL;
}

echo PHP_EOL . "=== MUTATION ROUTES WITHOUT THROTTLE ===" . PHP_EOL;
$unthrottledMutations = array_filter($mutations, fn($m) => $m['throttled'] === null);
echo "Unthrottled POST/PUT/DELETE routes: " . count($unthrottledMutations) . " / " . count($mutations) . PHP_EOL;
foreach ($unthrottledMutations as $um) {
    echo "  - {$um['method']} {$um['uri']}" . PHP_EOL;
}
