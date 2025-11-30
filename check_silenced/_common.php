<?php

/**
 * Common silenced issues counting logic.
 *
 * Expected variables:
 * - $repoName: Name of the repo directory
 * - $srcDir: Source directory relative to repo root
 */

$baseDir = dirname(__DIR__);
$reposDir = $baseDir . '/repos';
$dataDir = $baseDir . '/reports/data';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$fwDir = "$reposDir/$repoName";
$srcPath = "$fwDir/$srcDir";

echo "=== Silenced: $repoName ===\n";

$result = [
    'phpstan_ignore' => 0,
    'psalm_suppress' => 0,
    'phpcs_ignore' => 0,
    'coverage_ignore' => 0,
    'phpstan_baseline' => 0,
    'psalm_baseline' => 0,
];

// Count inline annotations
if (is_dir($srcPath)) {
    $result['phpstan_ignore'] = countPattern($srcPath, '@phpstan-ignore');
    $result['psalm_suppress'] = countPattern($srcPath, '@psalm-suppress');
    $result['phpcs_ignore'] = countPattern($srcPath, 'phpcs:(ignore|disable)', true);
    $result['coverage_ignore'] = countPattern($srcPath, '@codeCoverageIgnore');
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
$outputFile = "$dataDir/silenced_$repoName.json";
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
