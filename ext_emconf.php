<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'b13 make',
    'description' => 'Kickstart functionality for TYPO3',
    'category' => 'misc',
    'author' => 'b13 GmbH',
    'author_email' => 'typo3@b13.com',
    'author_company' => 'b13 GmbH',
    'state' => 'beta',
    'clearCacheOnLoad' => true,
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99', // This differs to composer.json since TER does not yet allow v12 dependencies
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'B13\\Make\\' => 'Classes/',
        ]
    ],
];
