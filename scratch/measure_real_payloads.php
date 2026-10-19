<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$urls = [
    'login' => '/',
    'login_admin' => '/login_admin',
    'admin_login_page' => '/login_admin',
];

echo "=== REAL ROUTE HTML PAYLOAD SIZES ===" . PHP_EOL;

foreach ($urls as $label => $uri) {
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $response = $kernel->handle($request);
    $content = $response->getContent();
    $size = strlen($content);
    $gzSize = strlen(gzencode($content, 9));
    echo sprintf("%-20s: %6d bytes (Gzip: %5d bytes) [Status: %d]\n", $label, $size, $gzSize, $response->getStatusCode());
    $kernel->terminate($request, $response);
}

// Let's also measure rendered Blade views directly
echo PHP_EOL . "=== RENDERED BLADE VIEW SIZES ===" . PHP_EOL;
$views = [
    'admin.auth.login_admin' => [],
    'user.auth.login_user' => [],
    'shared.shell-scripts' => [],
];

foreach ($views as $v => $data) {
    if (view()->exists($v)) {
        $html = view($v, $data)->render();
        $size = strlen($html);
        $gzSize = strlen(gzencode($html, 9));
        echo sprintf("%-30s: %6d bytes (Gzip: %5d bytes)\n", $v, $size, $gzSize);
    }
}
