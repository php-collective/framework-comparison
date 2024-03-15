#!/bin/bash

framework="cakephp/cakephp"

# Directory to store the repositories
repos_dir="./repos"

# Directory to store PHPStan reports
reports_dir="./reports"

# Ensure the directories exist
mkdir -p "$repos_dir"
mkdir -p "$reports_dir"

# Extract repository name
repo_name=$(basename "$framework")

# Clone the repository
[ ! -f "$repos_dir/$repo_name" ] && git clone "https://github.com/$framework.git" "../../reports/$repo_name"

# Run PHPStan
cd "$repos_dir/$repo_name"
composer install --optimize-autoloader
echo "" > ./phpstan-baseline.neon

vendor/bin/phpstan analyze --level=8 --no-progress --error-format=prettyJson > "../.$reports_dir/phpstan_$repo_name.json"

