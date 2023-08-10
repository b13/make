<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Acceptance\Support\Extension;

use Symfony\Component\Mailer\Transport\NullTransport;
use TYPO3\TestingFramework\Core\Acceptance\Extension\BackendEnvironment;

/**
 * Load various core extensions and styleguide and call styleguide generator
 */
class ExtensionEnvironment extends BackendEnvironment
{
    /**
     * Load a list of core extensions
     *
     * @var array
     */
    protected $localConfig = [
        'coreExtensionsToLoad' => [
            'core',
            'extbase',
            'filelist',
            'setup',
            'frontend',
            'fluid',
            'recordlist',
            'backend',
            'install',
        ],
        'testExtensionsToLoad' => [
            'typo3conf/ext/{{EXTENSION_KEY}}',
        ],
        'xmlDatabaseFixtures' => [
            'PACKAGE:typo3/testing-framework/Resources/Core/Acceptance/Fixtures/be_users.xml',
            'PACKAGE:../Web/typo3conf/ext/{{EXTENSION_KEY}}/Tests/Acceptance/Fixtures/pages.xml',
            'PACKAGE:../Web/typo3conf/ext/{{EXTENSION_KEY}}/Tests/Acceptance/Fixtures/be_sessions.xml',
            'PACKAGE:../Web/typo3conf/ext/{{EXTENSION_KEY}}/Tests/Acceptance/Fixtures/sys_template.xml',
        ],
        'configurationToUseInTestInstance' => [
            'MAIL' => [
                'transport' => NullTransport::class,
            ],
        ],

        // Link files/folders required for your acceptance tests to run
        // 'pathsToLinkInTestInstance' => [
        //    'typo3conf/ext/{{EXTENSION_KEY}}/Tests/Acceptance/Fixtures/sites' => 'typo3conf/sites',
        // ]
    ];
}
