<?php

/**
 * Laminas Cognitive analysis - analyzes multiple core packages.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$laminasDir = "$reposDir/laminas";
$reportPath = "$dataDir/cognitive_laminas.json";

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$packages = [
    'laminas-mvc', 'laminas-db', 'laminas-view', 'laminas-form',
    'laminas-validator', 'laminas-router', 'laminas-servicemanager',
    'laminas-eventmanager', 'laminas-http', 'laminas-session',
];

echo "=== Cognitive Analysis: Laminas (multi-package) ===\n";

$ccaBin = "$baseDir/vendor/bin/phpcca";
if (!file_exists($ccaBin)) {
    echo "Error: cognitive-code-analysis not installed.\n";
    exit(1);
}

$combinedResults = [];

foreach ($packages as $name) {
    $srcDir = "$laminasDir/$name/src";
    if (!is_dir($srcDir)) {
        continue;
    }

    $tempReport = "/tmp/cognitive_laminas_$name.json";
    exec("$ccaBin analyse " . escapeshellarg($srcDir) . " --report-type=json --report-file=$tempReport 2>/dev/null");

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
