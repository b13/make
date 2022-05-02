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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Package\PackageInterface;
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
     * @var Finder $finder
     */
    protected $finder;

    /**
     * @var PackageInterface $package
     */
    protected $package;

    protected function configure(): void
    {
        $this->setDescription('Setup runTests.sh to run tests, linter, cgl in a Docker environment');
        $this->filesystem = GeneralUtility::makeInstance(Filesystem::class);
        $this->finder = GeneralUtility::makeInstance(Finder::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->package = $this->askForPackage($this->io);

        $codeTemplatePath = '/Resources/Private/CodeTemplates/RunTests';
        $templatePath = $this->getPackageResolver()->resolvePackage('b13/make')->getPackagePath() . $codeTemplatePath;
        $templatesToCreate = $this->finder->in($templatePath)->files();
        $targetPackagePath = $this->package->getPackagePath();

        foreach ($templatesToCreate as $template) {
            $this->prepareTemplate($template, $targetPackagePath);
        }

        $this->io->writeln('For details run "cd ' . $this->package->getPackagePath() . ' && ' . 'Build/Scripts/runTests.sh -h"');

        return 0;
    }

    protected function prepareTemplate(SplFileInfo $file, string $target): void
    {
        $target .= 'Build' . explode('RunTests', $file->getRealPath())[1];
        $templateContent = str_replace('{{EXTENSION_KEY}}', $this->package->getPackageKey(), $file->getContents());

        try {
            $this->filesystem->dumpFile($target, $templateContent);
            if ($file->getExtension() === 'sh') {
                $this->filesystem->chmod([$target], 0770);
            }

            $this->io->writeln('<info>File saved ' . $target . '</info>');
        } catch (IOException $exception) {
            $this->io->writeln('<error>Failed to save file in ' . $target . PHP_EOL . $exception->getMessage() . '</error>');
        }
    }
}
