<?php

/**
 * NumberTest.php
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link       http://librenms.org
 *
 * @copyright  2025 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use LibreNMS\Exceptions\InsufficientDataException;
use LibreNMS\Util\Number;

uses(\LibreNMS\Tests\TestCase::class);

test('to bytes', function () {
    expect(Number::toBytes('2GiB'))->toEqual(2147483648);
    expect(Number::toBytes('2GiBytes'))->toEqual(2147483648);
    expect(Number::toBytes('2Gib'))->toEqual(2147483648);
    expect(Number::toBytes('2GB'))->toEqual(2000000000);
    expect(Number::toBytes('2 Gbps'))->toEqual(2000000000);
    // match Number::formatSI() output
    expect(Number::toBytes('2Gb'))->toEqual(2000000000);
    expect(Number::toBytes('2G'))->toEqual(2000000000);
    expect(Number::toBytes('3MiB'))->toEqual(3145728);
    expect(Number::toBytes('3M'))->toEqual(3000000);
    expect(Number::toBytes('4TiB'))->toEqual(4398046511104);
    expect(Number::toBytes('4TB'))->toEqual(4000000000000);
    expect(Number::toBytes('5PiB'))->toEqual(5629499534213120);
    expect(Number::toBytes('5PB'))->toEqual(5000000000000000);
    expect(Number::toBytes('12k'))->toEqual(12000);
    expect(Number::toBytes('12Kb'))->toEqual(12000);
    expect(Number::toBytes('12Ki'))->toEqual(12288);
    expect(Number::toBytes('12KiB'))->toEqual(12288);
    expect(Number::toBytes('12kiB'))->toEqual(12288);
    // not technically valid, but allowed
    expect(Number::toBytes('12B'))->toEqual(12);
    expect(Number::toBytes('1234'))->toEqual(1234);
    expect((int) Number::toBytes('garbage'))->toBe(0);
    // NAN cast to int is 0
    expect(Number::toBytes('1m'))->toBeNan();
    expect(Number::toBytes('1234a'))->toBeNan();
    expect(Number::toBytes('1234as'))->toBeNan();
    expect(Number::toBytes('1234asd'))->toBeNan();
    expect(Number::toBytes('fluff'))->toBeNan();
});

test('percent calculation', function () {
    expect(Number::calculatePercent(99, 100))->toEqual(99);
    expect(Number::calculatePercent(345, 1023450))->toEqual(0.03);
    expect(Number::calculatePercent(345, 1023450, 4))->toEqual(0.0337);
    expect(Number::calculatePercent(-1, 43))->toEqual(0);
    expect(Number::calculatePercent(-1, -43))->toEqual(0);
    expect(Number::calculatePercent(43, -43))->toEqual(0);
    expect(Number::calculatePercent(12639.53, 43))->toEqual(29394.26);
});

test('fill missing ratio', function () {
    expect(Number::fillMissingRatio(total: 20, used: 10))->toEqual([20, 10, 10, 50]);
    expect(Number::fillMissingRatio(total: 23, available: 14))->toEqual([23, 9, 14, 39.13]);
    expect(Number::fillMissingRatio(used: 15, available: 36))->toEqual([51, 15, 36, 29.41]);
    expect(Number::fillMissingRatio(total: 70, used_percent: 14.0))->toEqual([70, 9.8, 60.2, 14]);
    expect(Number::fillMissingRatio(used: 66, used_percent: 22.0))->toEqual([300, 66, 234, 22]);
    expect(Number::fillMissingRatio(available: 94, used_percent: 44.4, precision: 3))->toEqual([169.065, 75.065, 94, 44.4]);
    expect(Number::fillMissingRatio(used_percent: 10))->toEqual([100, 10, 90, 10.0]);

    // out of bounds percent
    expect(Number::fillMissingRatio(used_percent: 9905))->toEqual([100, 99.05, 0.95, 99.05]);

    // precision
    expect(Number::fillMissingRatio(used_percent: 9905, precision: 0))->toEqual([100, 99, 1, 99]);

    // multiplier and large numbers
    expect(Number::fillMissingRatio(total: 12341255311234234, used_percent: 12.34, precision: 0, multiplier: 1024))->toEqual([12637445438703855616, 1559460767136055808, 11077984671567799808, 12]);

    // handle strings
    expect(Number::fillMissingRatio(total: '20', used_percent: '50'))->toEqual([20, 10, 10, 50]);

    try {
        Number::fillMissingRatio();
        $this->fail('No exception thrown');
    } catch (InsufficientDataException) {
    }

    try {
        Number::fillMissingRatio(total: 1);
        $this->fail('No exception thrown');
    } catch (InsufficientDataException) {
    }

    try {
        Number::fillMissingRatio(used: 1);
        $this->fail('No exception thrown');
    } catch (InsufficientDataException) {
    }

    try {
        Number::fillMissingRatio(available: 1);
        $this->fail('No exception thrown');
    } catch (InsufficientDataException) {
    }
});

test('calculate rate', function () {
    expect(Number::calculateRate('100', '200', 1.0, 2.0))->toBe(100.0);
    expect(Number::calculateRate('4294967295', '100', 1.0, 2.0))->toBe(101.0);
    expect(Number::calculateRate('18446744073709551615', '500', 1.0, 3.0, 64))->toBe(250.5);
    expect(Number::calculateRate('1000', '1000', 1.0, 2.0))->toBe(0.0);
    expect(Number::calculateRate('100', '200', 1.0, 2.0))->toBe(100.0);
    //32bit
    expect(Number::calculateRate('5000000000', '5000000100', 1.0, 2.0))->toBe(100.0);
    //64bit
    expect(Number::calculateRate('100', '300', 1.5, 2.5))->toBe(200.0);
    // 200 difference / 1.0 second
});

test('calculate rate invalid time throws exception', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Current time must be greater than previous time');
    Number::calculateRate('100', '200', 2.0, 1.0);
});

test('calculate rate equal time throws exception', function () {
    $this->expectException(InvalidArgumentException::class);
    Number::calculateRate('100', '200', 1.0, 1.0);
});

test('calculate rate negative value throws exception', function () {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Counter values must be non-negative integers');
    Number::calculateRate('-100', '200', 1.0, 2.0);
});

test('calculate rate non numeric value throws exception', function () {
    $this->expectException(InvalidArgumentException::class);
    Number::calculateRate('abc', '200', 1.0, 2.0);
});
