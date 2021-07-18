<?php

//
// http://cs.sensiolabs.org/
//

$finder = PhpCsFixer\Finder::create()
    ->in(['app', 'src', 'tests']);

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder($finder);
