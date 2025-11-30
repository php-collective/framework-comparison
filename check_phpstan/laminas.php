<?php

/**
 * Laminas PHPStan analysis - analyzes multiple core packages.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$laminasDir = "$reposDir/laminas";
$reportPath = "$dataDir/phpstan_laminas.json";

// Load config for package/branch info
$config = require "$baseDir/config.php";
$laminasConfig = $config['laminas']['packages'] ?? [];

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

// Core Laminas packages for a fair comparison
$packages = array_keys($laminasConfig);

echo "=== PHPStan: Laminas (multi-package) ===\n";

if (!is_dir($laminasDir)) {
    mkdir($laminasDir, 0777, true);
}

$totalErrors = 0;
$allFiles = [];

foreach ($packages as $name) {
    $pkgConfig = $laminasConfig[$name];
    $package = $pkgConfig['repo'];
    $branch = $pkgConfig['branch'] ?? null;
    $pkgDir = "$laminasDir/$name";

    // Clone if not exists
    if (!is_dir($pkgDir)) {
        echo "Cloning $package...\n";
        $branchArg = $branch ? " --branch $branch" : '';
        exec("git clone --depth 1$branchArg https://github.com/$package.git $pkgDir 2>&1");
    } elseif ($branch) {
        // Repo exists - ensure correct branch is checked out
        $currentBranch = trim(shell_exec("git -C $pkgDir rev-parse --abbrev-ref HEAD 2>/dev/null") ?: '');
        if ($currentBranch !== $branch) {
            exec("git -C $pkgDir fetch --depth 1 origin $branch 2>&1");
            exec("git -C $pkgDir checkout $branch 2>&1");
        }
    }

    if (!is_dir($pkgDir)) {
        echo "  Failed to clone $package\n";
        continue;
    }

    // Install dependencies
    chdir($pkgDir);
    exec('composer install --no-interaction --ignore-platform-reqs --optimize-autoloader 2>&1');

    // Install PHPStan if needed
    if (!file_exists('vendor/bin/phpstan')) {
        exec('composer require --dev phpstan/phpstan --no-interaction --ignore-platform-reqs 2>&1');
    }

    // Run PHPStan
    $output = [];
    exec('vendor/bin/phpstan analyze src --level=8 --no-progress --error-format=prettyJson 2>/dev/null', $output);
    $json = json_decode(implode("\n", $output), true);

    if (isset($json['totals']['file_errors'])) {
        $errors = $json['totals']['file_errors'];
        $totalErrors += $errors;
        echo "  $name: $errors errors\n";

        // Merge file errors
        if (isset($json['files'])) {
            foreach ($json['files'] as $file => $data) {
                $allFiles[$file] = $data;
            }
        }
    }
}

// Save combined report
$combined = [
    'totals' => [
        'errors' => 0,
        'file_errors' => $totalErrors,
    ],
    'files' => $allFiles,
    'errors' => [],
];
file_put_contents($reportPath, json_encode($combined, JSON_PRETTY_PRINT));

echo "\nCompleted: $totalErrors total errors found.\n";
echo "Report saved to: $reportPath\n";
