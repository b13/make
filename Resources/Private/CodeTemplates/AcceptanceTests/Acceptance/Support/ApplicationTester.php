<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Tests\Acceptance\Support;

use {{NAMESPACE}}\Tests\Acceptance\Support\_generated\ApplicationTesterActions;
use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;

/**
 * Default backend admin or editor actor in the backend
*/
class ApplicationTester extends \Codeception\Actor
{
    use ApplicationTesterActions;
    use FrameSteps;
}
