<?php

/**
 * Laminas silenced issues - analyzes multiple core packages.
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';
$laminasDir = "$reposDir/laminas";

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$packages = [
    'laminas-mvc', 'laminas-db', 'laminas-view', 'laminas-form',
    'laminas-validator', 'laminas-router', 'laminas-servicemanager',
    'laminas-eventmanager', 'laminas-http', 'laminas-session',
];

echo "=== Silenced: Laminas (multi-package) ===\n";

$result = [
    'phpstan_ignore' => 0,
    'psalm_suppress' => 0,
    'phpcs_ignore' => 0,
    'coverage_ignore' => 0,
    'phpstan_baseline' => 0,
    'psalm_baseline' => 0,
];

foreach ($packages as $pkg) {
    $pkgDir = "$laminasDir/$pkg";
    $srcDir = "$pkgDir/src";

    // Count inline annotations
    if (is_dir($srcDir)) {
        $result['phpstan_ignore'] += countPattern($srcDir, '@phpstan-ignore');
        $result['psalm_suppress'] += countPattern($srcDir, '@psalm-suppress');
        $result['phpcs_ignore'] += countPattern($srcDir, 'phpcs:(ignore|disable)', true);
        $result['coverage_ignore'] += countPattern($srcDir, '@codeCoverageIgnore');
    }

    // Count PHPStan baseline entries
    $phpstanBaselines = glob("$pkgDir/phpstan*baseline*.neon");
    foreach ($phpstanBaselines as $baseline) {
        $content = file_get_contents($baseline);
        $result['phpstan_baseline'] += preg_match_all('/message:/', $content);
    }

    // Count Psalm baseline entries
    $psalmBaselines = glob("$pkgDir/psalm*baseline*.xml");
    foreach ($psalmBaselines as $baseline) {
        $content = file_get_contents($baseline);
        $result['psalm_baseline'] += preg_match_all('/<code>/', $content);
    }
}

// Save to JSON
$outputFile = "$dataDir/silenced_laminas.json";
file_put_contents($outputFile, json_encode($result, JSON_PRETTY_PRINT) . "\n");

echo "  @phpstan-ignore:     {$result['phpstan_ignore']}\n";
echo "  @psalm-suppress:     {$result['psalm_suppress']}\n";
echo "  phpcs:ignore:        {$result['phpcs_ignore']}\n";
echo "  @codeCoverageIgnore: {$result['coverage_ignore']}\n";
echo "  PHPStan baseline:    {$result['phpstan_baseline']}\n";
echo "  Psalm baseline:      {$result['psalm_baseline']}\n";
echo "\nReport saved to: $outputFile\n";

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
