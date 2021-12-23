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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * IO operations for the array configurations of an extension
 */
class ArrayConfiguration extends AbstractConfiguration
{
    /** @var string */
    protected $file;

    /** @var string */
    protected $directory;

    public function __construct(string $packagePath, string $file, string $directory)
    {
        $this->file = trim($file, '/');
        $this->directory = trim($directory, '/') . '/';
        parent::__construct($packagePath);
    }

    /**
     * Write / update the array configuration
     *
     * @return bool Whether the array configuration was updated successfully
     */
    public function write(): bool
    {
        $directory = $this->packagePath . $this->directory;
        if (!file_exists($directory)) {
            GeneralUtility::mkdir_deep($directory);
        }
        $file = $directory . $this->file;
        return GeneralUtility::writeFile($file, "<?php\n\n" . 'return ' . ArrayUtility::arrayExport($this->configuration) . ";\n", true);
    }

    /**
     * Load the array configuration
     */
    protected function load(): array
    {
        $configurationFile = $this->packagePath . $this->directory . $this->file;
        if (!file_exists($configurationFile)) {
            return [];
        }
        $configuration = require $configurationFile;

        return is_array($configuration) ? $configuration : [];
    }
}
