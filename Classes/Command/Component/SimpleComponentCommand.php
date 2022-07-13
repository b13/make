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

use B13\Make\Command\AbstractCommand;
use B13\Make\Component\ComponentInterface;
use B13\Make\Component\ServiceConfigurationComponentInterface;
use B13\Make\Exception\AbortCommandException;
use B13\Make\Exception\InvalidPackageException;
use B13\Make\IO\ArrayConfiguration;
use B13\Make\IO\ServiceConfiguration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class for creating simple components with only one file and an array and/or service configuration
 */
abstract class SimpleComponentCommand extends AbstractCommand
{
    /** @var string */
    protected $extensionKey = '';

    /** @var PackageInterface */
    protected $package;

    /** @var string */
    protected $psr4Prefix = '';

    /** @var ServiceConfiguration */
    protected $serviceConfiguration;

    /** @var ArrayConfiguration */
    protected $arrayConfiguration;

    /** @var bool */
    protected $showFlushCacheMessage = true;

    abstract protected function createComponent(): ComponentInterface;
    abstract protected function publishComponentConfiguration(ComponentInterface $component): bool;

    protected function configure(): void
    {
        $this->addArgument('extensionKey', InputArgument::OPTIONAL);
    }

    /**
     * Initialization of context, e.g. extension key and package
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->package = $this->getPackage($input);
        if ($this->package === null || !$this->package->getValueFromComposerManifest()) {
            throw new InvalidPackageException(
                'The requested extension is invalid. You may want to execute "bin/typo3 make:extension".',
                1639664756
            );
        }
        $this->extensionKey = $this->package->getPackageKey();
        $this->psr4Prefix = $this->getPsr4Prefix($this->package);
    }

    /**
     * Execute component generation. Extending classes MAY NOT override this method
     * but instead only provide necessary information via the abstract methods.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $component = $this->createComponent();
        $absoluteComponentDirectory = $this->getAbsoluteComponentDirectory($component);

        if (!file_exists($absoluteComponentDirectory)) {
            try {
                GeneralUtility::mkdir_deep($absoluteComponentDirectory);
            } catch (\Exception $e) {
                $this->io->error('Creating of directory ' . $absoluteComponentDirectory . ' failed.');
                return 1;
            }
        }

        // Use .php in case no file extension was given
        $fileInfo = pathinfo($component->getName());
        $filename = $fileInfo['filename'] . '.' . (($fileInfo['extension'] ?? false) ? $fileInfo['extension'] : 'php');

        $componentFile = rtrim($absoluteComponentDirectory, '/') . '/' . $filename;
        if (file_exists($componentFile)
            && !$this->io->confirm('The file ' . $componentFile . ' already exists. Do you want to override it?')
        ) {
            $this->io->note('Aborting component generation.');
            return 0;
        }

        if (!GeneralUtility::writeFile($componentFile, (string)$component)) {
            $this->io->error('Creating ' . $component->getName() . ' in ' . $componentFile . ' failed.');
            return 1;
        }

        try {
            if (!$this->publishComponentConfiguration($component)) {
                return 1;
            }
        } catch (AbortCommandException $e) {
            $this->io->note($e->getMessage());
            return 0;
        }

        if ($this->showFlushCacheMessage) {
            $this->io->note('You might want to flush the cache now');
        }

        return 0;
    }

    protected function getPsr4Prefix(PackageInterface $package): string
    {
        return (string)key((array)($package->getValueFromComposerManifest('autoload')->{'psr-4'} ?? []));
    }

    protected function getExtensionClassesPath(PackageInterface $package, string $psr4Prefix): string
    {
        $classesPath = (string)($package->getValueFromComposerManifest('autoload')->{'psr-4'}->{$psr4Prefix} ?? '');
        return $classesPath ? (trim($classesPath, '/') . '/') : '';
    }

    /**
     * Initialize the service configuration for the current package
     */
    protected function initializeServiceConfiguration(): void
    {
        $this->serviceConfiguration = new ServiceConfiguration($this->package->getPackagePath());

        if (!isset($this->serviceConfiguration->getConfiguration()['services'])) {
            $basicConfiguration = (bool)$this->io->confirm('Your extension does not yet contain a service configuration. May we add one for you?', true);
            if (!$basicConfiguration) {
                throw new \RuntimeException('Can not add component without a service configuration.', 1639664757);
            }
            // Create basic service configuration for the extension
            $this->serviceConfiguration->createBasicServiceConfiguration($this->psr4Prefix);
        }
    }

    /**
     * Write the updated service configuration for the current package
     *
     * @throws AbortCommandException
     */
    public function writeServiceConfiguration(ServiceConfigurationComponentInterface $component): bool
    {
        $configuration = $this->serviceConfiguration->getConfiguration();

        if (!isset($configuration['services'])) {
            // Service configuration does not exist or was not properly initialized
            return false;
        }

        if (isset($configuration['services'][$component->getClassName()])
            && !$this->io->confirm('A service configuration for ' . $component->getClassName() . ' already exists. Do you want to override it?', true)
        ) {
            throw new AbortCommandException('Aborting component generation.', 1639664758);
        }

        $configuration['services'] = array_replace_recursive(
            $configuration['services'],
            $component->getServiceConfiguration()
        );

        return $this->serviceConfiguration->setConfiguration($configuration)->write();
    }

    /**
     * Initialize an array configuration for the current package
     */
    protected function initializeArrayConfiguration(string $file, string $directory = 'Configuration/'): void
    {
        $this->arrayConfiguration = new ArrayConfiguration($this->package->getPackagePath(), $file, $directory);
        if ($this->arrayConfiguration->getConfiguration() === []) {
            $this->io->note('The configuration file ' . $directory . $file . ' does not yet exist. It will be automatically created.');
        }
    }

    /**
     * Write the updated array configuration for the current package
     */
    protected function writeArrayConfiguration(): bool
    {
        if ($this->arrayConfiguration->getConfiguration() === []) {
            // Array configuration was not properly set
            return false;
        }

        return $this->arrayConfiguration->write();
    }

    /**
     * Returns the absolute path to the component directory, while assuming that all
     * components are in the extensions classes directory. Can be overwritten in commands,
     * if this is not the case.
     */
    protected function getAbsoluteComponentDirectory(ComponentInterface $component): string
    {
        return $this->package->getPackagePath()
            . $this->getExtensionClassesPath($this->package, $this->psr4Prefix)
            . $component->getDirectory();
    }
}
