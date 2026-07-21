<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Acceptance\Backend;

use {{NAMESPACE}}\Tests\Acceptance\Support\ApplicationTester;

class ExampleCest
{
    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I)
    {
        $I->useExistingSession('admin');
        $I->switchToMainFrame();
    }

    /**
     * @param ApplicationTester $I
     * @throws \Exception
     */
    public function seeTestExample(ApplicationTester $I): void
    {
        $I->click('Page');
        $I->switchToContentFrame();
        $I->see('Web>Page module', '.callout-info');
    }
}
