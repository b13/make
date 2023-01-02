<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Tests\Unit;

use B13\Make\PackageResolver;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Package\Package;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

class PackageResolverTest extends TestCase
{
    /**
     * @test
     */
    public function packageNotFoundReturnsNullTest(): void
    {
        $packageResolver = new PackageResolver($this->createMock(PackageManager::class));
        self::assertNull($packageResolver->resolvePackage('my_extension'));
    }

    /**
     * @test
     */
    public function packageFoundReturnsPackageInterface(): void
    {
        $packageManagerMock = $this->createMock(PackageManager::class);
        $packageManagerMock->method('getPackage')->with('my_extension')->willReturn($this->createMock(Package::class));
        self::assertInstanceOf(
            PackageInterface::class,
            (new PackageResolver($packageManagerMock))->resolvePackage('my_extension')
        );
    }
}
