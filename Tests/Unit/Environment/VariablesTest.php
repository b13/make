<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "b13/make" by b13.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace B13\Make\Tests\Unit\Environment;

use B13\Make\Environment\Variables;
use PHPUnit\Framework\TestCase;

class VariablesTest extends TestCase
{
    /**
     * @test
     */
    public function prefixIsAppliedTest(): void
    {
        unset($_ENV);

        $_ENV['SOME_VALUE'] = 'some value';
        putenv('SOME_VALUE_PUTENV=some value putenv');

        self::assertFalse(Variables::has('SOME_VALUE'));
        self::assertFalse(Variables::has('SOME_VALUE_PUTENV'));
        self::assertEquals('', Variables::get('SOME_VALUE'));
        self::assertEquals('', Variables::get('SOME_VALUE_PUTENV'));

        $_ENV['B13_MAKE_SOME_VALUE'] = 'some value';
        putenv('B13_MAKE_SOME_VALUE_PUTENV=some value putenv');

        self::assertTrue(Variables::has('SOME_VALUE'));
        self::assertTrue(Variables::has('SOME_VALUE_PUTENV'));
        self::assertEquals('some value', Variables::get('SOME_VALUE'));
        self::assertEquals('some value putenv', Variables::get('SOME_VALUE_PUTENV'));
    }

    /**
     * @test
     */
    public function hasVariableTest(): void
    {
        unset($_ENV);

        self::assertFalse(Variables::has('NOT_SET'));

        $_ENV['B13_MAKE_EMPTY'] = '';
        putenv('B13_MAKE_EMPTY_PUTENV=');

        self::assertFalse(Variables::has('EMPTY'));
        self::assertTrue(Variables::has('EMPTY', true));
        self::assertFalse(Variables::has('EMPTY_PUTENV'));
        self::assertTrue(Variables::has('EMPTY_PUTENV', true));

        $_ENV['B13_MAKE_NOT_EMPTY'] = 'some value';
        putenv('B13_MAKE_NOT_EMPTY_PUTENV=some value');

        self::assertTrue(Variables::has('NOT_EMPTY'));
        self::assertTrue(Variables::has('NOT_EMPTY', true));
        self::assertTrue(Variables::has('NOT_EMPTY_PUTENV'));
        self::assertTrue(Variables::has('NOT_EMPTY_PUTENV', true));
    }

    /**
     * @test
     */
    public function getVariableTest(): void
    {
        unset($_ENV);

        self::assertEquals('', Variables::get('NOT_SET'));

        $_ENV['B13_MAKE_EMPTY'] = '';
        putenv('B13_MAKE_EMPTY_PUTENV=');

        self::assertEquals('', Variables::get('EMPTY'));
        self::assertEquals('', Variables::get('EMPTY_PUTENV'));

        $_ENV['B13_MAKE_NOT_EMPTY'] = 'some value';
        putenv('B13_MAKE_NOT_EMPTY_PUTENV=some value');

        self::assertEquals('some value', Variables::get('NOT_EMPTY'));
        self::assertEquals('some value', Variables::get('NOT_EMPTY_PUTENV'));

        putenv('B13_MAKE_OVERRIDE=');
        self::assertEquals('', Variables::get('OVERRIDE'));
        putenv('B13_MAKE_OVERRIDE=overridden');
        self::assertEquals('overridden', Variables::get('OVERRIDE'));
        $_ENV['B13_MAKE_OVERRIDE'] = 'overridden2';
        self::assertEquals('overridden2', Variables::get('OVERRIDE'));
        putenv('B13_MAKE_OVERRIDE=');
        // Putenv can't override $_ENV so we still get `overridden2`
        self::assertEquals('overridden2', Variables::get('OVERRIDE'));
        $_ENV['B13_MAKE_OVERRIDE'] = '';
        self::assertEquals('', Variables::get('OVERRIDE'));
    }
}
