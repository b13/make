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

use B13\Make\IO\ServiceConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ServiceConfigurationTest extends TestCase
{
    private const TEST_DIRECTORY = '/tests';
    private const PACKAGE_PATH = self::TEST_DIRECTORY . '/ServiceConfigurationTest';
    private const TEST_CONFIGURATION = [
        'services' => ['_defaults' => ['public' => true], 'Vendor\\Extension\\' => ['resource' => '../Classes/*']]
    ];

    protected function setUp(): void
    {
        parent::setUp();
        GeneralUtility::mkdir_deep(Environment::getVarPath() . self::PACKAGE_PATH . '/Configuration/');
        file_put_contents(
            Environment::getVarPath() . self::PACKAGE_PATH . '/Configuration/Services.yaml',
            Yaml::dump(self::TEST_CONFIGURATION, 99, 2)
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
    public function serviceConfigurationIsLoadedTest(): void
    {
        $serviceConfiguration = new ServiceConfiguration('/Fixtures/InvalidPath/');
        self::assertEmpty($serviceConfiguration->getConfiguration());

        $serviceConfiguration = new ServiceConfiguration(Environment::getVarPath() . self::PACKAGE_PATH);
        self::assertEquals(
            self::TEST_CONFIGURATION,
            $serviceConfiguration->getConfiguration()
        );
    }

    /**
     * @test
     * @dataProvider basicConfigurationIsCreatedDataProvider
     */
    public function basicConfigurationIsCreated(string $psr4Prefix): void
    {
        $serviceConfiguration = new ServiceConfiguration('/some/path/');
        self::assertEmpty($serviceConfiguration->getConfiguration());

        $serviceConfiguration->createBasicServiceConfiguration($psr4Prefix);
        $expectedBasicConfiguration = [
            '_defaults' => [
                'autowire' => true,
                'autoconfigure' => true,
                'public' => false
            ],
            'Vendor\\Extension\\' => [
                'resource' => '../Classes/*'
            ]
        ];

        self::assertEquals($expectedBasicConfiguration, $serviceConfiguration->getConfiguration()['services']);
    }

    public function basicConfigurationIsCreatedDataProvider(): array
    {
        return [
            ['vendor/Extension'],
            ['Vendor/Extension'],
            ['/Vendor/Extension'],
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
     */
    public function serviceConfigurationIsWrittenTest(): void
    {
        $serviceConfiguration = new ServiceConfiguration(Environment::getVarPath() . self::PACKAGE_PATH);
        // Get current configuration
        $configuration = $serviceConfiguration->getConfiguration();
        self::assertEquals(self::TEST_CONFIGURATION, $configuration);

        $configuration['new_key'] = [
            'name' => 'some name'
        ];
        $serviceConfiguration->setConfiguration($configuration);
        $serviceConfiguration->write();

        $writtenServiceConfiguration = new ServiceConfiguration(Environment::getVarPath() . self::PACKAGE_PATH);
        self::assertEquals($configuration, $writtenServiceConfiguration->getConfiguration());
    }

    /**
     * @test
     */
    public function serviceConfigurationSortsImportsOnTopTest(): void
    {
        $serviceConfiguration = new ServiceConfiguration(Environment::getVarPath() . self::PACKAGE_PATH);
        // Get current configuration
        $configuration = $serviceConfiguration->getConfiguration();
        self::assertEquals(self::TEST_CONFIGURATION, $configuration);

        $configuration['imports'] = [
            ['resource' => 'Some/Other/Configuration.yaml']
        ];
        $serviceConfiguration->setConfiguration($configuration);

        // Ensure imports are not already on top
        self::assertEquals('services', array_key_first($serviceConfiguration->getConfiguration()));
        $serviceConfiguration->write();

        $writtenServiceConfiguration = new ServiceConfiguration(Environment::getVarPath() . self::PACKAGE_PATH);
        // Now, imports should be the first array key
        self::assertEquals('imports', array_key_first($writtenServiceConfiguration->getConfiguration()));
        self::assertEquals(
            [['resource' => 'Some/Other/Configuration.yaml']],
            $writtenServiceConfiguration->getConfiguration()['imports']
        );
    }
}
