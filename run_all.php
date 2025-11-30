<?php

echo str_repeat('=', 60) . "\n";
echo "       PHP Framework Comparison - Full Analysis\n";
echo str_repeat('=', 60) . "\n\n";

$tools = ['phpstan', 'psalm', 'phploc', 'cognitive', 'silenced'];

foreach ($tools as $tool) {
    passthru("php " . __DIR__ . "/$tool/run_all.php");
    echo "\n\n";
}

echo str_repeat('=', 60) . "\n";
require __DIR__ . '/generate_report.php';
echo str_repeat('=', 60) . "\n";
