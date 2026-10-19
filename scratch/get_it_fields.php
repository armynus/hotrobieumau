<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$fields = App\Models\ItSupportFormField::all();
echo "Total ItSupportFormField: " . $fields->count() . PHP_EOL;
foreach ($fields as $f) {
    echo $f->id . " | " . $f->field_code . " | " . $f->field_name . " | " . $f->content_group . " | " . $f->data_type . PHP_EOL;
}
