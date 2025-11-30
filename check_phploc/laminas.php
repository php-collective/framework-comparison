<?php

/**
 * Laminas phploc analysis - analyzes multiple core packages.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$laminasDir = "$reposDir/laminas";
$reportPath = "$dataDir/phploc_laminas.json";

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$packages = [
    'laminas-mvc', 'laminas-db', 'laminas-view', 'laminas-form',
    'laminas-validator', 'laminas-router', 'laminas-servicemanager',
    'laminas-eventmanager', 'laminas-http', 'laminas-session',
];

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

// Run phploc on all directories at once
$tempReport = "/tmp/phploc_laminas.json";
$dirs = implode(' ', array_map('escapeshellarg', $srcDirs));
exec("$phplocBin --log-json=$tempReport $dirs 2>&1");

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
