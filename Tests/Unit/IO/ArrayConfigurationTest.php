<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Tests\Unit\IO;

use B13\Make\IO\ArrayConfiguration;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ArrayConfigurationTest extends TestCase
{
    private const TEST_DIRECTORY = '/tests';
    private const PACKAGE_PATH = self::TEST_DIRECTORY . '/ArrayConfigurationTest';
    private const TEST_CONFIGURATION = ['some' => ['test' => 'array']];

    protected function setUp(): void
    {
        parent::setUp();
        GeneralUtility::mkdir_deep(Environment::getVarPath() . self::PACKAGE_PATH . '/Configuration/');
        file_put_contents(
            Environment::getVarPath() . self::PACKAGE_PATH . '/Configuration/TestFile.php',
            "<?php\n\n" . 'return ' . ArrayUtility::arrayExport(self::TEST_CONFIGURATION) . ";\n"
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir(Environment::getVarPath() . self::TEST_DIRECTORY, true);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function arrayConfigurationIsLoaded(): void
    {
        $arrayConfiguration = new ArrayConfiguration(
            Environment::getVarPath() . self::PACKAGE_PATH,
            'TestFile.php',
            'Configuration/'
        );

        self::assertEquals(self::TEST_CONFIGURATION, $arrayConfiguration->getConfiguration());
    }

    /**
     * @test
     */
    public function arrayConfigurationIsWritten(): void
    {
        $arrayConfiguration = new ArrayConfiguration(
            Environment::getVarPath() . self::PACKAGE_PATH,
            'NewFile.php',
            'Configuration/Subdir/'
        );

        $configuration = $arrayConfiguration->getConfiguration();

        // Assert that the loaded file is "empty", which means it does not yet exist
        self::assertEmpty($configuration);

        $configuration['new_key'] = [
            'name' => 'some name'
        ];

        $arrayConfiguration->setConfiguration($configuration);
        $arrayConfiguration->write();

        $writtenArrayConfiguration = new ArrayConfiguration(
            Environment::getVarPath() . self::PACKAGE_PATH,
            'NewFile.php',
            'Configuration/Subdir/'
        );

        self::assertEquals($configuration, $writtenArrayConfiguration->getConfiguration());
    }
}
