<?php

/**
 * EnvTest.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use LibreNMS\Util\EnvHelper;

test('parseArray', function () {
    putenv('PARSETEST=one,two');
    expect(EnvHelper::parseArray('PARSETEST'))->toBe(['one', 'two']);
    expect(EnvHelper::parseArray('PARSETESTNOTSET', 'default'))->toBe(['default']);
    expect(EnvHelper::parseArray('PARSETESTNOTSET'))->toBeNull();
    expect(EnvHelper::parseArray('PARSETESTNOTSET', 3))->toBe(3);
    expect(EnvHelper::parseArray('PARSETESTNOTSET', 'default', ['default']))->toBe('default');

    putenv('PARSETEST=');
    expect(EnvHelper::parseArray('PARSETEST', null, []))->toBe(['']);

    putenv('PARSETEST=*');
    expect(EnvHelper::parseArray('PARSETEST', null, ['*', '*']))->toBe('*');

    // clean the environment
    putenv('PARSETEST');
});

test('setEnv', function () {
    expect(EnvHelper::setEnv("ONE=one\nTWO=\n", [
        'ONE' => 'zero',
        'TWO' => '2$',
        'THREE' => 'space space',
    ]))->toBe("ONE=one\nTWO=2\$\nTHREE=\"space space\"\n");

    expect(EnvHelper::setEnv("#A=\nB=b\nF=blah\nC=\n", [
        'C' => 'c',
        'D' => 'd',
        'B' => 'nope',
        'A' => 'a',
    ], ['F', 'A']))->toBe("A=a\nB=b\nC=c\nD=d\n");

    // replace
    expect(EnvHelper::setEnv("COMMENT=nothing\n#COMMENT=something", [
        'COMMENT' => 'else',
    ], ['COMMENT']))->toBe("#COMMENT=something\nCOMMENT=else\n");
});
