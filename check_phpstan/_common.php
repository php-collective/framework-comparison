<?php

/**
 * Common PHPStan analysis logic.
 *
 * Expected variables before requiring this file:
 * - $framework: GitHub repo path (e.g., 'cakephp/cakephp')
 * - $repoName: Short name for the framework
 * - $srcDir: Source directory to analyze
 * - $branch: (optional) Branch to checkout
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';

// Load config for branch info if not explicitly set
if (!isset($branch)) {
    $config = require "$baseDir/config.php";
    $branch = $config[$repoName]['branch'] ?? null;
}

if (!is_dir($reposDir)) {
    mkdir($reposDir, 0777, true);
}

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$repoPath = "$reposDir/$repoName";
$reportPath = "$dataDir/phpstan_$repoName.json";

echo "=== PHPStan: $framework ===\n";

// Clone the repository if not exists, or re-clone if branch changed
$needsClone = false;
if (!is_dir($repoPath)) {
    $needsClone = true;
} elseif ($branch) {
    // Check if current branch matches desired branch
    $currentBranch = trim(shell_exec("git -C $repoPath rev-parse --abbrev-ref HEAD 2>/dev/null") ?: '');
    // For tags, rev-parse returns "HEAD", so also check describe --tags
    if ($currentBranch === 'HEAD') {
        $currentBranch = trim(shell_exec("git -C $repoPath describe --tags --exact-match HEAD 2>/dev/null") ?: 'HEAD');
    }
    if ($currentBranch !== $branch) {
        echo "Branch mismatch: have '$currentBranch', need '$branch'. Re-cloning...\n";
        exec("rm -rf " . escapeshellarg($repoPath));
        $needsClone = true;
    }
}

if ($needsClone) {
    echo "Cloning $framework" . ($branch ? " (branch: $branch)" : "") . "...\n";
    $branchArg = $branch ? " --branch $branch" : '';
    $cloneCommand = "git clone --depth 1$branchArg https://github.com/$framework.git $repoPath";
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

// Run PHPStan (timed)
echo "Running PHPStan level 8...\n";
$phpstanCommand = "$phpstanBin analyze $srcDir --level=8 --no-progress --error-format=prettyJson 2>/dev/null";

$startTime = microtime(true);
exec($phpstanCommand, $phpstanOutput);
$elapsed = round(microtime(true) - $startTime, 1);

$jsonOutput = implode("\n", $phpstanOutput);
file_put_contents($reportPath, $jsonOutput);

// Save timing (analysis only, not setup)
$timingFile = "$dataDir/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];
$timing[$repoName]['phpstan'] = $elapsed;
file_put_contents($timingFile, json_encode($timing, JSON_PRETTY_PRINT) . "\n");

$jsonData = json_decode($jsonOutput, true);
$fileErrors = $jsonData['totals']['file_errors'] ?? 'unknown';

echo "\nCompleted: $fileErrors errors found.\n";
echo "Report saved to: $reportPath\n";
