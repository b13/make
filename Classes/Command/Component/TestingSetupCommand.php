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

/**
 * Command for creating docker based testing environment setup
 */
class TestingSetupCommand extends SimpleComponentCommand
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
        $this->setDescription('Create a docker based testing environment setup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->showFlushCacheMessage = false;

        $this->file = 'runTests.sh';
        $this->folder = 'Build/Scripts/';
        parent::execute($input, $output);

        $this->file = 'docker-compose.yml';
        $this->folder = 'Build/testing-docker/';
        parent::execute($input, $output);

        $this->io->success(
            'The docker based testing environment setup is ready. You can enter the root directory of ' .
            $this->package->getPackageKey() . ' and execute: "bash Build/Scripts/runTests.sh -h"'
        );

        $this->io->note('Running specific test suits like "cgl" or "unit" requires installing the corresponding packages and configuration.');

        return 0;
    }

    public function getAbsoluteComponentDirectory(ComponentInterface $component): string
    {
        return $this->package->getPackagePath() . $this->folder;
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
}
