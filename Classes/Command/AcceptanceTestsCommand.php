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

    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->package = $this->askForPackage($this->io);

        $packageKey = $this->package->getPackageKey();
        $targetPackagePath = $this->package->getPackagePath();

        if ($this->updateComposerFile($targetPackagePath)) {
            $this->io->writeln('<info>Updated composer.json for EXT:' . $packageKey . '</info>');
        } else {
            $this->io->writeln('<comment>Failed to update composer.json for EXT:' . $packageKey . '</comment>');
        }

        $this->io->writeln('Selected package: ' . $packageKey);
        $finder = GeneralUtility::makeInstance(Finder::class);

        $codeTemplatePath = '/Resources/Private/CodeTemplates/AcceptanceTests';
        $templatePath = $this->getPackageResolver()->resolvePackage('b13/make')->getPackagePath() . $codeTemplatePath;

        $this->filesystem->mkdir([
            $targetPackagePath . '/Tests/Acceptance/Fixtures',
            $targetPackagePath . '/Tests/Acceptance/Application',
            $targetPackagePath . '/Tests/Acceptance/Support/Extension'
        ]);

        // Create public folder which is required for e.g. acceptance tests to work
        $publicFolderPath = $targetPackagePath . '/Resources/Public';
        if (!is_dir($publicFolderPath)) {
            $createPublic = $this->io->confirm(
                'Resource/Public is necessary e.g. for acceptance tests. Do you want to create it now?',
                true
            );

            if ($createPublic) {
                $this->filesystem->mkdir([$publicFolderPath]);
                // Ensure the folder will be detected by git and committed
                $this->filesystem->touch([$publicFolderPath . '/.gitkeep']);
            }
        }

        $files = $finder->in($templatePath)->files();

        foreach ($files as $file) {
            $target = $targetPackagePath . 'Tests' . explode('AcceptanceTests', $file->getRealPath())[1];

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

        $namespace = rtrim($this->getNamespace(), '\\');

        $addToComposerFile = [
            'require-dev' => [
                'codeception/codeception' => '^4.1',
                'codeception/module-asserts' => '^1.2',
                'codeception/module-webdriver' => '^1.1',
                'typo3/testing-framework' => '^6.16.2'
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $namespace . '\\Tests\\' => 'Tests/'
                ]
            ],
            'config' => [
                'vendor-dir' => '.Build/vendor',
                'bin-dir' => '.Build/bin',
            ],
            'extra' => [
                'typo3/cms' => [
                    'app-dir' => '.Build',
                    'web-dir' => '.Build/Web',
                ]
            ]
        ];

        $enhancedComposer = $this->enhanceComposerFile($composer, $addToComposerFile);

        return GeneralUtility::writeFile($composerFile, json_encode($enhancedComposer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);
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
        $templateContent = $markerService->substituteMarker(
            $content,
            '{{NAMESPACE}}',
            rtrim($this->getNamespace(), '\\')
        );
        $templateContent = $markerService->substituteMarker(
            $templateContent,
            '{{EXTENSION_KEY}}',
            $this->package->getPackageKey()
        );

        try {
            $this->filesystem->dumpFile($target, $templateContent);
        } catch (IOException $exception) {
            $this->io->writeln('<error>Failed to save file in ' . $target . '</error>');
        }
    }

    protected function getNamespace(): string
    {
        return (string)key((array)($this->package->getValueFromComposerManifest('autoload')->{'psr-4'} ?? []));
    }

    private function enhanceComposerFile(array &$composer, array &$addToComposerFile): array
    {
        foreach ($addToComposerFile as $key => $value) {
            if (is_array($value) && isset($composer[$key])) {
                $this->enhanceComposerFile($composer[$key], $value);
            } else {
                $composer[$key] = $value;
            }
        }

        return $composer;
    }
}
