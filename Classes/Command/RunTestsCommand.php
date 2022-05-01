<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for creating docker based test environment for a TYPO3 extension
 */
class RunTestsCommand extends AbstractCommand
{
    /**
     * @var SymfonyStyle $io
     */
    protected $io;

    /**
     * @var Filesystem $filesystem
     */
    protected $filesystem;

    /**
     * @var PackageInterface $package
     */
    protected $package;

    protected function configure(): void
    {
        $this->setDescription('Setup runTests.sh to run tests, linter, cgl in a Docker environment');
        $this->filesystem = GeneralUtility::makeInstance(Filesystem::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->package = $this->askForPackage($this->io);

        $templatesToCreate = [
            [
                'source' => 'https://raw.githubusercontent.com/TYPO3/styleguide/main/Build/Scripts/runTests.sh',
                'target' => $this->package->getPackagePath() . 'Build/Scripts/runTests.sh'
            ],
            [
                'source' => 'https://raw.githubusercontent.com/TYPO3/styleguide/main/Build/testing-docker/docker-compose.yml',
                'target' => $this->package->getPackagePath() . 'Build/testing-docker/docker-compose.yml'
            ]
        ];

        foreach ($templatesToCreate as $template) {
            $this->prepareTemplate(
                $template['source'],
                $template['target']
            );
        }

        $this->io->writeln('<info>Created docker environment for testing:</info>');
        $filePaths = array_map(function ($ar) {return $ar['target'];}, $templatesToCreate);
        $this->io->listing($filePaths);
        $this->io->writeln('For details run "cd ' . $this->package->getPackagePath() . ' && ' . 'Build/Scripts/runTests.sh -h"');

        return 0;
    }

    protected function prepareTemplate(string $source, string $target): void
    {
        try {
            $template = $this->getGuzzleClient()->get($source);
        } catch (GuzzleException $exception) {
            $this->io->writeln('<error>Failed to get remote file ' . $source . '</error>');
            return;
        }

        $markerService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $templateContent = $markerService->substituteMarker(
            $template->getBody(),
            'styleguide',
            $this->package->getPackageKey()
        );

        try {
            $this->filesystem->dumpFile($target, $templateContent);
            if ((pathinfo($target)['extension'] ?? '') === 'sh') {
                $this->filesystem->chmod([$target], 0770);
            }
        } catch (IOException $exception) {
            $this->io->writeln('<error>Failed to save file in ' . $target . PHP_EOL . $exception->getMessage() . '</error>');
        }
    }

    protected function getGuzzleClient(): Client
    {
        return GeneralUtility::makeInstance(GuzzleClientFactory::class)->getClient();
    }
}
