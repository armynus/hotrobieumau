<?php
require 'vendor/autoload.php';

try {
    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('test.doc');
    echo "Success!";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
