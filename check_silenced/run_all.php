<?php

/**
 * Count silenced issues (inline annotations and baselines) for each framework.
 */

echo "=== Counting Silenced Issues ===\n\n";

$reposDir = __DIR__ . '/../repos';
$dataDir = __DIR__ . '/../reports/data';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$frameworks = [
    'cakephp' => ['src' => 'src'],
    'codeigniter' => ['src' => 'system'],
    'laravel' => ['src' => 'src'],
    'symfony' => ['src' => 'src'],
    'yii2' => ['src' => 'framework'],
];

// Laminas packages
$laminasPackages = [
    'laminas-mvc', 'laminas-db', 'laminas-view', 'laminas-form',
    'laminas-validator', 'laminas-router', 'laminas-servicemanager',
    'laminas-eventmanager', 'laminas-http', 'laminas-session',
];

foreach ($frameworks as $fw => $config) {
    echo "Processing $fw...\n";

    $srcDir = "$reposDir/$fw/{$config['src']}";
    $fwDir = "$reposDir/$fw";

    $result = [
        'phpstan_ignore' => 0,
        'psalm_suppress' => 0,
        'phpcs_ignore' => 0,
        'coverage_ignore' => 0,
        'phpstan_baseline' => 0,
        'psalm_baseline' => 0,
    ];

    // Count inline annotations
    if (is_dir($srcDir)) {
        $result['phpstan_ignore'] = countPattern($srcDir, '@phpstan-ignore');
        $result['psalm_suppress'] = countPattern($srcDir, '@psalm-suppress');
        $result['phpcs_ignore'] = countPattern($srcDir, 'phpcs:(ignore|disable)', true);
        $result['coverage_ignore'] = countPattern($srcDir, '@codeCoverageIgnore');
    }

    // Count PHPStan baseline entries
    $phpstanBaselines = glob("$fwDir/phpstan*baseline*.neon");
    foreach ($phpstanBaselines as $baseline) {
        $content = file_get_contents($baseline);
        $result['phpstan_baseline'] += preg_match_all('/message:/', $content);
    }

    // Count Psalm baseline entries
    $psalmBaselines = glob("$fwDir/psalm*baseline*.xml");
    foreach ($psalmBaselines as $baseline) {
        $content = file_get_contents($baseline);
        $result['psalm_baseline'] += preg_match_all('/<code>/', $content);
    }

    // Save to JSON
    $outputFile = "$dataDir/silenced_$fw.json";
    file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT) . "\n");
    echo "  Saved: $outputFile\n";
}

// Process Laminas multi-package
echo "Processing laminas (multi-package)...\n";
$laminasResult = [
    'phpstan_ignore' => 0,
    'psalm_suppress' => 0,
    'phpcs_ignore' => 0,
    'coverage_ignore' => 0,
    'phpstan_baseline' => 0,
    'psalm_baseline' => 0,
];

foreach ($laminasPackages as $pkg) {
    $pkgDir = "$reposDir/laminas/$pkg";
    $srcDir = "$pkgDir/src";

    // Count inline annotations
    if (is_dir($srcDir)) {
        $laminasResult['phpstan_ignore'] += countPattern($srcDir, '@phpstan-ignore');
        $laminasResult['psalm_suppress'] += countPattern($srcDir, '@psalm-suppress');
        $laminasResult['phpcs_ignore'] += countPattern($srcDir, 'phpcs:(ignore|disable)', true);
        $laminasResult['coverage_ignore'] += countPattern($srcDir, '@codeCoverageIgnore');
    }

    // Count PHPStan baseline entries
    $phpstanBaselines = glob("$pkgDir/phpstan*baseline*.neon");
    foreach ($phpstanBaselines as $baseline) {
        $content = file_get_contents($baseline);
        $laminasResult['phpstan_baseline'] += preg_match_all('/message:/', $content);
    }

    // Count Psalm baseline entries
    $psalmBaselines = glob("$pkgDir/psalm*baseline*.xml");
    foreach ($psalmBaselines as $baseline) {
        $content = file_get_contents($baseline);
        $laminasResult['psalm_baseline'] += preg_match_all('/<code>/', $content);
    }
}

$outputFile = "$dataDir/silenced_laminas.json";
file_put_contents($outputFile, json_encode($laminasResult, JSON_PRETTY_PRINT) . "\n");
echo "  Saved: $outputFile\n";

echo "\nDone!\n";

/**
 * Count occurrences of a pattern in PHP files.
 */
function countPattern(string $dir, string $pattern, bool $isRegex = false): int
{
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        if ($isRegex) {
            $count += preg_match_all('/' . $pattern . '/', $content);
        } else {
            $count += substr_count($content, $pattern);
        }
    }

    return $count;
}
