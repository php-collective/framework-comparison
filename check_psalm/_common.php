<?php

/**
 * Common Psalm analysis logic.
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
$reportPath = "$dataDir/psalm_$repoName.json";

echo "=== Psalm: $framework ===\n";

// Clone the repository if not exists
if (!is_dir($repoPath)) {
    echo "Cloning $framework...\n";
    $branchArg = $branch ? " --branch $branch" : '';
    $cloneCommand = "git clone --depth 1$branchArg https://github.com/$framework.git $repoPath";
    exec($cloneCommand, $cloneOutput, $cloneStatus);

    if ($cloneStatus !== 0) {
        echo "Error: Failed to clone $framework.\n";
        exit(1);
    }
} elseif ($branch) {
    // Repo exists - ensure correct branch is checked out
    $currentBranch = trim(shell_exec("git -C $repoPath rev-parse --abbrev-ref HEAD 2>/dev/null") ?: '');
    if ($currentBranch !== $branch) {
        echo "Switching to branch $branch...\n";
        exec("git -C $repoPath fetch --depth 1 origin $branch 2>&1");
        exec("git -C $repoPath checkout $branch 2>&1");
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

// Check if Psalm phar is available (prefer phar for consistency)
$psalmPhar = "$baseDir/psalm.phar";
if (!file_exists($psalmPhar)) {
    echo "Downloading Psalm...\n";
    exec("curl -sL https://github.com/vimeo/psalm/releases/latest/download/psalm.phar -o $psalmPhar 2>&1");
    chmod($psalmPhar, 0755);
}
$psalmBin = "php $psalmPhar";

// Remove vendor psalm to avoid conflicts with phar
if (is_dir('vendor/vimeo/psalm')) {
    echo "Removing vendor Psalm to avoid conflicts...\n";
    exec('composer remove --dev vimeo/psalm --no-interaction --ignore-platform-reqs 2>&1');
    exec('composer dump-autoload 2>&1');
}

// Initialize Psalm config if not exists
if (!file_exists('psalm.xml') && !file_exists('psalm.xml.dist')) {
    echo "Initializing Psalm config...\n";
    exec("$psalmBin --init $srcDir 2>&1");
}

// Run Psalm (timed)
echo "Running Psalm...\n";
$psalmCommand = "$psalmBin --output-format=json --no-progress 2>/dev/null";

$startTime = microtime(true);
exec($psalmCommand, $psalmOutput, $psalmStatus);
$elapsed = round(microtime(true) - $startTime, 1);

$jsonOutput = implode("\n", $psalmOutput);
file_put_contents($reportPath, $jsonOutput);

// Save timing (analysis only, not setup)
$timingFile = "$dataDir/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];
$timing[$repoName]['psalm'] = $elapsed;
file_put_contents($timingFile, json_encode($timing, JSON_PRETTY_PRINT) . "\n");

$jsonData = json_decode($jsonOutput, true);
$errorCount = is_array($jsonData) ? count($jsonData) : 'unknown';

echo "\nCompleted: $errorCount issues found.\n";
echo "Report saved to: $reportPath\n";
