<?php

/**
 * Common Cognitive Code Analysis logic.
 *
 * Expected variables before requiring this file:
 * - $framework: GitHub repo path (e.g., 'cakephp/cakephp')
 * - $repoName: Short name for the framework
 * - $srcDir: Source directory to analyze
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';

if (!is_dir($reposDir)) {
    mkdir($reposDir, 0777, true);
}

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$repoPath = "$reposDir/$repoName";
$reportPath = "$dataDir/cognitive_$repoName.json";

echo "=== Cognitive Analysis: $framework ===\n";

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

// Use global cognitive tool from project root
$ccaBin = "$baseDir/vendor/bin/phpcca";
if (!file_exists($ccaBin)) {
    echo "Error: cognitive-code-analysis not installed. Run: composer require phauthentic/cognitive-code-analysis\n";
    exit(1);
}

$analyzePath = "$repoPath/$srcDir";

// Run cognitive analysis
echo "Running cognitive analysis on $srcDir...\n";
$ccaCommand = "$ccaBin analyse $analyzePath --report-type=json --report-file=$reportPath 2>&1";
exec($ccaCommand, $ccaOutput, $ccaStatus);

// Read and display summary
if (file_exists($reportPath)) {
    $jsonData = json_decode(file_get_contents($reportPath), true);

    $totalMethods = 0;
    $totalCognitive = 0;
    $maxCognitive = 0;
    $maxMethod = '';

    // JSON structure: { "ClassName": { "methods": { "methodName": { "score": N, ... } } } }
    if (is_array($jsonData)) {
        foreach ($jsonData as $className => $classData) {
            if (isset($classData['methods']) && is_array($classData['methods'])) {
                foreach ($classData['methods'] as $methodName => $methodData) {
                    $totalMethods++;
                    $score = $methodData['score'] ?? 0;
                    $totalCognitive += $score;
                    if ($score > $maxCognitive) {
                        $maxCognitive = $score;
                        $maxMethod = "$className::$methodName";
                    }
                }
            }
        }
    }

    $avgCognitive = $totalMethods > 0 ? round($totalCognitive / $totalMethods, 2) : 0;

    echo "\nResults:\n";
    echo "  Methods analyzed:        $totalMethods\n";
    echo "  Total cognitive score:   $totalCognitive\n";
    echo "  Avg cognitive/method:    $avgCognitive\n";
    echo "  Max cognitive score:     $maxCognitive\n";
    echo "  Most complex method:     $maxMethod\n";

    echo "\nReport saved to: $reportPath\n";
} else {
    echo "Error: Failed to generate report.\n";
    exit(1);
}
