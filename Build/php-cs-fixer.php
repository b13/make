<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->getFinder()->exclude(['var', 'Resources/Private/CodeTemplates']);
return $config;
