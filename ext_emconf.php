<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Make',
    'description' => 'Kickstarter CLI tool for various TYPO3 functionalities',
    'category' => 'misc',
    'author' => 'b13 GmbH',
    'author_email' => 'typo3@b13.com',
    'author_company' => 'b13 GmbH',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'version' => '0.1.6',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'B13\\Make\\' => 'Classes/',
        ],
    ],
];
