<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'vendor', 'bin', 'node_modules'])
    ->name('*.php')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        
        'no_unused_imports' => true,
        
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        
        'no_whitespace_in_blank_line' => true,
        'no_extra_blank_lines' => true,
        'no_trailing_whitespace' => true,
        
        'line_ending' => true,
        
        'single_quote' => true,
        'single_import_per_statement' => true,
        
        'phpdoc_to_comment' => false,
        
        // Добавляем declare(strict_types=1) к каждому файлу
        'declare_strict_types' => true,
        
        // Классы и функции должны импортироваться из глобального пространства имен
        'global_namespace_import' => [
            'import_constants' => true,
            'import_functions' => true,
            'import_classes' => true,
        ],
        
        // Дополнительные правила для лучшего форматирования
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_after_namespace' => true,
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw', 'try', 'if', 'switch', 'foreach'],
        ],
        'concat_space' => ['spacing' => 'one'],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
    ])
    ->setFinder($finder)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer.cache')
    ->setRiskyAllowed(true);