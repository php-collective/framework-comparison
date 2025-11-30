<?php

/**
 * Generate markdown summary from existing JSON reports.
 * Run this after analyses are complete to regenerate the summary.
 */

echo "Generating summary report...\n";

$reportsDir = __DIR__ . '/reports';
$dataDir = __DIR__ . '/reports/data';
$reposDir = __DIR__ . '/repos';

// Alphabetical order
$frameworks = ['cakephp', 'codeigniter', 'laminas', 'laravel', 'symfony', 'yii2'];
$displayNames = [
    'cakephp' => 'CakePHP',
    'codeigniter' => 'CodeIgniter',
    'laminas' => 'Laminas',
    'laravel' => 'Laravel',
    'symfony' => 'Symfony',
    'yii2' => 'Yii2',
];

/**
 * Format a value for display: null => '-', otherwise the value.
 */
function fmt($value, bool $format = false, int $decimals = 0): string
{
    if ($value === null) {
        return '-';
    }
    if ($format) {
        return $decimals > 0 ? number_format($value, $decimals) : number_format($value);
    }
    return (string) $value;
}

/**
 * Detect framework version from source files.
 */
function getFrameworkVersion(string $reposDir, string $fw): string
{
    $fwDir = "$reposDir/$fw";

    return match ($fw) {
        'cakephp' => (function () use ($fwDir) {
            $lines = file("$fwDir/VERSION.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return trim(end($lines)) ?: 'unknown';
        })(),
        'codeigniter' => (function () use ($fwDir) {
            $content = file_get_contents("$fwDir/system/CodeIgniter.php");
            if (preg_match("/CI_VERSION\s*=\s*'([^']+)'/", $content, $m)) {
                return $m[1];
            }
            return 'unknown';
        })(),
        'laravel' => (function () use ($fwDir) {
            $content = file_get_contents("$fwDir/src/Illuminate/Foundation/Application.php");
            if (preg_match("/VERSION\s*=\s*'([^']+)'/", $content, $m)) {
                return $m[1];
            }
            return 'unknown';
        })(),
        'symfony' => (function () use ($fwDir) {
            $content = file_get_contents("$fwDir/src/Symfony/Component/HttpKernel/Kernel.php");
            if (preg_match("/VERSION\s*=\s*'([^']+)'/", $content, $m)) {
                return $m[1];
            }
            return 'unknown';
        })(),
        'yii2' => (function () use ($fwDir) {
            $content = file_get_contents("$fwDir/framework/BaseYii.php");
            if (preg_match("/return\s*'([^']+)'/", $content, $m)) {
                return $m[1];
            }
            return 'unknown';
        })(),
        'laminas' => (function () use ($fwDir) {
            // Get version from laminas-mvc branch name
            $branch = trim(shell_exec("git -C $fwDir/laminas-mvc rev-parse --abbrev-ref HEAD 2>/dev/null") ?: '');
            if (preg_match('/^(\d+\.\d+)/', $branch, $m)) {
                return $m[1] . '.x';
            }
            return 'unknown';
        })(),
        default => 'unknown',
    };
}

$data = [];
foreach ($frameworks as $fw) {
    $data[$fw] = [
        'phpstan' => null,
        'psalm' => null,
        'phploc' => null,
        'cognitive' => null,
        'silenced' => null,
    ];

    // PHPStan
    $phpstanFile = "$dataDir/phpstan_$fw.json";
    if (file_exists($phpstanFile)) {
        $json = json_decode(file_get_contents($phpstanFile), true);
        $data[$fw]['phpstan'] = $json['totals']['file_errors'] ?? null;
    }

    // Psalm
    $psalmFile = "$dataDir/psalm_$fw.json";
    if (file_exists($psalmFile)) {
        $json = json_decode(file_get_contents($psalmFile), true);
        $data[$fw]['psalm'] = is_array($json) ? count($json) : null;
    }

    // phploc
    $phplocFile = "$dataDir/phploc_$fw.json";
    if (file_exists($phplocFile)) {
        $data[$fw]['phploc'] = json_decode(file_get_contents($phplocFile), true);
    }

    // Cognitive
    $cognitiveFile = "$dataDir/cognitive_$fw.json";
    if (file_exists($cognitiveFile)) {
        $json = json_decode(file_get_contents($cognitiveFile), true);
        if (is_array($json)) {
            $totalMethods = 0;
            $totalScore = 0;
            $maxScore = 0;
            // JSON structure: { "ClassName": { "methods": { "methodName": { "score": N } } } }
            foreach ($json as $className => $classData) {
                if (isset($classData['methods']) && is_array($classData['methods'])) {
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
            $data[$fw]['cognitive'] = [
                'methods' => $totalMethods,
                'total' => $totalScore,
                'avg' => $totalMethods > 0 ? round($totalScore / $totalMethods, 2) : 0,
                'max' => $maxScore,
            ];
        }
    }

    // Silenced issues
    $silencedFile = "$dataDir/silenced_$fw.json";
    if (file_exists($silencedFile)) {
        $data[$fw]['silenced'] = json_decode(file_get_contents($silencedFile), true);
    }
}

// Build markdown
$md = "# PHP Framework Comparison Results\n\n";
$md .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Static Analysis table
$md .= "## Static Analysis Errors\n\n";
$md .= "| Framework | PHPStan (Level 8) | Psalm |\n";
$md .= "|-----------|-------------------|-------|\n";

foreach ($frameworks as $fw) {
    $phpstan = $data[$fw]['phpstan'] ?? '-';
    $psalm = $data[$fw]['psalm'] ?? '-';
    $md .= "| " . $displayNames[$fw] . " | $phpstan | $psalm |\n";
}

// Code metrics table
$md .= "\n## Code Metrics (phploc)\n\n";
$md .= "| Framework | LOC | Classes | Methods | Avg Method Length | Complexity/LLOC |\n";
$md .= "|-----------|-----|---------|---------|-------------------|----------------|\n";

foreach ($frameworks as $fw) {
    $loc = $data[$fw]['phploc'];
    if ($loc) {
        $md .= sprintf(
            "| %s | %s | %s | %s | %s | %s |\n",
            $displayNames[$fw],
            fmt($loc['loc'] ?? null, true),
            fmt($loc['classes'] ?? null, true),
            fmt($loc['methods'] ?? null, true),
            fmt(isset($loc['methodLlocAvg']) ? round($loc['methodLlocAvg'], 1) : null),
            fmt(isset($loc['ccnByLloc']) ? round($loc['ccnByLloc'], 2) : null)
        );
    } else {
        $md .= "| " . $displayNames[$fw] . " | - | - | - | - | - |\n";
    }
}

// Cognitive Complexity table
$md .= "\n## Cognitive Complexity\n\n";
$md .= "| Framework | Methods | Avg Complexity | Max Complexity |\n";
$md .= "|-----------|---------|----------------|----------------|\n";

foreach ($frameworks as $fw) {
    $cog = $data[$fw]['cognitive'];
    if ($cog) {
        $md .= sprintf(
            "| %s | %s | %s | %s |\n",
            $displayNames[$fw],
            fmt($cog['methods'] ?? null, true),
            fmt($cog['avg'] ?? null),
            fmt($cog['max'] ?? null)
        );
    } else {
        $md .= "| " . $displayNames[$fw] . " | - | - | - |\n";
    }
}

// Silenced Issues table
$md .= "\n## Silenced Issues (Inline Annotations & Baselines)\n\n";
$md .= "| Framework | @phpstan-ignore | @psalm-suppress | phpcs:ignore | @codeCoverageIgnore | PHPStan Baseline | Psalm Baseline |\n";
$md .= "|-----------|-----------------|-----------------|--------------|---------------------|------------------|----------------|\n";

foreach ($frameworks as $fw) {
    $s = $data[$fw]['silenced'];
    if ($s) {
        $md .= sprintf(
            "| %s | %s | %s | %s | %s | %s | %s |\n",
            $displayNames[$fw],
            fmt($s['phpstan_ignore'] ?? null),
            fmt($s['psalm_suppress'] ?? null),
            fmt($s['phpcs_ignore'] ?? null),
            fmt($s['coverage_ignore'] ?? null),
            fmt($s['phpstan_baseline'] ?? null),
            fmt($s['psalm_baseline'] ?? null)
        );
    } else {
        $md .= "| " . $displayNames[$fw] . " | - | - | - | - | - | - |\n";
    }
}

$md .= "\n## Frameworks Analyzed\n\n";
$md .= "| Framework | Version | First Release | GitHub |\n";
$md .= "|-----------|---------|---------------|--------|\n";

$frameworkMeta = [
    'cakephp' => ['year' => 2005, 'repo' => 'cakephp/cakephp'],
    'codeigniter' => ['year' => 2006, 'repo' => 'codeigniter4/CodeIgniter4'],
    'laminas' => ['year' => 2006, 'repo' => 'laminas/laminas-mvc'],
    'laravel' => ['year' => 2011, 'repo' => 'laravel/framework'],
    'symfony' => ['year' => 2005, 'repo' => 'symfony/symfony'],
    'yii2' => ['year' => 2008, 'repo' => 'yiisoft/yii2'],
];

foreach ($frameworks as $fw) {
    $version = getFrameworkVersion($reposDir, $fw);
    $meta = $frameworkMeta[$fw];
    $md .= sprintf(
        "| %s | %s | %d | [%s](https://github.com/%s) |\n",
        $displayNames[$fw],
        $version,
        $meta['year'],
        $meta['repo'],
        $meta['repo']
    );
}

$md .= "\n## Notes\n\n";
$md .= "- PHPStan and Psalm run at their strictest levels\n";
$md .= "- Silenced issues = errors hidden via inline annotations or baseline files\n";
$md .= "- Lower error counts indicate better type safety and static analysis compliance\n";
$md .= "- Laminas: analyzed 10 core packages (mvc, db, view, form, validator, router, servicemanager, eventmanager, http, session)\n";
$md .= "- Symfony Psalm: analyzed per-component using root autoloader (1 component failed)\n";
$md .= "- LOC = Lines of Code, LLOC = Logical Lines of Code\n";
$md .= "- Complexity/LLOC = Cyclomatic complexity per logical line of code\n";

file_put_contents("$reportsDir/README.md", $md);

echo "Summary saved to: reports/README.md\n";
