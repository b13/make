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
use B13\Make\Component\RunTests;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for creating docker based test environment for a TYPO3 extension
 */
class RunTestsCommand extends SimpleComponentCommand
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
        $this->setDescription('Setup runTests.sh and docker-compose.yml in the ./Build folder to run tests, linter, cgl in a Docker environment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->file = 'runTests.sh';
        $this->folder = 'Build/Scripts/';
        parent::execute($input, $output);

        $this->file = 'docker-compose.yml';
        $this->folder = 'Build/testing-docker/';
        parent::execute($input, $output);

        $this->io->note('For details run "cd ' . $this->package->getPackagePath() . ' && ' . 'bash Build/Scripts/runTests.sh -h"');

        return 0;
    }

    public function getAbsoluteComponentDirectory(ComponentInterface $component): string
    {
        return $this->package->getPackagePath() . $this->folder;
    }

    protected function createComponent(): ComponentInterface
    {
        return (new RunTests($this->psr4Prefix))
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
