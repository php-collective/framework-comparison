<?php

echo "Running PHPStan on all frameworks...\n";
echo str_repeat('=', 50) . "\n\n";

$scripts = [
    'cakephp.php',
    'codeigniter.php',
    'laminas.php',
    'laravel.php',
    'symfony.php',
    'yii2.php',
];

foreach ($scripts as $script) {
    passthru("php " . __DIR__ . "/$script");
    echo "\n";
}

echo str_repeat('=', 50) . "\n";
echo "PHPStan analysis complete.\n";
