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
 * Format seconds as human-readable time (e.g., "2m 30s" or "1h 15m").
 */
function formatTime(float $seconds): string
{
    if ($seconds < 60) {
        return round($seconds) . 's';
    }
    if ($seconds < 3600) {
        $m = floor($seconds / 60);
        $s = round($seconds % 60);
        return $s > 0 ? "{$m}m {$s}s" : "{$m}m";
    }
    $h = floor($seconds / 3600);
    $m = round(($seconds % 3600) / 60);
    return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
}

/**
 * Get PHP version requirement from composer.json.
 */
function getPhpRequirement(string $reposDir, string $fw): string
{
    $composerFile = "$reposDir/$fw/composer.json";
    if (!file_exists($composerFile)) {
        return '-';
    }

    $composer = json_decode(file_get_contents($composerFile), true);
    $php = $composer['require']['php'] ?? null;

    if (!$php) {
        return '-';
    }

    // Simplify common patterns
    $php = str_replace(' ', '', $php);

    // Multiple versions (||) -> extract minimum + "+"
    if (strpos($php, '||') !== false || strpos($php, '|') !== false) {
        if (preg_match('/^[~^]?(\d+\.\d+)/', $php, $m)) {
            return "`{$m[1]}+`";
        }
    }

    // ^X.Y means >=X.Y <(X+1).0, so show as X.Y+
    if (preg_match('/^\^(\d+\.\d+)/', $php, $m)) {
        return "`{$m[1]}+`";
    }

    // >=X.Y - keep as is
    if (preg_match('/^>=(\d+\.\d+)/', $php, $m)) {
        return "`>={$m[1]}`";
    }

    // ~X.Y means >=X.Y <X.(Y+1), so show as X.Y+
    if (preg_match('/^~(\d+\.\d+)/', $php, $m)) {
        return "`{$m[1]}+`";
    }

    // Fallback: clean up and fence
    $php = preg_replace('/[~^]/', '', $php);
    return "`$php`";
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

// Code metrics table
$md .= "## Code Metrics (phploc)\n\n";
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

// Static Analysis table
$md .= "\n## Static Analysis Errors\n\n";
$md .= "| Framework | PHPStan | /1K LOC | Psalm | /1K LOC |\n";
$md .= "|-----------|---------|---------|-------|---------|\n";

foreach ($frameworks as $fw) {
    $loc = $data[$fw]['phploc']['loc'] ?? null;
    $phpstan = $data[$fw]['phpstan'];
    $psalm = $data[$fw]['psalm'];

    $phpstanPer1k = ($phpstan !== null && $loc) ? round($phpstan / $loc * 1000, 2) : null;
    $psalmPer1k = ($psalm !== null && $loc) ? round($psalm / $loc * 1000, 2) : null;

    $md .= sprintf(
        "| %s | %s | %s | %s | %s |\n",
        $displayNames[$fw],
        fmt($phpstan),
        fmt($phpstanPer1k),
        fmt($psalm),
        fmt($psalmPer1k)
    );
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

// Load timing data
$timingFile = "$dataDir/timing.json";
$timing = file_exists($timingFile) ? json_decode(file_get_contents($timingFile), true) : [];

$md .= "\n## Frameworks Analyzed\n\n";
$md .= "| Framework | Version | PHP | Analysis Time | First Release | GitHub |\n";
$md .= "|-----------|---------|-----|---------------|---------------|--------|\n";

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
    $phpReq = getPhpRequirement($reposDir, $fw);
    $meta = $frameworkMeta[$fw];

    // Calculate total analysis time
    $fwTiming = $timing[$fw] ?? [];
    $totalTime = array_sum($fwTiming);
    $timeStr = $totalTime > 0 ? formatTime($totalTime) : '-';

    $md .= sprintf(
        "| %s | %s | %s | %s | %d | [%s](https://github.com/%s) |\n",
        $displayNames[$fw],
        $version,
        $phpReq,
        $timeStr,
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
$md .= "- Symfony: analyzed per-component using root autoloader (Psalm: 67 components, Cognitive: 66/67)\n";
$md .= "- LOC = Lines of Code, LLOC = Logical Lines of Code\n";
$md .= "- Complexity/LLOC = Cyclomatic complexity per logical line of code\n";

file_put_contents("$reportsDir/README.md", $md);

echo "Summary saved to: reports/README.md\n";
