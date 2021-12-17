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
 * Event listener component
 */
class EventListener extends AbstractComponent implements ServiceConfigurationComponentInterface
{
    /** @var string */
    protected $identifier = '';

    /** @var string */
    protected $eventName = '';

    /** @var string */
    protected $methodName = '';

    public function getNameProposal(): string
    {
        $parts = explode('\\',  ltrim($this->eventName, '\\')) ?: [];
        return $parts !== [] ? end($parts) . 'Listener' : '';
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): self
    {
        $this->eventName = '\\' . ltrim(str_replace('/', '\\', $eventName), '\\');
        return $this;
    }

    public function setMethodName(string $methodName): self
    {
        $this->methodName = $methodName;
        return $this;
    }

    public function __toString(): string
    {
        return $this->createFileContent(
            'EventListener',
            [
                'NAMESPACE' => $this->getNamespace(),
                'NAME' => $this->name,
                'METHOD' => $this->methodName ?: '__invoke',
                'EVENT' => $this->eventName
            ]
        );
    }

    public function getServiceConfiguration(): array
    {
        $configuration = [
            $this->getClassName() => [
                'tags' => [
                    [
                        'name' => 'event.listener',
                        'identifier' => $this->identifier,
                        'event' => ltrim($this->eventName, '\\')
                    ]
                ]
            ]
        ];
        if ($this->methodName !== '') {
            $configuration[$this->getClassName()]['tags'][0]['method'] = $this->methodName;
        }

        return $configuration;
    }
}
