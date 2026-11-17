<?php

/**
 * BgpTest.php
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
 * @copyright  2026 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

uses(\LibreNMS\Tests\TestCase::class);
use LibreNMS\Util\Bgp;
use PHPUnit\Framework\Attributes\DataProvider;


/**
 * @return array<string, array{index: string, trailing: int, address: string|null, zone: int|null, rest: string[]}>
 */
dataset('inetAddressProvider', function () {
    return [
        'with length' => ['index' => '1.4.192.0.2.1', 'trailing' => 0, 'address' => '192.0.2.1', 'zone' => null, 'rest' => []],
        'without length (FRR)' => ['index' => '1.192.0.2.1', 'trailing' => 0, 'address' => '192.0.2.1', 'zone' => null, 'rest' => []],
        'starting with its own length' => ['index' => '1.4.4.0.2.1', 'trailing' => 0, 'address' => '4.0.2.1', 'zone' => null, 'rest' => []],
        'without length starting with 4' => ['index' => '1.4.0.2.1', 'trailing' => 0, 'address' => '4.0.2.1', 'zone' => null, 'rest' => []],
        'ipv6' => ['index' => '2.16.32.1.13.184.0.0.0.0.0.0.0.0.0.0.0.1', 'trailing' => 0, 'address' => '2001:db8::1', 'zone' => null, 'rest' => []],
        'ipv6 link local with zone' => ['index' => '4.20.254.128.0.0.0.0.0.0.0.0.0.0.0.0.0.1.0.0.15.161', 'trailing' => 0, 'address' => 'fe80::1', 'zone' => 4001, 'rest' => []],
        'followed by afi and safi' => ['index' => '1.4.192.0.2.1.2.128', 'trailing' => 2, 'address' => '192.0.2.1', 'zone' => null, 'rest' => ['2', '128']],
        'followed by another address' => ['index' => '1.192.168.99.24.1.4.168.99.25', 'trailing' => 5, 'address' => '192.168.99.24', 'zone' => null, 'rest' => ['1', '4', '168', '99', '25']],
        'unknown type' => ['index' => '16.4.192.0.2.1', 'trailing' => 0, 'address' => null, 'zone' => null, 'rest' => []],
        'too short' => ['index' => '1.4.192.0', 'trailing' => 0, 'address' => null, 'zone' => null, 'rest' => []],
    ];
});

test('shift inet address', function (string $index, int $trailing, ?string $address, ?int $zone, array $rest) {
    $parts = explode('.', $index);
    $found = Bgp::shiftInetAddress($parts, $trailing, $foundZone);

    expect($found?->compressed())->toBe($address);
    if ($address !== null) {
        expect($foundZone)->toBe($zone);
        expect($parts)->toBe($rest);
    }
})->with('inetAddressProvider');

test('shift ip', function () {
    $parts = ['4', '0', '2', '1'];
    expect(Bgp::shiftIp($parts)?->compressed())->toBe('4.0.2.1');

    $parts = ['4', '192', '0', '2', '1', '7'];
    expect(Bgp::shiftIp($parts, 1)?->compressed())->toBe('192.0.2.1', 'The address may be prefixed with its length');
    expect($parts)->toBe(['7']);

    $parts = ['192', '0', '2'];
    expect(Bgp::shiftIp($parts))->toBeNull();
});

test('values are decoded', function () {
    expect(Bgp::state('openSent'))->toBe('opensent');
    expect(Bgp::state('closed'))->toBe('idle', 'Unknown states are idle');
    expect(Bgp::state(null))->toBe('idle');

    expect(Bgp::lastError('04 0B '))->toBe(['bgpPeerLastErrorCode' => 4, 'bgpPeerLastErrorSubCode' => 11]);
    expect(Bgp::lastError("\x06\x02"))->toBe(['bgpPeerLastErrorCode' => 6, 'bgpPeerLastErrorSubCode' => 2]);
    expect(Bgp::lastError('6:02'))->toBe(['bgpPeerLastErrorCode' => 6, 'bgpPeerLastErrorSubCode' => 2]);
    expect(Bgp::lastError(''))->toBe([]);

    expect(Bgp::integer('-94967294'))->toBe(4200000002, 'Signed 32 bit AS numbers are corrected');
    expect(Bgp::integer('none'))->toBeNull();

    expect([Bgp::afi(2), Bgp::safi('128')])->toBe(['ipv6', 'vpn']);
    expect([Bgp::afi(3), Bgp::safi(99)])->toBe(['3', '99']);
});

/**
 * @return array<string, array{0: string, 1: int}>
 */
dataset('counterProvider', function () {
    return [
        '32 bit' => ['4294967295', 4294967295],
        'wrapped 32 bit' => ['4294967301', 5],
        'above PHP_INT_MAX' => ['18446744071926153491', 2511569171],
        'PHP_INT_MAX + 1' => ['9223372036854775808', 0],
        'max 64 bit' => ['18446744073709551615', 4294967295],
        'near max 64 bit' => ['18446744073709551614', 4294967294],
        'negative 32 bit' => ['-5', 4294967291],
    ];
});

test('counters keep the low32 bits', function (string $value, int $expected) {
    expect(Bgp::counter($value))->toBe($expected);
})->with('counterProvider');

test('local as', function () {
    expect(Bgp::localAs([null, '0', '65001', '65002']))->toBe(65001);
    expect(Bgp::localAs(['23456', '65001']))->toBe(65001, 'AS_TRANS is only used when nothing else is known');
    expect(Bgp::localAs(['23456', '']))->toBe(23456);
    expect(Bgp::localAs([]))->toBeNull();
});
