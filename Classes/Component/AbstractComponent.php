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

/**
 * Abstract class for components
 */
abstract class AbstractComponent implements ComponentInterface
{
    /** @var string */
    protected $psr4Prefix;

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $directory = '';

    public function __construct(string $psr4Prefix)
    {
        $this->psr4Prefix = ucfirst(trim(str_replace('/', '\\', $psr4Prefix), '\\')) . '\\';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = ucfirst(str_replace(['/', '\\'], '', $name));
        return $this;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function setDirectory(string $directory): self
    {
        $this->directory = ltrim($directory, '/');
        return $this;
    }

    public function getClassName(): string
    {
        return $this->getNamespace() . '\\' . $this->name;
    }

    public function getIdentifierProposal(string $prefix = ''): string
    {
        $packagePrefix = $prefix ?: mb_strtolower(
            trim(
                preg_replace(
                    '/(?<=\\w)([A-Z])/',
                    '-\\1',
                    trim(str_replace('\\', '/', $this->psr4Prefix), '/')
                ) ?? '',
                '-'
            ),
            'utf-8'
        );

        $identifier = mb_strtolower(
            trim(preg_replace('/(?<=\\w)([A-Z])/', '-\\1', $this->name) ?? '', '-'),
            'utf-8'
        );

        return $packagePrefix . '/' . $identifier;
    }

    protected function getNamespace(): string
    {
        return rtrim($this->psr4Prefix . ucfirst(trim(str_replace('/', '\\', $this->directory), '\\')), '\\');
    }

    protected function createFileContent(string $fileName, array $replace): string
    {
        return (string)preg_replace_callback(
            '/\{\{([A-Z_]*)\}\}/',
            static function ($result) use ($replace): string {
                return $replace[$result[1]] ?? $result[0];
            },
            file_get_contents(__DIR__ . '/../../Resources/Private/CodeTemplates/' . $fileName)
        );
    }
}
