<?php

/**
 * FunctionsTest.php
 *
 * tests functions in includes/functions.php
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
 * @copyright  2017 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use LibreNMS\Device\YamlDiscovery;
use LibreNMS\Enum\IntegerType;
use LibreNMS\Util\Number;
use LibreNMS\Util\StringHelpers;

uses(\LibreNMS\Tests\TestCase::class);

test('hex2 bin', function () {
    expect(hex2bin('426967203130205550'))->toEqual('Big 10 UP');
});

test('snmp hexstring', function () {
    $input = '4c 61 72 70 69 6e 67 20 34 20 55 00 0a';
    expect(StringHelpers::hexToAscii($input, ' '))->toEqual("Larping 4 U\n");
});

test('dynamic discovery get value', function () {
    $pre_cache = [
        'firstdata' => [
            0 => ['temp' => 1],
            1 => ['temp' => 2],
        ],
        'high' => [
            0 => ['high' => 3],
            1 => ['high' => 4],
        ],
        'table' => [
            0 => ['first' => 5, 'second' => 6],
            1 => ['first' => 7, 'second' => 8],
        ],
        'single' => ['something' => 9],
        'oneoff' => 10,
        'singletable' => [
            11 => ['singletable' => 'Pickle'],
        ],
        'doubletable' => [
            12 => ['doubletable' => 'Mustard'],
            13 => ['doubletable' => 'BBQ'],
        ],
    ];

    $data = ['value' => 'temp', 'oid' => 'firstdata'];
    expect(YamlDiscovery::getValueFromData('missing', 0, $data, $pre_cache))->toBeNull();
    expect(YamlDiscovery::getValueFromData('default', 0, $data, $pre_cache, 'yar'))->toBe('yar');
    expect(YamlDiscovery::getValueFromData('value', 1, $data, $pre_cache))->toBe(2);

    $data = ['oid' => 'high'];
    expect(YamlDiscovery::getValueFromData('high', 0, $data, $pre_cache))->toBe(3);

    $data = ['oid' => 'table'];
    expect(YamlDiscovery::getValueFromData('first', 0, $data, $pre_cache))->toBe(5);
    expect(YamlDiscovery::getValueFromData('first', 1, $data, $pre_cache))->toBe(7);
    expect(YamlDiscovery::getValueFromData('second', 0, $data, $pre_cache))->toBe(6);
    expect(YamlDiscovery::getValueFromData('second', 1, $data, $pre_cache))->toBe(8);

    expect(YamlDiscovery::getValueFromData('single', 0, $data, $pre_cache))->toBe(9);
    expect(YamlDiscovery::getValueFromData('oneoff', 3, $data, $pre_cache))->toBe(10);
    expect(YamlDiscovery::getValueFromData('singletable', 11, $data, $pre_cache))->toBe('Pickle');
    expect(YamlDiscovery::getValueFromData('doubletable', 13, $data, $pre_cache))->toBe('BBQ');
});

test('number cast', function () {
    expect(Number::cast(-14.3))->toBe(-14.3);
    expect(Number::cast('b -35'))->toBe(0);
    // cast must start with the number as old style php cast did
    expect(Number::cast('0 43 51'))->toBe(0);
    expect(Number::cast('14.35 a'))->toBe(14.35);
    expect(Number::cast('-43.332 a'))->toBe(-43.332);
    expect(Number::cast('-12325234523.43asdf'))->toBe(-12325234523.43);
    expect(Number::cast(1.0))->toBe(1);
    expect(Number::cast('2.000'))->toBe(2);
});

test('number as unsigned', function () {
    expect(Number::constrainInteger('42', IntegerType::Int32))->toBe(42);
    /** @phpstan-ignore-line */
    expect(Number::constrainInteger(2147483647, IntegerType::Int32))->toBe(2147483647);
    expect(Number::constrainInteger(2147483648, IntegerType::Int32))->toBe(-2147483648);
    expect(Number::constrainInteger(2147483649, IntegerType::Int32))->toBe(-2147483647);
    expect(Number::constrainInteger(4294967295, IntegerType::Int32))->toBe(-1);
    expect(Number::constrainInteger(61779, IntegerType::Int16))->toBe(-3757);
    expect(Number::constrainInteger(0, IntegerType::Uint32))->toBe(0);
    expect(Number::constrainInteger(42, IntegerType::Uint32))->toBe(42);
    expect(Number::constrainInteger(-42, IntegerType::Uint32))->toBe(4294967252);
    expect(Number::constrainInteger(-2147483646, IntegerType::Uint32))->toBe(2147483648);
    expect(Number::constrainInteger(-2147483647, IntegerType::Uint32))->toBe(2147483647);
    expect(Number::constrainInteger(-2147483648, IntegerType::Uint32))->toBe(2147483646);
    expect(Number::constrainInteger(-2147483649, IntegerType::Uint32))->toBe(2147483645);
});

test('number as unsigned value exceeds max unsigned value', function () {
    $this->expectException(\InvalidArgumentException::class);

    // Exceeds the maximum representable value for a 16-bit unsigned integer
    Number::constrainInteger(4294967296, IntegerType::Int16);
});
