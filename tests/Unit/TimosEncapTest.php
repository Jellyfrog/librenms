<?php

/*
 * TimosEncapTest.php
 *
 * Tests decoding and formatting of TIMETRA-TC-MIB::TmnxEncapVal values.
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
 */

use LibreNMS\OS\Timos;

uses(\LibreNMS\Tests\TestCase::class);

test('null encap decodes', function (): void {
    expect(Timos::decodeEncapValue(0))->toBe(['outer' => 0, 'inner' => null]);
});

test('dot1q encap decodes', function (): void {
    expect(Timos::decodeEncapValue(500))->toBe(['outer' => 500, 'inner' => null]);
    expect(Timos::decodeEncapValue(1))->toBe(['outer' => 1, 'inner' => null]);
});

test('QinQ encap decodes', function (): void {
    // outer 100, inner 200: inner VLAN lives in the upper 16 bits
    $encap = (200 << 16) | 100;
    expect(Timos::decodeEncapValue($encap))->toBe(['outer' => 100, 'inner' => 200]);
});

test('string input is accepted', function (): void {
    expect(Timos::decodeEncapValue('500'))->toBe(['outer' => 500, 'inner' => null]);
});

test('dot1q formats', function (): void {
    expect(Timos::formatEncapValue(500))->toBe('500');
    expect(Timos::formatEncapValue(0))->toBe('0');
});

test('QinQ formats as outer dot inner', function (): void {
    expect(Timos::formatEncapValue((200 << 16) | 100))->toBe('100.200');
});

test('wildcard formats', function (): void {
    expect(Timos::formatEncapValue(4095))->toBe('*');
});
