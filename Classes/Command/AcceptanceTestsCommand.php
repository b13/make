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

use B13\Make\PackageResolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for creating a new backend controller component
 */
class AcceptanceTestsCommand extends AbstractCommand
{
    /**
     * @var Filesystem $filesystem
     */
    protected $filesystem;

    /**
     * @var SymfonyStyle $io
     */
    protected $io;

    /**
     * @var PackageInterface $package
     */
    protected $package;

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Prepare extensions to run acceptance tests');
        $this->filesystem = GeneralUtility::makeInstance(Filesystem::class);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $packages = $this->getPackageResolver()->getPackageManager()->getActivePackages();

        $choices = array_reduce($packages, function ($result, PackageInterface $package) {
            if ($package->getPackageMetaData()->getPackageType() === 'typo3-cms-extension') {
                $packageKey = $package->getPackageKey();
                $result[$packageKey] = $packageKey;
            }
            return $result;
        }, []);

        $selectedPackageName = $this->io->choice('Select a package to create acceptance tests for', $choices);
        $this->package = $this->getPackageResolver()->resolvePackage($selectedPackageName);

        $packageKey = $this->package->getPackageKey();
        $this->io->writeln('Selected package: ' . $packageKey);
        $finder = GeneralUtility::makeInstance(Finder::class);

        $targetPackage = $this->package->getPackagePath();
        $codeTemplatePath = '/Resources/Private/CodeTemplates/AcceptanceTests';
        $templatePath = $this->getPackageResolver()->resolvePackage('b13/make')->getPackagePath() . $codeTemplatePath;

        $this->filesystem->mkdir([
            $targetPackage . '/Tests/Acceptance',
            $targetPackage . '/Tests/Acceptance/Fixtures',
            $targetPackage . '/Tests/Acceptance/Application',
            $targetPackage . '/Tests/Acceptance/Support/Extension'
        ]);

        // Create public folder which is required for e.g. acceptance tests to work
        $publicFolderPath = $targetPackage . '/Resources/Public';
        if (!is_dir($publicFolderPath)) {
            $createPublic = $this->io->confirm('Resource/Public is necessary e.g. for acceptance tests. Do you want to create it now?', true);
            if ($createPublic) {
                $this->filesystem->mkdir([$publicFolderPath]);
                // Ensure the folder will be detected by git and committed
                $this->filesystem->touch([$publicFolderPath . '/.gitkeep']);
            }
        }

        $files = $finder->in($templatePath)->files();

        foreach ($files as $file) {
            $target = $targetPackage . 'Tests' . explode('AcceptanceTests', $file->getRealPath())[1];

            if (!is_file($target)) {
                $content = $file->getContents();
                $this->substituteMarkersAndSave($content, $target);
            } else {
                $overwrite = $this->io->confirm('File exists ' . basename($target) . '. Do you want to overwrite it?');
                if ($overwrite) {
                    $content = $file->getContents();
                    $this->substituteMarkersAndSave($content, $target);
                }
            }
        }

        if ($this->updateComposerFile($targetPackage)) {
            $this->io->writeln('<info>Updated composer.json for EXT:' . $packageKey . '</info>');
        } else {
            $this->io->writeln('<comment>Failed to update composer.json for EXT:' . $packageKey . '</comment>');
        }

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
        $namespace = rtrim($this->getNamespace(), '\\');

        // @todo: if a value already exists ask for permission to change it?!
        $composer['require-dev']['codeception/codeception'] = '^4.1';
        $composer['require-dev']['codeception/module-asserts'] = '^1.2';
        $composer['require-dev']['codeception/module-webdriver'] = '^1.1';
        $composer['require-dev']['typo3/testing-framework'] = '^6.16.2';

        $composer['autoload-dev']['psr-4'][$namespace . '\\Tests\\'] = 'Tests/';

        $composer['config']['vendor-dir'] = '.Build/vendor';
        $composer['config']['bin-dir'] = '.Build/bin';

        $composer['extra']['typo3/cms']['app-dir'] = '.Build';
        $composer['extra']['typo3/cms']['web-dir'] = '.Build/Web';

        return GeneralUtility::writeFile($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);
    }

    /**
     * Substitute marker values and save file to extension_key/Tests/
     *
     * @param string $content
     * @param string $target
     */
    protected function substituteMarkersAndSave(string $content, string $target): void
    {
        $markerService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $templateContent = $markerService->substituteMarker($content, '{{NAMESPACE}}', rtrim($this->getNamespace(), '\\'));
        $templateContent = $markerService->substituteMarker($templateContent, '{{EXTENSION_KEY}}', $this->package->getPackageKey());

        try {
            $this->filesystem->dumpFile($target, $templateContent);
        } catch (IOException $exception) {
            $this->io->writeln('<error>Failed to save file in ' . $target . '</error>');
        }
    }

    protected function getPackageResolver(): PackageResolver
    {
        return GeneralUtility::makeInstance(PackageResolver::class);
    }

    protected function getNamespace(): string
    {
        return (string)key((array)($this->package->getValueFromComposerManifest('autoload')->{'psr-4'} ?? []));
    }
}
