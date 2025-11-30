<?php

/**
 * Laminas Psalm analysis - analyzes multiple core packages.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$laminasDir = "$reposDir/laminas";
$reportPath = "$dataDir/psalm_laminas.json";

// Load config for package list
$config = require "$baseDir/config.php";
$packages = array_keys($config['laminas']['packages'] ?? []);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

echo "=== Psalm: Laminas (multi-package) ===\n";

$psalmPhar = "$baseDir/psalm.phar";
if (!file_exists($psalmPhar)) {
    echo "Downloading Psalm...\n";
    exec("curl -sL https://github.com/vimeo/psalm/releases/latest/download/psalm.phar -o $psalmPhar 2>&1");
    chmod($psalmPhar, 0755);
}
$psalmBin = "php $psalmPhar";

$allIssues = [];
$totalTime = 0;

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
        $psalmConfig = '<?xml version="1.0"?><psalm errorLevel="1" xmlns="https://getpsalm.org/schema/config"><projectFiles><directory name="src" /></projectFiles></psalm>';
        file_put_contents($configFile, $psalmConfig);
    }

    // Run psalm (timed)
    $output = [];
    $pkgStart = microtime(true);
    exec("cd " . escapeshellarg($pkgDir) . " && $psalmBin --output-format=json --no-progress 2>/dev/null", $output);
    $totalTime += microtime(true) - $pkgStart;
    $json = json_decode(implode("\n", $output), true);

    if (is_array($json)) {
        $count = count($json);
        echo "  $name: $count issues\n";
        $allIssues = array_merge($allIssues, $json);
    }
}

file_put_contents($reportPath, json_encode($allIssues, JSON_PRETTY_PRINT));

// Save timing (analysis only)
$timingFile = "$dataDir/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];
$timing['laminas']['psalm'] = round($totalTime, 1);
file_put_contents($timingFile, json_encode($timing, JSON_PRETTY_PRINT) . "\n");

echo "\nCompleted: " . count($allIssues) . " total issues found.\n";
echo "Report saved to: $reportPath\n";
