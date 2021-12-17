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
 * Middleware component
 */
class Middleware extends AbstractComponent implements ArrayConfigurationComponentInterface
{
    /** @var string */
    protected $identifier = '';

    /** @var string */
    protected $type = '';

    /** @var array */
    protected $before = [];

    /** @var array */
    protected $after = [];

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setBefore(array $before): self
    {
        $this->before = $before;
        return $this;
    }

    public function setAfter(array $after): self
    {
        $this->after = $after;
        return $this;
    }

    public function __toString(): string
    {
        return $this->createFileContent(
            'Middleware',
            [
                'NAMESPACE' => $this->getNamespace(),
                'NAME' => $this->name,
            ]
        );
    }

    public function getArrayConfiguration(): array
    {
        $configuration = [
            'target' => $this->getClassName(),
        ];

        if ($this->before !== []) {
            $configuration['before'] = $this->before;
        }

        if ($this->after !== []) {
            $configuration['after'] = $this->after;
        }

        return $configuration;
    }
}
