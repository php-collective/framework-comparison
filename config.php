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
        'branch' => '5.x', // stable branch
        'srcDir' => 'src',
    ],
    'codeigniter' => [
        'repo' => 'codeigniter4/CodeIgniter4',
        'branch' => 'v4.6.3', // latest stable tag (develop is default)
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
        'branch' => '12.x', // stable branch
        'srcDir' => 'src',
    ],
    'symfony' => [
        'repo' => 'symfony/symfony',
        'branch' => '8.0', // stable branch
        'srcDir' => 'src',
    ],
    'yii2' => [
        'repo' => 'yiisoft/yii2',
        'branch' => null, // default branch (master) is stable
        'srcDir' => 'framework',
    ],
];
