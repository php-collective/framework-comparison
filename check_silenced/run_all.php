<?php

echo "Running Silenced Issues scan on all frameworks...\n";
echo str_repeat('=', 50) . "\n\n";

$baseDir = dirname(__DIR__);
$timingFile = "$baseDir/reports/data/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];

$frameworks = ['cakephp', 'codeigniter', 'laminas', 'laravel', 'symfony', 'yii2'];

foreach ($frameworks as $fw) {
    $start = microtime(true);
    passthru("php " . __DIR__ . "/$fw.php");
    $elapsed = round(microtime(true) - $start, 1);
    $timing[$fw]['silenced'] = $elapsed;
    echo "\n";
}

file_put_contents($timingFile, json_encode($timing, JSON_PRETTY_PRINT) . "\n");

echo str_repeat('=', 50) . "\n";
echo "Silenced issues scan complete.\n";
