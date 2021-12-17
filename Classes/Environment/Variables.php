<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Environment;

/**
 * Check and access environment variables
 */
class Variables
{
    private const B13_ENV_PREFIX = 'B13_MAKE_';

    public static function has(string $name, bool $allowEmpty = false): bool
    {
        $value = $_ENV[self::B13_ENV_PREFIX . $name] ?? getenv(self::B13_ENV_PREFIX . $name);

        return $allowEmpty ? is_string($value) : (bool)$value;
    }

    public static function get(string $name): string
    {
        return $_ENV[self::B13_ENV_PREFIX . $name] ?? getenv(self::B13_ENV_PREFIX . $name) ?: '';
    }
}
