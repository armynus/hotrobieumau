<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ffCount = Illuminate\Support\Facades\DB::table('form_fields')->count();
echo "Total form_fields: " . $ffCount . PHP_EOL;

$sampleFF = Illuminate\Support\Facades\DB::table('form_fields')->take(20)->get();
foreach ($sampleFF as $f) {
    echo json_encode($f, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
