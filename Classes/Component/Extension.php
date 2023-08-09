<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Component;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Extension component
 */
class Extension implements \JsonSerializable
{
    /** @var string */
    protected $packageName = '';

    /** @var string */
    protected $packageKey = '';

    /** @var string */
    protected $extensionKey = '';

    /** @var string */
    protected $psr4Prefix = '';

    /** @var array<int, string> */
    protected $typo3Versions = [];

    /** @var string */
    protected $description = '';

    /** @var string */
    protected $directory = '';

    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function setPackageName(string $packageName): self
    {
        $this->packageName = $packageName;
        return $this;
    }

    public function setPackageKey(string $packageKey): self
    {
        $this->packageKey = $packageKey;
        return $this;
    }

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }

    public function setExtensionKey(string $extensionKey): self
    {
        $this->extensionKey = $extensionKey;
        return $this;
    }

    public function getPsr4Prefix(): string
    {
        return $this->psr4Prefix;
    }

    public function setPsr4Prefix(string $psr4Prefix): self
    {
        $this->psr4Prefix = str_replace('/', '\\', $psr4Prefix) . '\\';
        return $this;
    }

    public function setTypo3Versions(array $typo3Versions): self
    {
        asort($typo3Versions);
        $this->typo3Versions = $typo3Versions;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setDirectory(string $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    public function getExtensionPath(): string
    {
        return Environment::getProjectPath() . '/' . trim($this->directory, '/') . '/' . $this->packageKey . '/';
    }

    public function jsonSerialize(): array
    {
        return $this->createComposerManifest();
    }

    public function __toString()
    {
        return "<?php\n\n" . '$EM_CONF[$_EXTKEY] = ' . ArrayUtility::arrayExport($this->createExtensionConfiguration()) . ";\n";
    }

    protected function createComposerManifest(): array
    {
        return [
            'name' => $this->packageName,
            'description' => $this->description,
            'type' => 'typo3-cms-extension',
            'license' => ['GPL-2.0-or-later'],
            'require' => [
                'typo3/cms-core' => implode(' || ', $this->typo3Versions),
            ],
            'autoload' => [
                'psr-4' => [
                    $this->psr4Prefix => 'Classes/',
                ],
            ],
            'extra' => [
                'typo3/cms' => [
                    'extension-key' => $this->extensionKey,
                ],
            ],
        ];
    }

    protected function createExtensionConfiguration(): array
    {
        return [
            'title' => $this->extensionKey,
            'description' => $this->description,
            'constraints' => [
                'depends' => [
                    'typo3' => $this->getTypo3Constraint(),
                ],
            ],
            'autoload' => [
                'psr-4' => [
                    str_replace('\\', '\\\\', $this->psr4Prefix) => 'Classes/',
                ],
            ],
        ];
    }

    protected function getTypo3Constraint(): string
    {
        $min = $this->typo3Versions[array_key_first($this->typo3Versions)];
        $max = $this->typo3Versions[array_key_last($this->typo3Versions)];

        // While ^12.0 will also include upcoming sprint releases,
        // we need to set minor version "4" for the max version.
        // @todo Should be handled more efficient
        if ($max === '^12.0') {
            $max = str_replace('.0', '.4', $max);
        }

        return sprintf(
            '%s-%s',
            str_replace('^', '', $min) . '.0',
            str_replace('^', '', $max) . '.99'
        );
    }
}
