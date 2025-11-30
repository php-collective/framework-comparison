<?php

/**
 * Symfony Psalm analysis - uses root autoloader for each component.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$repoPath = "$reposDir/symfony";
$reportPath = "$dataDir/psalm_symfony.json";

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

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
$psalmBin = "php -d memory_limit=2G $psalmPhar";

// Find all components
$componentDirs = glob("$repoPath/src/Symfony/Component/*", GLOB_ONLYDIR);
$bundleDirs = glob("$repoPath/src/Symfony/Bundle/*", GLOB_ONLYDIR);
$bridgeDirs = glob("$repoPath/src/Symfony/Bridge/*", GLOB_ONLYDIR);

$allDirs = array_merge($componentDirs, $bundleDirs, $bridgeDirs);
$totalIssues = 0;
$allIssues = [];
$processed = 0;
$failed = 0;
$totalTime = 0;

echo "Found " . count($allDirs) . " components/bundles/bridges to analyze...\n";

// Temp config file in the repo root
$tempConfigFile = "$repoPath/_psalm_component.xml";

foreach ($allDirs as $dir) {
    $name = basename($dir);
    $parent = basename(dirname($dir));
    $relativePath = "src/Symfony/$parent/$name";

    // Create psalm.xml in repo root that targets this component but uses root autoloader
    // Only add ignoreFiles if Tests directory exists
    $testsDir = "$repoPath/$relativePath/Tests";
    $ignoreFiles = is_dir($testsDir)
        ? "<ignoreFiles><directory name=\"$relativePath/Tests\" /></ignoreFiles>"
        : '';

    $psalmConfig = <<<XML
<?xml version="1.0"?>
<psalm
    errorLevel="1"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    autoloader="vendor/autoload.php"
>
    <projectFiles>
        <directory name="$relativePath" />
        $ignoreFiles
    </projectFiles>
</psalm>
XML;

    file_put_contents($tempConfigFile, $psalmConfig);

    // Run psalm from repo root with the temp config (timed)
    $output = [];
    $compStart = microtime(true);
    exec("cd " . escapeshellarg($repoPath) . " && $psalmBin --config=_psalm_component.xml --output-format=json --no-progress 2>/dev/null", $output);
    $totalTime += microtime(true) - $compStart;
    $json = json_decode(implode("\n", $output), true);

    if (is_array($json)) {
        $count = count($json);
        $totalIssues += $count;
        $allIssues = array_merge($allIssues, $json);
        $processed++;
        if ($count > 0) {
            echo "  $parent/$name: $count issues\n";
        }
    } else {
        $failed++;
        echo "  $parent/$name: failed\n";
    }
}

// Cleanup temp config
if (file_exists($tempConfigFile)) {
    unlink($tempConfigFile);
}

// Save combined report
file_put_contents($reportPath, json_encode($allIssues, JSON_PRETTY_PRINT));

// Save timing (analysis only)
$timingFile = "$dataDir/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];
$timing['symfony']['psalm'] = round($totalTime, 1);
file_put_contents($timingFile, json_encode($timing, JSON_PRETTY_PRINT) . "\n");

echo "\nCompleted: $totalIssues issues found.\n";
echo "Components processed: $processed, failed: $failed\n";
echo "Report saved to: $reportPath\n";
