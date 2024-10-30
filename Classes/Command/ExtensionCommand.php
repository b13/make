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

use B13\Make\Component\Extension;
use B13\Make\IO\ServiceConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for creating a new TYPO3 extension
 */
class ExtensionCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setDescription('Create a TYPO3 extension');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = (string)$this->io->ask(
            'Enter the composer package name (e.g. "vendor/awesome")',
            null,
            [$this, 'validatePackageKey']
        );

        [,$packageKey] = explode('/', $packageName);

        $extensionKey = (string)$this->io->ask(
            'Enter the extension key',
            str_replace('-', '_', $packageKey)
        );

        $psr4Prefix = (string)$this->io->ask(
            'Enter the PSR-4 namespace',
            str_replace(['_', '-'], [], ucwords($packageName, '/-_'))
        );

        $availableTypo3Versions = [
            '^10.4' => 'TYPO3 v10 LTS',
            '^11.5' => 'TYPO3 v11 LTS',
            '^12.4' => 'TYPO3 v12 LTS',
            '^13.4' => 'TYPO3 v13 LTS',
        ];
        $question = $this->io->askQuestion((new ChoiceQuestion(
            'Choose supported TYPO3 versions (comma separate for multiple)',
            array_combine([10, 11, 12, 13], array_values($availableTypo3Versions)),
            12
        ))->setMultiselect(true));

        $supportedTypo3Versions = [];
        foreach ($question as $resultPosition) {
            $versionConstraint = array_search($resultPosition, $availableTypo3Versions, true);
            $supportedTypo3Versions[$this->getMajorVersion($versionConstraint)] = $versionConstraint;
        }

        $description = $this->io->ask(
            'Enter a description of the extension',
            null,
            [$this, 'answerRequired']
        );

        $directory = (string)$this->io->ask(
            'Where should the extension be created?',
            $this->getProposalFromEnvironment('EXTENSION_DIR', 'src/extensions/')
        );

        $extension = (new Extension())
            ->setPackageName($packageName)
            ->setPackageKey($packageKey)
            ->setExtensionKey($extensionKey)
            ->setPsr4Prefix($psr4Prefix)
            ->setTypo3Versions($supportedTypo3Versions)
            ->setDescription($description)
            ->setDirectory($directory);

        // Create extension directory
        $absoluteExtensionPath = $extension->getExtensionPath();
        if (!file_exists($absoluteExtensionPath)) {
            try {
                GeneralUtility::mkdir_deep($absoluteExtensionPath);
            } catch (\Exception $e) {
                $this->io->error('Creating of directory ' . $absoluteExtensionPath . ' failed');
                return 1;
            }
        }

        // Create composer.json
        $composerFile = rtrim($absoluteExtensionPath, '/') . '/composer.json';
        if (file_exists($composerFile)
            && !$this->io->confirm('A composer.json does already exist. Do you want to override it?', true)
        ) {
            $this->io->note('Creating composer.json skipped');
        } elseif (!GeneralUtility::writeFile($composerFile, json_encode($extension, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true)) {
            $this->io->error('Creating composer.json failed');
            return 1;
        }

        // Add basic service configuration if requested
        if ($this->io->confirm('May we add a basic service configuration for you?', true)) {
            $serviceConfiguration = new ServiceConfiguration($absoluteExtensionPath);
            if ($serviceConfiguration->getConfiguration() !== []
                && !$this->io->confirm('A service configuration does already exist. Do you want to override it?', true)
            ) {
                $this->io->note('Creating service configuration skipped');
            } else {
                $serviceConfiguration->createBasicServiceConfiguration($extension->getPsr4Prefix());
                if (!$serviceConfiguration->write()) {
                    $this->io->warning('Creating service configuration failed');
                    return 1;
                }
            }
        }

        // Add ext_emconf.php if TYPO3 v10 or requested (default=NO)
        if (isset($supportedTypo3Versions[10])
            || $this->io->confirm('May we create a ext_emconf.php for you?', false)
        ) {
            $extEmConfFile = rtrim($absoluteExtensionPath, '/') . '/ext_emconf.php';
            if (file_exists($extEmConfFile)
                && !$this->io->confirm('A ext_emconf.php does already exist. Do you want to override it?')
            ) {
                $this->io->note('Creating ext_emconf.php skipped');
            } elseif (!GeneralUtility::writeFile($extEmConfFile, (string)$extension)) {
                $this->io->error('Creating ' . $extEmConfFile . ' failed.');
                return 1;
            }
        }

        // Create the "Classes/" folder
        if (!file_exists($absoluteExtensionPath . 'Classes/')) {
            try {
                GeneralUtility::mkdir($absoluteExtensionPath . 'Classes/');
            } catch (\Exception $e) {
                $this->io->error('Creating of the "Classes/" folder in ' . $absoluteExtensionPath . ' failed');
                return 1;
            }
        }

        $this->io->success('Successfully created the extension ' . $extension->getExtensionKey() . ' (' . $extension->getPackageName() . ').');
        $this->io->note('Depending on your installation, the extension now might have to be activated manually.');

        return 0;
    }

    protected function getMajorVersion(string $versionConstraint): int
    {
        return (int)preg_replace_callback(
            '/^\^([0-9]{1,2}).*$/',
            static function ($matches) { return $matches[1]; },
            $versionConstraint
        );
    }
}
