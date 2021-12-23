<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Tests\Unit\Component;

use B13\Make\Component\AbstractComponent;
use PHPUnit\Framework\TestCase;

class AbstractComponentTest extends TestCase
{
    /**
     * @test
     * @dataProvider psr4PrefixIsSanitizedDataProvider
     */
    public function psr4PrefixIsSanitized(string $psr4Prefix): void
    {
        $component = new class($psr4Prefix) extends AbstractComponent {
            public function __toString(): string
            {
                return '';
            }
            public function getPsr4Prefix(): string
            {
                return $this->psr4Prefix;
            }
        };

        self::assertEquals('Vendor\\Extension\\', $component->getPsr4Prefix());
    }

    public function psr4PrefixIsSanitizedDataProvider(): array
    {
        return [
            ['vendor/Extension'],
            ['Vendor/Extension'],
            ['/Vendor/Extension'],
            ['/vendor/Extension'],
            ['/Vendor/Extension'],
            ['/Vendor/Extension/'],
            ['vendor\\Extension'],
            ['Vendor\\Extension'],
            ['Vendor\\Extension\\'],
            ['\\Vendor\\Extension'],
            ['\\Vendor\\Extension\\'],
        ];
    }

    /**
     * @test
     * @dataProvider nameIsSanitizedDataProvider
     */
    public function nameIsSanitized(string $name): void
    {
        $component = new class('Vendor/Extension') extends AbstractComponent {
            public function __toString(): string
            {
                return '';
            }
        };

        $component->setName($name);
        self::assertEquals('MyClassName', $component->getName());
    }

    public function nameIsSanitizedDataProvider(): array
    {
        return [
            ['/MyClassName'],
            ['/myClassName'],
            ['MyClassName/'],
            ['myClassName/'],
            ['/MyClassName/'],
            ['/myClassName/'],
            ['\\MyClassName'],
            ['\\MyClassName'],
            ['MyClassName\\'],
            ['myClassName\\'],
            ['\\MyClassName\\'],
            ['\\myClassName\\'],
            ['myClassName'],
            ['MyClassName'],
        ];
    }

    /**
     * @test
     */
    public function directoryIsSanitized(): void
    {
        $component = new class('Vendor/Extension') extends AbstractComponent {
            public function __toString(): string
            {
                return '';
            }
        };

        $component->setDirectory('/Directory/');
        self::assertEquals('Directory/', $component->getDirectory());
    }

    /**
     * @test
     * @dataProvider namespaceIsSanitizedDataProvider
     */
    public function namespaceIsSanitized(string $directory, string $expectedNamespace): void
    {
        $component = new class('Vendor\\Extension\\') extends AbstractComponent {
            public function __toString(): string
            {
                return '';
            }
            public function getSanitizedNamespace(): string
            {
                return $this->getNamespace();
            }
        };

        $component->setDirectory($directory);

        self::assertEquals($expectedNamespace, $component->getSanitizedNamespace());
    }

    public function namespaceIsSanitizedDataProvider(): array
    {
        return [
            ['', 'Vendor\\Extension'],
            ['namespace', 'Vendor\\Extension\\Namespace'],
            ['Namespace', 'Vendor\\Extension\\Namespace'],
            ['/Namespace', 'Vendor\\Extension\\Namespace'],
            ['Namespace/', 'Vendor\\Extension\\Namespace'],
            ['/Namespace/', 'Vendor\\Extension\\Namespace'],
            ['/namespace/', 'Vendor\\Extension\\Namespace'],
            ['Namespace\\', 'Vendor\\Extension\\Namespace'],
            ['Namespace\\', 'Vendor\\Extension\\Namespace'],
            ['\\Namespace\\', 'Vendor\\Extension\\Namespace'],
            ['Namespace/Foobar', 'Vendor\\Extension\\Namespace\\Foobar'],
            ['Namespace/Foobar/', 'Vendor\\Extension\\Namespace\\Foobar'],
            ['\\Namespace/Foobar/', 'Vendor\\Extension\\Namespace\\Foobar'],
        ];
    }

    /**
     * @test
     * @dataProvider classNameIsSanitizedDataProvider
     */
    public function classNameIsSanitized(string $name, string $directory, string $expectedClassName): void
    {
        $component = new class('Vendor\\Extension\\') extends AbstractComponent {
            public function __toString(): string
            {
                return '';
            }
        };

        $component->setName($name);
        $component->setDirectory($directory);

        self::assertEquals($expectedClassName, $component->getClassName());
    }

