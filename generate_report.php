<?php

/**
 * Generate markdown summary from existing JSON reports.
 * Run this after analyses are complete to regenerate the summary.
 */

echo "Generating summary report...\n";

$reportsDir = __DIR__ . '/reports';
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

$data = [];
foreach ($frameworks as $fw) {
    $data[$fw] = [
        'phpstan' => null,
        'psalm' => null,
        'phploc' => null,
        'cognitive' => null,
    ];

    // PHPStan
    $phpstanFile = "$reportsDir/phpstan_$fw.json";
    if (file_exists($phpstanFile)) {
        $json = json_decode(file_get_contents($phpstanFile), true);
        $data[$fw]['phpstan'] = $json['totals']['file_errors'] ?? null;
    }

    // Psalm
    $psalmFile = "$reportsDir/psalm_$fw.json";
    if (file_exists($psalmFile)) {
        $json = json_decode(file_get_contents($psalmFile), true);
        $data[$fw]['psalm'] = is_array($json) ? count($json) : null;
    }

    // phploc
    $phplocFile = "$reportsDir/phploc_$fw.json";
    if (file_exists($phplocFile)) {
        $data[$fw]['phploc'] = json_decode(file_get_contents($phplocFile), true);
    }

    // Cognitive
    $cognitiveFile = "$reportsDir/cognitive_$fw.json";
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
            number_format($loc['loc'] ?? 0),
            number_format($loc['classes'] ?? 0),
            number_format($loc['methods'] ?? 0),
            round($loc['methodLlocAvg'] ?? 0, 1),
            round($loc['ccnByLloc'] ?? 0, 2)
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
            number_format($cog['methods']),
            $cog['avg'],
            $cog['max']
        );
    } else {
        $md .= "| " . $displayNames[$fw] . " | - | - | - |\n";
    }
}

$md .= "\n## Frameworks Analyzed\n\n";
$md .= "| Framework | First Release | GitHub |\n";
$md .= "|-----------|---------------|--------|\n";
$md .= "| CakePHP | 2005 | [cakephp/cakephp](https://github.com/cakephp/cakephp) |\n";
$md .= "| CodeIgniter | 2006 | [codeigniter4/CodeIgniter4](https://github.com/codeigniter4/CodeIgniter4) |\n";
$md .= "| Laminas | 2006 | [laminas/laminas-mvc](https://github.com/laminas/laminas-mvc) |\n";
$md .= "| Laravel | 2011 | [laravel/framework](https://github.com/laravel/framework) |\n";
$md .= "| Symfony | 2005 | [symfony/symfony](https://github.com/symfony/symfony) |\n";
$md .= "| Yii2 | 2008 | [yiisoft/yii2](https://github.com/yiisoft/yii2) |\n";

$md .= "\n## Notes\n\n";
$md .= "- PHPStan and Psalm run at their strictest levels\n";
$md .= "- Lower error counts indicate better type safety and static analysis compliance\n";
$md .= "- Symfony Psalm: crashes due to codebase complexity (marked as `-`)\n";
$md .= "- LOC = Lines of Code, LLOC = Logical Lines of Code\n";
$md .= "- Complexity/LLOC = Cyclomatic complexity per logical line of code\n";

file_put_contents("$reportsDir/README.md", $md);

echo "Summary saved to: reports/README.md\n";
