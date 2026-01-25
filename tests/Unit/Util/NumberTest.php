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

test('toBytes', function () {
    expect(Number::toBytes('2GiB'))->toBe(2147483648);
    expect(Number::toBytes('2GiBytes'))->toBe(2147483648);
    expect(Number::toBytes('2Gib'))->toBe(2147483648);
    expect(Number::toBytes('2GB'))->toBe(2000000000);
    expect(Number::toBytes('2 Gbps'))->toBe(2000000000); // match Number::formatSI() output
    expect(Number::toBytes('2Gb'))->toBe(2000000000);
    expect(Number::toBytes('2G'))->toBe(2000000000);
    expect(Number::toBytes('3MiB'))->toBe(3145728);
    expect(Number::toBytes('3M'))->toBe(3000000);
    expect(Number::toBytes('4TiB'))->toBe(4398046511104);
    expect(Number::toBytes('4TB'))->toBe(4000000000000);
    expect(Number::toBytes('5PiB'))->toBe(5629499534213120);
    expect(Number::toBytes('5PB'))->toBe(5000000000000000);
    expect(Number::toBytes('12k'))->toBe(12000);
    expect(Number::toBytes('12Kb'))->toBe(12000);
    expect(Number::toBytes('12Ki'))->toBe(12288);
    expect(Number::toBytes('12KiB'))->toBe(12288);
    expect(Number::toBytes('12kiB'))->toBe(12288); // not technically valid, but allowed
    expect(Number::toBytes('12B'))->toBe(12);
    expect(Number::toBytes('1234'))->toBe(1234);
    expect((int) Number::toBytes('garbage'))->toBe(0); // NAN cast to int is 0
    expect(Number::toBytes('1m'))->toBeNan();
    expect(Number::toBytes('1234a'))->toBeNan();
    expect(Number::toBytes('1234as'))->toBeNan();
    expect(Number::toBytes('1234asd'))->toBeNan();
    expect(Number::toBytes('fluff'))->toBeNan();
});

test('percent calculation', function () {
    expect(Number::calculatePercent(99, 100))->toBe(99);
    expect(Number::calculatePercent(345, 1023450))->toBe(0.03);
    expect(Number::calculatePercent(345, 1023450, 4))->toBe(0.0337);
    expect(Number::calculatePercent(-1, 43))->toBe(0);
    expect(Number::calculatePercent(-1, -43))->toBe(0);
    expect(Number::calculatePercent(43, -43))->toBe(0);
    expect(Number::calculatePercent(12639.53, 43))->toBe(29394.26);
});

test('fillMissingRatio', function () {
    expect(Number::fillMissingRatio(total: 20, used: 10))->toBe([20, 10, 10, 50]);
    expect(Number::fillMissingRatio(total: 23, available: 14))->toBe([23, 9, 14, 39.13]);
    expect(Number::fillMissingRatio(used: 15, available: 36))->toBe([51, 15, 36, 29.41]);
    expect(Number::fillMissingRatio(total: 70, used_percent: 14.0))->toBe([70, 9.8, 60.2, 14]);
    expect(Number::fillMissingRatio(used: 66, used_percent: 22.0))->toBe([300, 66, 234, 22]);
    expect(Number::fillMissingRatio(available: 94, used_percent: 44.4, precision: 3))->toBe([169.065, 75.065, 94, 44.4]);
    expect(Number::fillMissingRatio(used_percent: 10))->toBe([100, 10, 90, 10.0]);

    // out of bounds percent
    expect(Number::fillMissingRatio(used_percent: 9905))->toBe([100, 99.05, 0.95, 99.05]);

    // precision
    expect(Number::fillMissingRatio(used_percent: 9905, precision: 0))->toBe([100, 99, 1, 99]);

    // multiplier and large numbers
    expect(Number::fillMissingRatio(total: 12341255311234234, used_percent: 12.34, precision: 0, multiplier: 1024))
        ->toBe([12637445438703855616, 1559460767136055808, 11077984671567799808, 12]);

    // handle strings
    expect(Number::fillMissingRatio(total: '20', used_percent: '50'))->toBe([20, 10, 10, 50]);
});

test('fillMissingRatio throws exception with no arguments', function () {
    Number::fillMissingRatio();
})->throws(InsufficientDataException::class);

test('fillMissingRatio throws exception with only total', function () {
    Number::fillMissingRatio(total: 1);
})->throws(InsufficientDataException::class);

test('fillMissingRatio throws exception with only used', function () {
    Number::fillMissingRatio(used: 1);
})->throws(InsufficientDataException::class);

test('fillMissingRatio throws exception with only available', function () {
    Number::fillMissingRatio(available: 1);
})->throws(InsufficientDataException::class);
