<?php

/**
 * Symfony Cognitive analysis - iterates over components to avoid crashes.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$reportsDir = $baseDir . '/reports';
$repoPath = "$reposDir/symfony";
$reportPath = "$reportsDir/cognitive_symfony.json";

echo "=== Cognitive Analysis: symfony/symfony (by component) ===\n";

if (!is_dir($repoPath)) {
    echo "Error: Symfony repo not found. Run phpstan first.\n";
    exit(1);
}

// Use global cognitive tool
$ccaBin = "$baseDir/vendor/bin/phpcca";
if (!file_exists($ccaBin)) {
    echo "Error: cognitive-code-analysis not installed.\n";
    exit(1);
}

// Find all components (excluding Tests)
$componentDirs = glob("$repoPath/src/Symfony/Component/*", GLOB_ONLYDIR);
$bundleDirs = glob("$repoPath/src/Symfony/Bundle/*", GLOB_ONLYDIR);
$bridgeDirs = glob("$repoPath/src/Symfony/Bridge/*", GLOB_ONLYDIR);

$allDirs = array_merge($componentDirs, $bundleDirs, $bridgeDirs);
$combinedResults = [];
$processed = 0;
$failed = 0;

echo "Found " . count($allDirs) . " components/bundles/bridges to analyze...\n";

foreach ($allDirs as $dir) {
    $name = basename($dir);

    // Skip Tests directories inside component
    $tempReport = "/tmp/cognitive_symfony_$name.json";

    // Run cognitive analysis
    $output = [];
    exec("$ccaBin analyse " . escapeshellarg($dir) . " --report-type=json --report-file=$tempReport 2>/dev/null", $output, $status);

    if (file_exists($tempReport)) {
        $json = json_decode(file_get_contents($tempReport), true);
        if (is_array($json)) {
            // Merge results
            foreach ($json as $class => $data) {
                $combinedResults[$class] = $data;
            }
            $processed++;
        } else {
            $failed++;
        }
        unlink($tempReport);
    } else {
        $failed++;
    }
}

// Save combined report
file_put_contents($reportPath, json_encode($combinedResults, JSON_PRETTY_PRINT));

// Calculate summary
$totalMethods = 0;
$totalScore = 0;
$maxScore = 0;
$maxMethod = '';

foreach ($combinedResults as $className => $classData) {
    if (isset($classData['methods']) && is_array($classData['methods'])) {
        foreach ($classData['methods'] as $methodName => $methodData) {
            $totalMethods++;
            $score = $methodData['score'] ?? 0;
            $totalScore += $score;
            if ($score > $maxScore) {
                $maxScore = $score;
                $maxMethod = "$className::$methodName";
            }
        }
    }
}

$avgScore = $totalMethods > 0 ? round($totalScore / $totalMethods, 2) : 0;

echo "\nResults:\n";
echo "  Methods analyzed:        $totalMethods\n";
echo "  Total cognitive score:   $totalScore\n";
echo "  Avg cognitive/method:    $avgScore\n";
echo "  Max cognitive score:     $maxScore\n";
echo "  Most complex method:     $maxMethod\n";
echo "Components processed: $processed, failed: $failed\n";

echo "\nReport saved to: $reportPath\n";
