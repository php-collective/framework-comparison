<?php

echo "Running Psalm on all frameworks...\n";
echo str_repeat('=', 50) . "\n\n";

$frameworks = ['cakephp', 'codeigniter', 'laminas', 'laravel', 'symfony', 'yii2'];

foreach ($frameworks as $fw) {
    passthru("php " . __DIR__ . "/$fw.php");
    echo "\n";
}

echo str_repeat('=', 50) . "\n";
echo "Psalm analysis complete.\n";
