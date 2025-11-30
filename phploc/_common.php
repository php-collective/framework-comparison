<?php

/**
 * Common phploc analysis logic.
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
$reportPath = "$reportsDir/phploc_$repoName.json";

echo "=== phploc: $framework ===\n";

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

// Check if phploc is available globally or download it
$phplocBin = null;
exec('which phploc 2>/dev/null', $whichOutput, $whichStatus);
if ($whichStatus === 0 && !empty($whichOutput)) {
    $phplocBin = trim($whichOutput[0]);
}

// Try to find phploc.phar in base directory
$phplocPhar = "$baseDir/phploc.phar";
if (!$phplocBin && file_exists($phplocPhar)) {
    $phplocBin = "php $phplocPhar";
}

// Download phploc if not available
if (!$phplocBin) {
    echo "Downloading phploc...\n";
    $downloadUrl = 'https://phar.phpunit.de/phploc.phar';
    exec("curl -sL $downloadUrl -o $phplocPhar 2>&1", $downloadOutput, $downloadStatus);
    if ($downloadStatus === 0 && file_exists($phplocPhar)) {
        chmod($phplocPhar, 0755);
        $phplocBin = "php $phplocPhar";
    } else {
        echo "Error: Failed to download phploc.\n";
        exit(1);
    }
}

$analyzePath = "$repoPath/$srcDir";

// Run phploc
echo "Running phploc on $srcDir...\n";
$phplocCommand = "$phplocBin --log-json=$reportPath $analyzePath 2>&1";
exec($phplocCommand, $phplocOutput, $phplocStatus);

// Read and display summary
if (file_exists($reportPath)) {
    $jsonData = json_decode(file_get_contents($reportPath), true);

    echo "\nResults:\n";
    echo "  Lines of Code (LOC):     " . ($jsonData['loc'] ?? '-') . "\n";
    echo "  Logical LOC (LLOC):      " . ($jsonData['lloc'] ?? '-') . "\n";
    echo "  Classes:                 " . ($jsonData['classes'] ?? '-') . "\n";
    echo "  Methods:                 " . ($jsonData['methods'] ?? '-') . "\n";
    echo "  Functions:               " . ($jsonData['functions'] ?? '-') . "\n";
    echo "  Avg Class Length:        " . ($jsonData['classLlocAvg'] ?? '-') . "\n";
    echo "  Avg Method Length:       " . ($jsonData['methodLlocAvg'] ?? '-') . "\n";
    echo "  Avg Complexity/Method:   " . ($jsonData['ccnByLloc'] ?? '-') . "\n";

    echo "\nReport saved to: $reportPath\n";
} else {
    echo "Error: Failed to generate report.\n";
    exit(1);
}
