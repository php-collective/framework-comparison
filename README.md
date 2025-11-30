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

# Or run individual tools
php phpstan/run_all.php
php psalm/run_all.php
php phploc/run_all.php
php cognitive/run_all.php
php silenced/run_all.php

# Regenerate report from existing JSON
php generate_report.php
```

## Results

See [reports/README.md](reports/README.md) for the latest comparison table.

Note: The results are not interpreted here, only displayed as raw data so far.

## TODOs

- visible results as table or graph
