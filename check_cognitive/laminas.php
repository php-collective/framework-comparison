<?php

/**
 * Laminas Cognitive analysis - analyzes multiple core packages.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$laminasDir = "$reposDir/laminas";
$reportPath = "$dataDir/cognitive_laminas.json";

// Load config for package list
$config = require "$baseDir/config.php";
$packages = array_keys($config['laminas']['packages'] ?? []);

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

echo "=== Cognitive Analysis: Laminas (multi-package) ===\n";

$ccaBin = "$baseDir/vendor/bin/phpcca";
if (!file_exists($ccaBin)) {
    echo "Error: cognitive-code-analysis not installed.\n";
    exit(1);
}

$combinedResults = [];
$totalTime = 0;

foreach ($packages as $name) {
    $srcDir = "$laminasDir/$name/src";
    if (!is_dir($srcDir)) {
        continue;
    }

    $tempReport = "/tmp/cognitive_laminas_$name.json";
    $pkgStart = microtime(true);
    exec("$ccaBin analyse " . escapeshellarg($srcDir) . " --report-type=json --report-file=$tempReport 2>/dev/null");
    $totalTime += microtime(true) - $pkgStart;

    if (file_exists($tempReport)) {
        $json = json_decode(file_get_contents($tempReport), true);
        if (is_array($json)) {
            foreach ($json as $class => $data) {
                $combinedResults[$class] = $data;
            }
        }
        unlink($tempReport);
    }
}

file_put_contents($reportPath, json_encode($combinedResults, JSON_PRETTY_PRINT));

// Save timing (analysis only)
$timingFile = "$dataDir/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];
$timing['laminas']['cognitive'] = round($totalTime, 1);
file_put_contents($timingFile, json_encode($timing, JSON_PRETTY_PRINT) . "\n");

// Calculate summary
$totalMethods = 0;
$totalScore = 0;
$maxScore = 0;

foreach ($combinedResults as $classData) {
    if (isset($classData['methods'])) {
        foreach ($classData['methods'] as $methodData) {
            $totalMethods++;
            $score = $methodData['score'] ?? 0;
            $totalScore += $score;
            if ($score > $maxScore) {
                $maxScore = $score;
            }
        }
    }
}

$avgScore = $totalMethods > 0 ? round($totalScore / $totalMethods, 2) : 0;

echo "\nResults:\n";
echo "  Methods analyzed:        $totalMethods\n";
echo "  Avg cognitive/method:    $avgScore\n";
echo "  Max cognitive score:     $maxScore\n";

echo "\nReport saved to: $reportPath\n";
