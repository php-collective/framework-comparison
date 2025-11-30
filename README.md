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

## Results

See [reports/README.md](reports/README.md) for the latest comparison table.

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
