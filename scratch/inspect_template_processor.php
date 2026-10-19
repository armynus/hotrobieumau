<?php
require 'vendor/autoload.php';

$reflector = new ReflectionClass(\PhpOffice\PhpWord\TemplateProcessor::class);
echo "TemplateProcessor methods:\n";
foreach ($reflector->getMethods() as $m) {
    if (str_contains($m->getName(), 'Variable') || str_contains($m->getName(), 'Macro') || str_contains($m->getName(), 'Delimiter')) {
        echo $m->getName() . PHP_EOL;
    }
}

// Let's check the source or constants of TemplateProcessor
$file = $reflector->getFileName();
echo "File: " . $file . PHP_EOL;
