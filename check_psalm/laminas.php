<?php

/**
 * Laminas Psalm analysis - analyzes multiple core packages.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$laminasDir = "$reposDir/laminas";
$reportPath = "$dataDir/psalm_laminas.json";

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$packages = [
    'laminas-mvc', 'laminas-db', 'laminas-view', 'laminas-form',
    'laminas-validator', 'laminas-router', 'laminas-servicemanager',
    'laminas-eventmanager', 'laminas-http', 'laminas-session',
];

echo "=== Psalm: Laminas (multi-package) ===\n";

$psalmPhar = "$baseDir/psalm.phar";
if (!file_exists($psalmPhar)) {
    echo "Downloading Psalm...\n";
    exec("curl -sL https://github.com/vimeo/psalm/releases/latest/download/psalm.phar -o $psalmPhar 2>&1");
    chmod($psalmPhar, 0755);
}
$psalmBin = "php $psalmPhar";

$allIssues = [];

foreach ($packages as $name) {
    $pkgDir = "$laminasDir/$name";
    if (!is_dir($pkgDir)) {
        continue;
    }

    // Remove vendor psalm to avoid conflicts
    if (is_dir("$pkgDir/vendor/vimeo/psalm")) {
        exec("cd $pkgDir && composer remove --dev vimeo/psalm --no-interaction --ignore-platform-reqs 2>&1");
    }

    // Create psalm config if needed
    $configFile = "$pkgDir/psalm.xml";
    if (!file_exists($configFile)) {
        $config = '<?xml version="1.0"?><psalm errorLevel="1" xmlns="https://getpsalm.org/schema/config"><projectFiles><directory name="src" /></projectFiles></psalm>';
        file_put_contents($configFile, $config);
    }

    // Run psalm
    $output = [];
    exec("cd " . escapeshellarg($pkgDir) . " && $psalmBin --output-format=json --no-progress 2>/dev/null", $output);
    $json = json_decode(implode("\n", $output), true);

    if (is_array($json)) {
        $count = count($json);
        echo "  $name: $count issues\n";
        $allIssues = array_merge($allIssues, $json);
    }
}

file_put_contents($reportPath, json_encode($allIssues, JSON_PRETTY_PRINT));

echo "\nCompleted: " . count($allIssues) . " total issues found.\n";
echo "Report saved to: $reportPath\n";
