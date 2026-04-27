<?php

require_once __DIR__.'/vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\ArrayLoader;

try {
    $loader = new ArrayLoader([
        'index' => 'Hello {{ name }}!',
    ]);
    $twig = new Environment($loader);

    echo $twig->render('index', ['name' => 'Fabien']);
    echo "\nSuccess!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
