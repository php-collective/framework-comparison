# PHP Framework Comparison Results

Generated: 2025-11-30 16:08:50

## Code Metrics (phploc)

| Framework | LOC | Classes | Methods | Avg Method Length | Complexity/LLOC |
|-----------|-----|---------|---------|-------------------|----------------|
| CakePHP | 148,259 | 525 | 4,981 | 4.3 | 0.45 |
| CodeIgniter | 117,117 | 459 | 4,045 | 4.4 | 0.48 |
| Laminas | 101,380 | 745 | 4,276 | 3.6 | 0.39 |
| Laravel | 242,353 | 1,077 | 12,634 | 2.1 | 0.38 |
| Symfony | 1,871,621 | 7,898 | 45,795 | 4 | 0.23 |
| Yii2 | 116,720 | 402 | 3,477 | 4.5 | 0.48 |

## Cognitive Complexity

| Framework | Methods | Avg Complexity | Max Complexity |
|-----------|---------|----------------|----------------|
| CakePHP | 4,640 | 0.33 | 8.385 |
| CodeIgniter | 3,679 | 0.38 | 11.814 |
| Laminas | 3,948 | 0.24 | 11.273 |
| Laravel | 11,902 | 0.08 | 7.233 |
| Symfony | 42,004 | 0.19 | 14.617 |
| Yii2 | 3,313 | 0.48 | 8.225 |

## Static Analysis Errors

| Framework | PHPStan | /1K LOC | Psalm | /1K LOC |
|-----------|---------|---------|-------|---------|
| CakePHP | 16 | 0.11 | 3088 | 20.83 |
| CodeIgniter | 2332 | 19.91 | 104 | 0.89 |
| Laminas | 3135 | 30.92 | 6188 | 61.04 |
| Laravel | 11782 | 48.62 | 8287 | 34.19 |
| Symfony | 2 | 0 | 55145 | 29.46 |
| Yii2 | 4494 | 38.5 | 3229 | 27.66 |

## Silenced Issues (Inline Annotations & Baselines)

| Framework | @phpstan-ignore | @psalm-suppress | phpcs:ignore | @codeCoverageIgnore | PHPStan Baseline | Psalm Baseline |
|-----------|-----------------|-----------------|--------------|---------------------|------------------|----------------|
| CakePHP | 17 | 0 | 67 | 0 | 124 | 0 |
| CodeIgniter | 12 | 25 | 0 | 225 | 0 | 87 |
| Laminas | 0 | 37 | 97 | 0 | 0 | 2841 |
| Laravel | 9 | 0 | 0 | 0 | 0 | 0 |
| Symfony | 0 | 0 | 0 | 0 | 0 | 0 |
| Yii2 | 0 | 0 | 18 | 0 | 91 | 0 |

## Frameworks Analyzed

| Framework | Version | PHP | Analysis Time | First Release | GitHub |
|-----------|---------|-----|---------------|---------------|--------|
| CakePHP | 5.2.9 | `>=8.1` | 16s | 2005 | [cakephp/cakephp](https://github.com/cakephp/cakephp) |
| CodeIgniter | 4.6.3 | `8.1+` | 50s | 2006 | [codeigniter4/CodeIgniter4](https://github.com/codeigniter4/CodeIgniter4) |
| Laminas | 3.9.x | `8.1+` | 1m 14s | 2006 | [laminas/laminas-mvc](https://github.com/laminas/laminas-mvc) |
| Laravel | 12.40.2 | `8.2+` | 1m 13s | 2011 | [laravel/framework](https://github.com/laravel/framework) |
| Symfony | 8.0.1-DEV | `>=8.4` | 10m 59s | 2005 | [symfony/symfony](https://github.com/symfony/symfony) |
| Yii2 | 2.0.55-dev | `>=7.4` | 26s | 2008 | [yiisoft/yii2](https://github.com/yiisoft/yii2) |

## Notes

- PHPStan and Psalm run at their strictest levels
- Silenced issues = errors hidden via inline annotations or baseline files
- Lower error counts indicate better type safety and static analysis compliance
- Laminas: analyzed 10 core packages (mvc, db, view, form, validator, router, servicemanager, eventmanager, http, session)
- Symfony: analyzed per-component using root autoloader (Psalm: 67 components, Cognitive: 66/67)
- LOC = Lines of Code, LLOC = Logical Lines of Code
- Complexity/LLOC = Cyclomatic complexity per logical line of code
