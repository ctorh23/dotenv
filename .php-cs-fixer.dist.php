<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRiskyAllowed(false)
    ->setRules([
        '@auto' => true
    ])
    ->setCacheFile(__DIR__ . '/var/build/php-cs-fixer/.php-cs-fixer.cache')
    ->setFinder(
        (new Finder())
            ->in([
                __DIR__ . '/src', 
                __DIR__ . '/tests'
            ])
            ->exclude('fixtures')
    )
;