    public function classNameIsSanitizedDataProvider(): array
    {
        return [
            ['/MyClassName', '', 'Vendor\\Extension\\MyClassName'],
            ['/myClassName', 'namespace', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['MyClassName/', 'Namespace', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['myClassName/', '/Namespace', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['/MyClassName/', 'Namespace/', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['/myClassName/', '/Namespace/', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['\\MyClassName', '/namespace/', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['\\MyClassName', 'Namespace\\', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['MyClassName\\', 'Namespace\\', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['myClassName\\', '\\Namespace\\', 'Vendor\\Extension\\Namespace\\MyClassName'],
            ['\\myClassName\\', 'Namespace/Foobar', 'Vendor\\Extension\\Namespace\\Foobar\\MyClassName'],
            ['myClassName', 'Namespace/Foobar/', 'Vendor\\Extension\\Namespace\\Foobar\\MyClassName'],
            ['MyClassName', '\\Namespace/Foobar/', 'Vendor\\Extension\\Namespace\\Foobar\\MyClassName'],
        ];
    }

    /**
     * @test
     * @dataProvider getIdentifierProposalTestDataProvider
     */
    public function getIdentifierProposalTest(
        string $psr4Prefix,
        string $name,
        string $expectedIdentifier
    ): void {
        $component = new class($psr4Prefix) extends AbstractComponent {
            public function __toString(): string
            {
                return '';
            }
            public function getSanitizedIdentifierProposal(): string
            {
                return $this->getIdentifierProposal();
            }
        };

        $component->setName($name);

        self::assertEquals($expectedIdentifier, $component->getSanitizedIdentifierProposal());
    }

    public function getIdentifierProposalTestDataProvider(): array
    {
        return [
            ['vendor/MyExtension', '/MyClassName', 'vendor/my-extension/my-class-name'],
            ['/Vendor/Extension', 'MyClassName/', 'vendor/extension/my-class-name'],
            ['/vendor/MyExtension', 'myClassName/', 'vendor/my-extension/my-class-name'],
            ['/Vendor/Extension', '/MyClassName/', 'vendor/extension/my-class-name'],
            ['/Vendor/MyExtension/', '/myClassName/', 'vendor/my-extension/my-class-name'],
            ['vendor\\Extension', 'MyClassName\\', 'vendor/extension/my-class-name'],
            ['Vendor\\MyExtension', '\\myClassName\\', 'vendor/my-extension/my-class-name'],
            ['Vendor\\Extension\\', 'myClassName', 'vendor/extension/my-class-name'],
            ['\\Vendor\\MyExtension', 'MyClassName', 'vendor/my-extension/my-class-name'],
            ['\\Vendor\\Extension\\', '\\MyClassName', 'vendor/extension/my-class-name'],
        ];
    }

    /**
     * @test
     * @dataProvider getIdentifierProposalWithStaticPrefixTestDataProvider
     */
    public function getIdentifierProposalWithStaticPrefixTest(
        string $psr4Prefix,
        string $name,
        string $expectedIdentifier
    ): void {
        $component = new class($psr4Prefix) extends AbstractComponent {
            public function __toString(): string
            {
                return '';
            }
            public function getSanitizedIdentifierProposal(): string
            {
                return $this->getIdentifierProposal('prefix');
            }
        };

        $component->setName($name);

        self::assertEquals($expectedIdentifier, $component->getSanitizedIdentifierProposal());
    }

    public function getIdentifierProposalWithStaticPrefixTestDataProvider(): array
    {
        return [
            ['vendor/Extension', '/MyClassName', 'prefix/my-class-name'],
            ['Vendor/Extension', '/myClassName', 'prefix/my-class-name'],
            ['/vendor/Extension', 'myClassName/', 'prefix/my-class-name'],
            ['/Vendor/Extension', '/MyClassName/', 'prefix/my-class-name'],
            ['/Vendor/Extension/', '/myClassName/', 'prefix/my-class-name'],
            ['vendor\\Extension', 'MyClassName\\', 'prefix/my-class-name'],
            ['Vendor\\Extension', '\\myClassName\\', 'prefix/my-class-name'],
            ['Vendor\\Extension\\', 'myClassName', 'prefix/my-class-name'],
            ['\\Vendor\\Extension', 'MyClassName', 'prefix/my-class-name'],
            ['\\Vendor\\Extension\\', '\\MyClassName', 'prefix/my-class-name'],
        ];
    }
}
