<?php

/**
 * Framework configuration.
 *
 * Each framework defines:
 * - repo: GitHub repository path
 * - branch: Branch to analyze (null = default branch)
 * - srcDir: Source directory to analyze
 */
return [
    'cakephp' => [
        'repo' => 'cakephp/cakephp',
        'branch' => null, // default branch (master) is stable
        'srcDir' => 'src',
    ],
    'codeigniter' => [
        'repo' => 'codeigniter4/CodeIgniter4',
        'branch' => null, // default branch (develop) - consider 4.x for stable
        'srcDir' => 'system',
    ],
    'laminas' => [
        'repo' => null, // multi-package, handled separately
        'branch' => null,
        'srcDir' => 'src',
        'packages' => [
            'laminas-mvc' => ['repo' => 'laminas/laminas-mvc', 'branch' => '3.9.x'],
            'laminas-db' => ['repo' => 'laminas/laminas-db', 'branch' => null],
            'laminas-view' => ['repo' => 'laminas/laminas-view', 'branch' => null],
            'laminas-form' => ['repo' => 'laminas/laminas-form', 'branch' => null],
            'laminas-validator' => ['repo' => 'laminas/laminas-validator', 'branch' => null],
            'laminas-router' => ['repo' => 'laminas/laminas-router', 'branch' => null],
            'laminas-servicemanager' => ['repo' => 'laminas/laminas-servicemanager', 'branch' => null],
            'laminas-eventmanager' => ['repo' => 'laminas/laminas-eventmanager', 'branch' => null],
            'laminas-http' => ['repo' => 'laminas/laminas-http', 'branch' => null],
            'laminas-session' => ['repo' => 'laminas/laminas-session', 'branch' => null],
        ],
    ],
    'laravel' => [
        'repo' => 'laravel/framework',
        'branch' => null, // default branch (master) is dev - consider 11.x for stable
        'srcDir' => 'src',
    ],
    'symfony' => [
        'repo' => 'symfony/symfony',
        'branch' => null, // default branch is dev - consider 7.2 for stable
        'srcDir' => 'src',
    ],
    'yii2' => [
        'repo' => 'yiisoft/yii2',
        'branch' => null, // default branch (master) is stable
        'srcDir' => 'framework',
    ],
];
