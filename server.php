<?php

// Router dành cho php artisan serve: không để máy chủ PHP trả file kho trực tiếp.
$publicPath = __DIR__.'/public';
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$normalized = str_replace('\\', '/', $uri);
$resolved = realpath($publicPath.$normalized);
$protectedPath = false;
foreach (['documents', 'document-deletion-staging'] as $directory) {
    $root = realpath(__DIR__.'/storage/app/public/'.$directory);
    if ($root && $resolved && str_starts_with(strtolower(str_replace('\\', '/', $resolved)).'/', rtrim(strtolower(str_replace('\\', '/', $root)), '/').'/')) {
        $protectedPath = true;
    }
}
if ($protectedPath || preg_match('#^/storage/(documents|document-deletion-staging)(/|$)#i', $normalized)) {
    http_response_code(403);
    header('Cache-Control: no-store');
    echo 'Forbidden';
    return;
}
if ($uri !== '/' && $resolved && is_file($resolved)) {
    return false;
}
require $publicPath.'/index.php';
