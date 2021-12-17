<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'b13 make',
    'description' => 'Kickstart functionality for TYPO3',
    'category' => 'be',
    'autoload' => [
        'psr-4' => [
            'B13\\Make\\' => 'Classes/',
        ]
    ],
];
