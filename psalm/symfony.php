<?php

/**
 * Symfony Psalm analysis - iterates over components to avoid crashes.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$reportsDir = $baseDir . '/reports';
$repoPath = "$reposDir/symfony";
$reportPath = "$reportsDir/psalm_symfony.json";

echo "=== Psalm: symfony/symfony (by component) ===\n";

if (!is_dir($repoPath)) {
    echo "Error: Symfony repo not found. Run phpstan first.\n";
    exit(1);
}

// Check if Psalm phar is available
$psalmPhar = "$baseDir/psalm.phar";
if (!file_exists($psalmPhar)) {
    echo "Downloading Psalm...\n";
    exec("curl -sL https://github.com/vimeo/psalm/releases/latest/download/psalm.phar -o $psalmPhar 2>&1");
    chmod($psalmPhar, 0755);
}
$psalmBin = "php $psalmPhar";

// Find all components
$componentDirs = glob("$repoPath/src/Symfony/Component/*", GLOB_ONLYDIR);
$bundleDirs = glob("$repoPath/src/Symfony/Bundle/*", GLOB_ONLYDIR);
$bridgeDirs = glob("$repoPath/src/Symfony/Bridge/*", GLOB_ONLYDIR);

$allDirs = array_merge($componentDirs, $bundleDirs, $bridgeDirs);
$totalIssues = 0;
$allIssues = [];
$processed = 0;
$failed = 0;

echo "Found " . count($allDirs) . " components/bundles/bridges to analyze...\n";

foreach ($allDirs as $dir) {
    $name = basename($dir);
    $parent = basename(dirname($dir));

    // Create temp psalm.xml for this component
    $psalmConfig = <<<XML
<?xml version="1.0"?>
<psalm errorLevel="1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="https://getpsalm.org/schema/config">
    <projectFiles>
        <directory name="." />
        <ignoreFiles><directory name="Tests" /></ignoreFiles>
    </projectFiles>
</psalm>
XML;

    $configFile = "$dir/psalm.xml";
    $hadConfig = file_exists($configFile);
    if (!$hadConfig) {
        file_put_contents($configFile, $psalmConfig);
    }

    // Run psalm
    $output = [];
    exec("cd " . escapeshellarg($dir) . " && $psalmBin --output-format=json --no-progress 2>/dev/null", $output);
    $json = json_decode(implode("\n", $output), true);

    if (is_array($json)) {
        $count = count($json);
        $totalIssues += $count;
        $allIssues = array_merge($allIssues, $json);
        $processed++;
    } else {
        $failed++;
    }

    // Cleanup temp config
    if (!$hadConfig && file_exists($configFile)) {
        unlink($configFile);
    }
}

// Save combined report
file_put_contents($reportPath, json_encode($allIssues, JSON_PRETTY_PRINT));

echo "\nCompleted: $totalIssues issues found.\n";
echo "Components processed: $processed, failed: $failed\n";
echo "Report saved to: $reportPath\n";
