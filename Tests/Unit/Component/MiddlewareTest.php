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

use B13\Make\Component\Middleware;
use PHPUnit\Framework\TestCase;

class MiddlewareTest extends TestCase
{
    /**
     * @var Middleware
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Middleware('Vendor\\Extension\\');
        $this->subject->setName('MyMiddleware');
        $this->subject->setIdentifier('vendor/extension/my-middleware');
        $this->subject->setBefore(['typo3/cms-frontend/timetracker', 'typo3/cms-core/verify-host-header']);
        $this->subject->setAfter(['typo3/cms-core/normalized-params-attribute', 'typo3/cms-frontend/eid']);
        $this->subject->setType('frontend');
    }

    /**
     * @test
     */
    public function generateMiddlewareFileContentTest(): void
    {
        $expectedFileContent = <<<EOF
<?php

declare(strict_types=1);

namespace Vendor\Extension;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface \$request, RequestHandlerInterface \$handler): ResponseInterface
    {
        // Do awesome stuff
        return \$handler->handle(\$request);
    }
}

EOF;

        self::assertEquals($expectedFileContent, $this->subject->__toString());
    }

    /**
     * @test
     */
    public function generateMiddlewareConfiguration(): void
    {
        $expected = [
            'target' => 'Vendor\\Extension\\MyMiddleware',
            'before' => ['typo3/cms-frontend/timetracker', 'typo3/cms-core/verify-host-header'],
            'after' => ['typo3/cms-core/normalized-params-attribute', 'typo3/cms-frontend/eid']
        ];

        self::assertEquals($expected, $this->subject->getArrayConfiguration());
    }
}
