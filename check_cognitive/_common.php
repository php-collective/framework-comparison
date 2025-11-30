<?php

/**
 * Common Cognitive Code Analysis logic.
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
$reportPath = "$dataDir/cognitive_$repoName.json";

echo "=== Cognitive Analysis: $framework ===\n";

// Clone the repository if not exists, or re-clone if branch changed
$needsClone = false;
if (!is_dir($repoPath)) {
    $needsClone = true;
} elseif ($branch) {
    // Check if current branch matches desired branch
    $currentBranch = trim(shell_exec("git -C $repoPath rev-parse --abbrev-ref HEAD 2>/dev/null") ?: '');
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

// Use global cognitive tool from project root
$ccaBin = "$baseDir/vendor/bin/phpcca";
if (!file_exists($ccaBin)) {
    echo "Error: cognitive-code-analysis not installed. Run: composer require phauthentic/cognitive-code-analysis\n";
    exit(1);
}

$analyzePath = "$repoPath/$srcDir";

// Run cognitive analysis (timed)
echo "Running cognitive analysis on $srcDir...\n";
$ccaCommand = "$ccaBin analyse $analyzePath --report-type=json --report-file=$reportPath 2>&1";

$startTime = microtime(true);
exec($ccaCommand, $ccaOutput, $ccaStatus);
$elapsed = round(microtime(true) - $startTime, 1);

// Save timing (analysis only, not setup)
$timingFile = "$dataDir/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];
$timing[$repoName]['cognitive'] = $elapsed;
file_put_contents($timingFile, json_encode($timing, JSON_PRETTY_PRINT) . "\n");

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
