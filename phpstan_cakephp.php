<?php

// Define framework repository
$framework = "cakephp/cakephp";

// Directory to store the repositories
$repos_dir = "./repos";

// Directory to store PHPStan reports
$reports_dir = "./reports";

// Ensure the directories exist
if (!is_dir($repos_dir)) {
    mkdir($repos_dir, 0777, true);
}

if (!is_dir($reports_dir)) {
    mkdir($reports_dir, 0777, true);
}

// Extract repository name
$repo_name = basename($framework);

// Clone the repository
if (!is_dir("$repos_dir/$repo_name")) {
    $clone_command = "git clone https://github.com/$framework.git $repos_dir/$repo_name";
    exec($clone_command, $clone_output, $clone_status);

    if ($clone_status !== 0) {
        echo "Error: Failed to clone repository.\n";
        exit(1);
    }
}

// Run PHPStan
chdir("$repos_dir/$repo_name");
exec("composer install --optimize-autoloader", $composer_output, $composer_status);

if ($composer_status !== 0) {
    echo "Error: Failed to install dependencies.\n";
    exit(1);
}

exec("vendor/bin/phpstan analyze --level=8 --no-progress --error-format=prettyJson > ../.$reports_dir/phpstan_$repo_name.json", $phpstan_output, $phpstan_status);

