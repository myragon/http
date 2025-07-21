<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src');

return (new PhpCsFixer\Config())
    ->setParallelConfig(new PhpCsFixer\Runner\Parallel\ParallelConfig(2, 20))
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/tmp/.php-cs-fixer.cache');
