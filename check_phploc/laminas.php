<?php

/**
 * Laminas phploc analysis - analyzes multiple core packages.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$laminasDir = "$reposDir/laminas";
$reportPath = "$dataDir/phploc_laminas.json";

// Load config for package list
$config = require "$baseDir/config.php";
$packages = array_keys($config['laminas']['packages'] ?? []);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

echo "=== phploc: Laminas (multi-package) ===\n";

// Check for phploc
$phplocPhar = "$baseDir/phploc.phar";
if (!file_exists($phplocPhar)) {
    echo "Downloading phploc...\n";
    exec("curl -sL https://phar.phpunit.de/phploc.phar -o $phplocPhar 2>&1");
    chmod($phplocPhar, 0755);
}
$phplocBin = "php $phplocPhar";

// Collect all src directories
$srcDirs = [];
foreach ($packages as $name) {
    $srcDir = "$laminasDir/$name/src";
    if (is_dir($srcDir)) {
        $srcDirs[] = $srcDir;
    }
}

// Run phploc on all directories at once (timed)
$tempReport = "/tmp/phploc_laminas.json";
$dirs = implode(' ', array_map('escapeshellarg', $srcDirs));

$startTime = microtime(true);
exec("$phplocBin --log-json=$tempReport $dirs 2>&1");
$elapsed = round(microtime(true) - $startTime, 1);

// Save timing
$timingFile = "$dataDir/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];
$timing['laminas']['phploc'] = $elapsed;
file_put_contents($timingFile, json_encode($timing, JSON_PRETTY_PRINT) . "\n");

if (file_exists($tempReport)) {
    copy($tempReport, $reportPath);
    $json = json_decode(file_get_contents($tempReport), true);

    echo "\nResults:\n";
    echo "  Lines of Code (LOC):     " . ($json['loc'] ?? '-') . "\n";
    echo "  Logical LOC (LLOC):      " . ($json['lloc'] ?? '-') . "\n";
    echo "  Classes:                 " . ($json['classes'] ?? '-') . "\n";
    echo "  Methods:                 " . ($json['methods'] ?? '-') . "\n";

    unlink($tempReport);
}

echo "\nReport saved to: $reportPath\n";
