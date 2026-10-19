<?php
require 'vendor/autoload.php';

$file = $argv[1] ?? 'd:/hotrobieumau/scratch/test_macro.docx';
$tp = new \PhpOffice\PhpWord\TemplateProcessor($file);
$vars = $tp->getVariables();
echo json_encode($vars, JSON_UNESCAPED_UNICODE);
