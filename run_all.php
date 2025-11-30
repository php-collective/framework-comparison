<?php

echo str_repeat('=', 60) . "\n";
echo "       PHP Framework Comparison - Full Analysis\n";
echo str_repeat('=', 60) . "\n\n";

$checks = ['phpstan', 'psalm', 'phploc', 'cognitive', 'silenced'];

foreach ($checks as $check) {
    passthru("php " . __DIR__ . "/check_$check/run_all.php");
    echo "\n\n";
}

echo str_repeat('=', 60) . "\n";
require __DIR__ . '/generate_report.php';
echo str_repeat('=', 60) . "\n";
