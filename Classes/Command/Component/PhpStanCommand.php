<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Command\Component;

use B13\Make\Component\ComponentInterface;
use B13\Make\Component\TestingSetup;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for creating docker based testing environment setup
 */
class PhpStanCommand extends SimpleComponentCommand
{
    /**
     * @var string $folder
     */
    protected $folder = '';

    /**
     * @var string $file
     */
    protected $file;

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Create configuration files for PHPStan');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetPackagePath = $this->package->getPackagePath();
        $this->showFlushCacheMessage = false;
        $this->folder = 'Build/';
        $packageKey = $this->package->getPackageKey();

        $this->file = 'phpstan.neon';
        parent::execute($input, $output);

        $this->file = 'phpstan.ci.neon';
        parent::execute($input, $output);

        $this->file = 'phpstan.local.neon';
        parent::execute($input, $output);

        if ($this->updateComposerFile($targetPackagePath)) {
            $this->io->writeln('<info>Updated composer.json for EXT:' . $packageKey . '</info>');
        } else {
            $this->io->writeln('<comment>Failed to update composer.json for EXT:' . $packageKey . '</comment>');
        }

        $this->io->success(
            'Please run "composer install" on ' .
            $packageKey . ' and execute: "bash Build/Scripts/runTests.sh -s phpstan"'
        );

        return 0;
    }

    /**
     * Extend/prepare composer.json of the extension
     * for acceptance tests
     *
     * @throws \JsonException
     */
    protected function updateComposerFile(string $packagePath): bool
    {
        $composerFile = $packagePath . '/composer.json';
        $composerJson = file_get_contents($composerFile);
        $composer = json_decode($composerJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \JsonException('Could not parse ' . $composerFile);
        }

        $composer['require-dev']['phpstan/phpstan'] = '^1.8.0';

        return GeneralUtility::writeFile($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);
    }

    protected function createComponent(): ComponentInterface
    {
        return (new TestingSetup($this->psr4Prefix))
            ->setExtensionKey($this->extensionKey)
            ->setDirectory($this->folder)
            ->setName($this->file);
    }

    protected function publishComponentConfiguration(ComponentInterface $component): bool
    {
        // As we do not need to publish a configuration, we just return true
        return true;
    }

    protected function getAbsoluteComponentDirectory(ComponentInterface $component): string
    {
        return $this->package->getPackagePath() . $this->folder;
    }
}
