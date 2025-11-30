# PHP Framework Comparison Results

Generated: 2025-11-30 13:20:45

## Static Analysis Errors

| Framework | PHPStan (Level 8) | Psalm |
|-----------|-------------------|-------|
| CakePHP | 16 | 3095 |
| CodeIgniter | 2354 | 104 |
| Laminas | 3135 | 6188 |
| Laravel | 11782 | 8287 |
| Symfony | 2 | 53010 |
| Yii2 | 4494 | 3229 |

## Code Metrics (phploc)

| Framework | LOC | Classes | Methods | Avg Method Length | Complexity/LLOC |
|-----------|-----|---------|---------|-------------------|----------------|
| CakePHP | 148,259 | 525 | 4,981 | 4.3 | 0.45 |
| CodeIgniter | 117,550 | 459 | 4,049 | 4.5 | 0.48 |
| Laminas | 101,380 | 745 | 4,276 | 3.6 | 0.39 |
| Laravel | 242,353 | 1,077 | 12,634 | 2.1 | 0.38 |
| Symfony | 1,871,683 | 7,898 | 45,798 | 4 | 0.23 |
| Yii2 | 116,720 | 402 | 3,477 | 4.5 | 0.48 |

## Cognitive Complexity

| Framework | Methods | Avg Complexity | Max Complexity |
|-----------|---------|----------------|----------------|
| CakePHP | 4,640 | 0.33 | 8.385 |
| CodeIgniter | 3,683 | 0.38 | 12.471 |
| Laminas | 3,948 | 0.24 | 11.273 |
| Laravel | 11,902 | 0.08 | 7.233 |
| Symfony | 42,007 | 0.19 | 14.617 |
| Yii2 | 3,313 | 0.48 | 8.225 |

## Silenced Issues (Inline Annotations & Baselines)

| Framework | @phpstan-ignore | @psalm-suppress | phpcs:ignore | @codeCoverageIgnore | PHPStan Baseline | Psalm Baseline |
|-----------|-----------------|-----------------|--------------|---------------------|------------------|----------------|
| CakePHP | 17 | 0 | 67 | 0 | 124 | 0 |
| CodeIgniter | 12 | 30 | 0 | 224 | 0 | 89 |
| Laminas | 0 | 37 | 97 | 0 | 0 | 2841 |
| Laravel | 9 | 0 | 0 | 0 | 0 | 0 |
| Symfony | 0 | 0 | 0 | 0 | 0 | 0 |
| Yii2 | 0 | 0 | 18 | 0 | 91 | 0 |

## Frameworks Analyzed

| Framework | Version | First Release | GitHub |
|-----------|---------|---------------|--------|
| CakePHP | 5.2.9 | 2005 | [cakephp/cakephp](https://github.com/cakephp/cakephp) |
| CodeIgniter | 4.6.3 | 2006 | [codeigniter4/CodeIgniter4](https://github.com/codeigniter4/CodeIgniter4) |
| Laminas | 3.9.x | 2006 | [laminas/laminas-mvc](https://github.com/laminas/laminas-mvc) |
| Laravel | 12.40.2 | 2011 | [laravel/framework](https://github.com/laravel/framework) |
| Symfony | 8.1.0-DEV | 2005 | [symfony/symfony](https://github.com/symfony/symfony) |
| Yii2 | 2.0.55-dev | 2008 | [yiisoft/yii2](https://github.com/yiisoft/yii2) |

## Notes

- PHPStan and Psalm run at their strictest sensible levels
- Silenced issues = errors hidden via inline annotations or baseline files
- Lower error counts indicate better type safety and static analysis compliance
- Laminas: analyzed 10 core packages (mvc, db, view, form, validator, router, servicemanager, eventmanager, http, session)
- Symfony Psalm: analyzed per-component using root autoloader (1 component failed)
- LOC = Lines of Code, LLOC = Logical Lines of Code
- Complexity/LLOC = Cyclomatic complexity per logical line of code
