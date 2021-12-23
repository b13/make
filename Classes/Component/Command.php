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
 * Command component
 */
class Command extends AbstractComponent implements ServiceConfigurationComponentInterface
{
    /** @var string */
    protected $commandName = '';

    /** @var string */
    protected $description = '';

    /** @var bool */
    protected $schedulable = false;

    public function getCommandName(): string
    {
        return $this->commandName;
    }

    public function getCommandNameProposal(string $extensionKey): string
    {
        $extensionPrefix = trim(str_replace('_', '-', $extensionKey), '-');
        $commandName = trim(
            str_replace(
                'command',
                '',
                mb_strtolower(preg_replace('/(?<=\\w)([A-Z])/', '-\\1', $this->name) ?? '', 'utf-8')
            ),
            '-'
        );

        return $extensionPrefix . ':' . $commandName;
    }

    public function setCommandName(string $commandName): self
    {
        $this->commandName = $commandName;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setSchedulable(bool $schedulable): self
    {
        $this->schedulable = $schedulable;
        return $this;
    }

    public function __toString(): string
    {
        return $this->createFileContent(
            'Command',
            [
                'NAMESPACE' => $this->getNamespace(),
                'NAME' => $this->name,
                'DESCRIPTION' => $this->description,
            ]
        );
    }

    public function getServiceConfiguration(): array
    {
        return [
            $this->getClassName() => [
                'tags' => [
                    [
                        'name' => 'console.command',
                        'command' => $this->commandName,
                        'description' => $this->description,
                        'schedulable' => $this->schedulable
                    ]
                ]
            ]
        ];
    }
}
