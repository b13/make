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
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $packageResolver = new PackageResolver($packageManagerProphecy->reveal());

        self::assertNull($packageResolver->resolvePackage('my_extension'));
    }

    /**
     * @test
     */
    public function packageFoundReturnsPackageInterface(): void
    {
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $packageProphecy = $this->prophesize(Package::class);
        $packageManagerProphecy->getPackage('my_extension')->willReturn($packageProphecy->reveal());

        self::assertInstanceOf(
            PackageInterface::class,
            (new PackageResolver($packageManagerProphecy->reveal()))->resolvePackage('my_extension')
        );
    }
}
