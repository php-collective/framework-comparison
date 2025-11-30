# PHP Framework Comparison

Compare static analysis and code quality metrics of popular PHP frameworks.

## Frameworks Analyzed

- [cakephp/cakephp](https://github.com/cakephp/cakephp)
- [codeigniter4/CodeIgniter4](https://github.com/codeigniter4/CodeIgniter4)
- [laminas/laminas-mvc](https://github.com/laminas/laminas-mvc)
- [laravel/framework](https://github.com/laravel/framework)
- [symfony/symfony](https://github.com/symfony/symfony)
- [yiisoft/yii2](https://github.com/yiisoft/yii2)

## Metrics Collected

| Tool | Description |
|------|-------------|
| PHPStan (Level 8) | Static analysis error count |
| Psalm | Static analysis error count |
| phploc | Lines of code, classes, methods, complexity |
| Cognitive Complexity | Method complexity analysis |
| Silenced Issues | Inline suppressions and baseline entries |

## Requirements

- PHP 8.1+
- Composer
- Git

## Getting Started

```bash
# Clone the repo
git clone https://github.com/php-collective/framework-comparison.git
cd framework-comparison

# Install dependencies
composer install

# Run all checks (first run clones frameworks - takes a while)
php run_all.php
```

## Usage

```bash
# Run all analyses
php run_all.php

# Or run individual checks
php check_phpstan/run_all.php
php check_psalm/run_all.php
php check_phploc/run_all.php
php check_cognitive/run_all.php
php check_silenced/run_all.php

# Regenerate report from existing JSON
php generate_report.php
```

## Configuration

Framework settings are defined in `config.php`:

```php
'laravel' => [
    'repo' => 'laravel/framework',
    'branch' => '11.x',  // null = default branch
    'srcDir' => 'src',
],
```

- **branch**: Set to analyze a specific branch (e.g., `11.x` for stable vs `master` for dev)
- **srcDir**: Directory containing source code to analyze
- Laminas packages each have their own branch setting

To switch between dev/stable branches, edit `config.php` and delete the `repos/` directory to re-clone.

## Results

See **[reports/README.md](reports/README.md)** for the latest comparison table.

Note: The results are not interpreted here, only displayed as raw data so far.

## Out of Scope

This comparison focuses solely on static code analysis metrics. The following are explicitly **not** covered:

- **Performance benchmarks** - Runtime speed, memory usage, request throughput
- **Feature comparisons** - ORM capabilities, routing, templating, etc.
- **Security audits** - Vulnerability assessments, CVE history
- **Documentation quality** - Completeness, clarity, examples
- **Community & ecosystem** - Package availability, job market, support
- **Learning curve** - Ease of onboarding, developer experience
- **Test coverage** - Unit/integration test percentages
- **API stability** - Breaking changes between versions

## TODOs

- visible results as table or graph

Other ideas:

| Category       | Idea                                                              |
|----------------|-------------------------------------------------------------------|
| More metrics   | Test coverage %, dependency count                                 |
| Normalization  | Errors per class, errors per method                               |
| Breakdown      | PHPStan/Psalm errors by category (type safety, unused code, etc.) |
| Automation     | GitHub Actions to re-run monthly                                  |
| Trend tracking | Store historical data to show improvement over versions           |
