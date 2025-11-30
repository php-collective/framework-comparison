<?php

/**
 * Common PHPStan analysis logic.
 *
 * Expected variables before requiring this file:
 * - $framework: GitHub repo path (e.g., 'cakephp/cakephp')
 * - $repoName: Short name for the framework
 * - $srcDir: Source directory to analyze
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$reportsDir = $baseDir . '/reports';

if (!is_dir($reposDir)) {
    mkdir($reposDir, 0777, true);
}

if (!is_dir($reportsDir)) {
    mkdir($reportsDir, 0777, true);
}

$repoPath = "$reposDir/$repoName";
$reportPath = "$reportsDir/phpstan_$repoName.json";

echo "=== PHPStan: $framework ===\n";

// Clone the repository if not exists
if (!is_dir($repoPath)) {
    echo "Cloning $framework...\n";
    $cloneCommand = "git clone --depth 1 https://github.com/$framework.git $repoPath";
    exec($cloneCommand, $cloneOutput, $cloneStatus);

    if ($cloneStatus !== 0) {
        echo "Error: Failed to clone $framework.\n";
        exit(1);
    }
}

chdir($repoPath);

// Install dependencies
echo "Installing dependencies...\n";
exec('composer install --no-interaction --ignore-platform-reqs --optimize-autoloader 2>&1', $composerOutput, $composerStatus);

if ($composerStatus !== 0) {
    echo "Error: Failed to install dependencies.\n";
    exit(1);
}

// Check if PHPStan is available
$phpstanBin = 'vendor/bin/phpstan';
if (!file_exists($phpstanBin)) {
    echo "PHPStan not found, installing...\n";
    exec('composer require --dev phpstan/phpstan --no-interaction --ignore-platform-reqs 2>&1');
}

// Run PHPStan
echo "Running PHPStan level 8...\n";
$phpstanCommand = "$phpstanBin analyze $srcDir --level=8 --no-progress --error-format=prettyJson 2>/dev/null";
exec($phpstanCommand, $phpstanOutput);

$jsonOutput = implode("\n", $phpstanOutput);
file_put_contents($reportPath, $jsonOutput);

$jsonData = json_decode($jsonOutput, true);
$fileErrors = $jsonData['totals']['file_errors'] ?? 'unknown';

echo "\nCompleted: $fileErrors errors found.\n";
echo "Report saved to: $reportPath\n";
