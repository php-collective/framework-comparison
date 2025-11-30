<?php

/**
 * Common Psalm analysis logic.
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
$reportPath = "$reportsDir/psalm_$repoName.json";

echo "=== Psalm: $framework ===\n";

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

// Run Psalm
echo "Running Psalm...\n";
$psalmCommand = "$psalmBin --output-format=json --no-progress 2>/dev/null";
exec($psalmCommand, $psalmOutput, $psalmStatus);

$jsonOutput = implode("\n", $psalmOutput);
file_put_contents($reportPath, $jsonOutput);

$jsonData = json_decode($jsonOutput, true);
$errorCount = is_array($jsonData) ? count($jsonData) : 'unknown';

echo "\nCompleted: $errorCount issues found.\n";
echo "Report saved to: $reportPath\n";
