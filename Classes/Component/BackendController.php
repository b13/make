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
 * Backend controller component
 */
class BackendController extends AbstractComponent implements ArrayConfigurationComponentInterface, ServiceConfigurationComponentInterface
{
    /** @var string */
    protected $routeIdentifier = '';

    /** @var string */
    protected $routePath = '';

    /** @var string */
    protected $methodName = '';

    public function getRouteIdentifier(): string
    {
        return $this->routeIdentifier;
    }

    public function getRouteIdentifierProposal(string $prefix): string
    {
        return 'tx_' . trim($prefix, '_') . '_' . mb_strtolower(
            trim(str_replace('Controller', '', preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $this->name)), '_'),
            'utf-8'
        );
    }

    public function setRouteIdentifier(string $routeIdentifier): BackendController
    {
        $this->routeIdentifier = trim(str_replace('-', '_', $routeIdentifier), '_');
        return $this;
    }

    public function getRoutePathProposal(): string
    {
        return mb_strtolower(
            '/' . trim(str_replace('_', '/', str_replace('tx_', '', $this->routeIdentifier)), '/)'),
            'utf-8'
        );
    }

    public function setRoutePath(string $routePath): BackendController
    {
        $this->routePath = '/' . trim($routePath, '/');
        return $this;
    }

    public function setMethodName(string $methodName): BackendController
    {
        $this->methodName = $methodName;
        return $this;
    }

    public function getArrayConfiguration(): array
    {
        return  [
            'path' => $this->routePath,
            'target' => $this->getClassName() . ($this->methodName !== '' ? '::' . $this->methodName : ''),
        ];
    }

    public function __toString(): string
    {
        return $this->createFileContent(
            'BackendController',
            [
                'NAMESPACE' => $this->getNamespace(),
                'NAME' => $this->name,
                'METHOD' => $this->methodName ?: '__invoke',
            ]
        );
    }

    public function getServiceConfiguration(): array
    {
        return [
            $this->getClassName() => [
                'tags' => [
                    'backend.controller'
                ]
            ]
        ];
    }
}
