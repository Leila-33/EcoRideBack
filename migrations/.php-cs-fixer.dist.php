<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')   // dossier à analyser
    ->in(__DIR__ . '/tests'); // facultatif

return PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
