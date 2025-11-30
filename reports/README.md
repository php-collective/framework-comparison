# PHP Framework Comparison Results

Generated: 2025-11-30 03:49:10

## Static Analysis Errors

| Framework | PHPStan (Level 8) | Psalm |
|-----------|-------------------|-------|
| CakePHP | 16 | 3095 |
| CodeIgniter | 2354 | 104 |
| Laravel | 11782 | 8287 |
| Symfony | 2 | - |
| Yii2 | 4494 | 3229 |

## Code Metrics (phploc)

| Framework | LOC | Classes | Methods | Avg Method Length | Complexity/LLOC |
|-----------|-----|---------|---------|-------------------|----------------|
| CakePHP | 148,259 | 525 | 4,981 | 4.3 | 0.45 |
| CodeIgniter | 117,550 | 459 | 4,049 | 4.5 | 0.48 |
| Laravel | 242,353 | 1,077 | 12,634 | 2.1 | 0.38 |
| Symfony | 1,871,683 | 7,898 | 45,798 | 4 | 0.23 |
| Yii2 | 116,720 | 402 | 3,477 | 4.5 | 0.48 |

## Cognitive Complexity

| Framework | Methods | Avg Complexity | Max Complexity |
|-----------|---------|----------------|----------------|
| CakePHP | 4,640 | 0.33 | 8.385 |
| CodeIgniter | 3,683 | 0.38 | 12.471 |
| Laravel | 11,902 | 0.08 | 7.233 |
| Symfony | - | - | - |
| Yii2 | 3,313 | 0.48 | 8.225 |

## Frameworks Analyzed

| Framework | First Release | GitHub |
|-----------|---------------|--------|
| CakePHP | 2005 | [cakephp/cakephp](https://github.com/cakephp/cakephp) |
| CodeIgniter | 2006 | [codeigniter4/CodeIgniter4](https://github.com/codeigniter4/CodeIgniter4) |
| Laravel | 2011 | [laravel/framework](https://github.com/laravel/framework) |
| Symfony | 2005 | [symfony/symfony](https://github.com/symfony/symfony) |
| Yii2 | 2008 | [yiisoft/yii2](https://github.com/yiisoft/yii2) |

## Notes

- PHPStan and Psalm run at their strictest levels
- Lower error counts indicate better type safety and static analysis compliance
- Symfony Psalm: crashes due to codebase complexity (marked as `-`)
- LOC = Lines of Code, LLOC = Logical Lines of Code
- Complexity/LLOC = Cyclomatic complexity per logical line of code
