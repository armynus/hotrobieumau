<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
foreach ($tables as $t) {
    foreach ($t as $k => $tableName) {
        if (str_contains($tableName, 'field') || str_contains($tableName, 'form') || str_contains($tableName, 'template')) {
            echo $tableName . PHP_EOL;
        }
    }
}
