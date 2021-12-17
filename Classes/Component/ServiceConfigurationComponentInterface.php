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
 * Interface to be implemented by components, registered in the service configuration
 */
interface ServiceConfigurationComponentInterface extends ComponentInterface
{
    public function getServiceConfiguration(): array;
}
