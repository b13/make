<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\IO;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * IO operations for the service configuration of an extension
 */
class ServiceConfiguration extends AbstractConfiguration
{
    private const CONFIGURATION_DIRECTORY = 'Configuration/';
    private const CONFIGURATION_FILE = 'Services.yaml';

    /**
     * Write / update the service configuration
     *
     * @return bool Whether the service configuration was updated successfully
     */
    public function write(): bool
    {
        $directory = $this->packagePath . self::CONFIGURATION_DIRECTORY;
        if (!file_exists($directory)) {
            GeneralUtility::mkdir_deep($directory);
        }
        $file = $directory . self::CONFIGURATION_FILE;
        return GeneralUtility::writeFile($file, Yaml::dump($this->sortImportsOnTop($this->configuration), 99, 2), true);
    }

    /**
     * Initialize a new basic service configuration
     */
    public function createBasicServiceConfiguration(string $psr4Prefix): void
    {
        $this->configuration['services'] = [
            '_defaults' => [
                'autowire' => true,
                'autoconfigure' => true,
                'public' => false
            ],
            trim(str_replace('/', '\\', ucfirst($psr4Prefix)), '\\') . '\\' => [
                'resource' => '../Classes/*'
            ]
        ];
    }

    /**
     * Load the service configuration
     */
    protected function load(): array
    {
        try {
            $configuration = Yaml::parse(
                file_get_contents($this->packagePath . self::CONFIGURATION_DIRECTORY . self::CONFIGURATION_FILE) ?: ''
            );
        } catch (\Exception $e) {
            // In case configuration can not be loaded / parsed return an empty array
            return [];
        }

        return is_array($configuration) ? $configuration : [];
    }

    protected function sortImportsOnTop(array $newConfiguration): array
    {
        ksort($newConfiguration);
        if (isset($newConfiguration['imports'])) {
            $imports = $newConfiguration['imports'];
            unset($newConfiguration['imports']);
            $newConfiguration = array_merge(['imports' => $imports], $newConfiguration);
        }
        return $newConfiguration;
    }
}
