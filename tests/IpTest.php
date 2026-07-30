<?php

/**
 * IpTest.php
 *
 * Tests Util\IP classes
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

use LibreNMS\Util\IP;
use LibreNMS\Util\IPv4;
use LibreNMS\Util\IPv6;

uses(LibreNMS\Tests\TestCase::class);

test('is valid', function (): void {
    expect(IP::isValid('192.168.0.1'))->toBeTrue();
    expect(IP::isValid('192.168.0.1'))->toBeTrue();
    expect(IP::isValid('2001:4860:4860::8888'))->toBeTrue();
    expect(IPv4::isValid('192.168.0.1'))->toBeTrue();
    expect(IPv6::isValid('2001:4860:4860::8888'))->toBeTrue();
    expect(IPv4::isValid('2001:4860:4860::8888'))->toBeFalse();
    expect(IPv6::isValid('192.168.0.1'))->toBeFalse();
    expect(IP::isValid('not_an_ip'))->toBeFalse();

    expect(IP::isValid('0:0:0:0:0:0:a9fe:901'))->toBeTrue();
    expect(IPv6::isValid('0:0:0:0:0:0:a9fe:901'))->toBeTrue();
    expect(IPv4::isValid('0:0:0:0:0:0:a9fe:901'))->toBeFalse();
    expect(IP::isValid('::169.254.12.3'))->toBeTrue();
    expect(IP::isValid('::ffff:169.254.12.3'))->toBeTrue();

    expect(IPv4::isValid('8.8.8.8', true))->toBeTrue();
    expect(IP::isValid('8.8.8.8', true))->toBeTrue();
    expect(IPv4::isValid('192.168.0.1', true))->toBeTrue();
    expect(IPv6::isValid('FF81::', true))->toBeTrue();
    expect(IPv4::isValid('127.0.0.1', true))->toBeFalse();
    expect(IPv6::isValid('::1', true))->toBeFalse();
    expect(IP::isValid('169.254.1.1', true))->toBeFalse();
    expect(IP::isValid('fe80::1', true))->toBeFalse();
    expect(IPv4::isValid('fe80::1', true))->toBeFalse();
    expect(IP::isValid('Falafel', true))->toBeFalse();
});

test('is valid ipv6 exclude reserved', function (): void {
    expect(IPv6::isValid('::1', true))->toBeFalse();
});

test('ipv6 is link local', function (): void {
    expect(IP::parse('169.254.1.1')->isLinkLocal())->toBeFalse();
    expect(IP::parse('fe80::1')->isLinkLocal())->toBeTrue();
    expect(IP::parse('FE80::1')->isLinkLocal())->toBeTrue();
    expect(IP::parse('fe80:8000::1')->isLinkLocal())->toBeFalse();
    expect(IP::parse('febf::1')->isLinkLocal())->toBeFalse();
});

test('ip parse', function (): void {
    expect(IP::parse('192.168.0.1'))->toEqual('192.168.0.1');
    expect(IP::parse('127.0.0.1'))->toEqual('127.0.0.1');
    expect(IP::parse('2001:db8:85a3::8a2e:370:7334'))->toEqual('2001:db8:85a3::8a2e:370:7334');
    expect(IP::parse('::1'))->toEqual('::1');

    expect(new IPv4('192.168.0.1'))->toEqual('192.168.0.1');
    expect(new IPv4('127.0.0.1'))->toEqual('127.0.0.1');
    expect(new IPv6('2001:db8:85a3::8a2e:370:7334'))->toEqual('2001:db8:85a3::8a2e:370:7334');
    expect(new IPv6('::1'))->toEqual('::1');

    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    new IPv6('192.168.0.1');
    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    new IPv6('127.0.0.1');
    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    new IPv4('2001:db8:85a3::8a2e:370:7334');
    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    new IPv4('::1');
});

test('hex to ip', function (): void {
    expect(IP::fromHexString('c0 a8 01 fe'))->toEqual('192.168.1.254');
    expect(IP::fromHexString('c0a801fe'))->toEqual('192.168.1.254');
    expect(IP::fromHexString('c0 a8 01 fe '))->toEqual('192.168.1.254');
    expect(IP::fromHexString('"c0 a8 01 fe"'))->toEqual('192.168.1.254');
    expect(IP::fromHexString('192.168.1.254'))->toEqual('192.168.1.254');
    expect(IP::fromHexString('>(}}'))->toEqual('62.40.125.125');

    // stupid ascii encoded
    expect(IP::fromHexString('2001:db8::2:1'))->toEqual('2001:db8::2:1');
    expect(IP::fromHexString('20 01 0d b8 00 00 00 00 00 00 00 00 00 02 00 01'))->toEqual('2001:db8::2:1');
    expect(IP::fromHexString('"20 01 0d b8 00 00 00 00 00 00 00 00 00 02 00 01"'))->toEqual('2001:db8::2:1');
    expect(IP::fromHexString('"20:01:0d:b8:00:00:00:00:00:00:00:00:00:02:00:01"'))->toEqual('2001:db8::2:1');
    expect(IP::fromHexString('"20.01.0d.b8.00.00.00.00.00.00.00.00.00.02.00.01"'))->toEqual('2001:db8::2:1');
    expect(IP::fromHexString('20010db8000000000000000000020001'))->toEqual('2001:db8::2:1');
    expect(IP::fromHexString('>(}}>(}}>(}}>(}}'))->toEqual('3e28:7d7d:3e28:7d7d:3e28:7d7d:3e28:7d7d');

    // stupid ascii encoded
    expect(IP::fromHexString('00000000000000000000000000000000'))->toEqual('::');

    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    IP::fromHexString('c0 a8 01 01 fe');

    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    IP::fromHexString('20 01 0d b8 00 00 00 00 00 00 00 00 00 02 00 00 00 01');
});

test('netmask2 cidr', function (): void {
    expect(IPv4::netmask2cidr('255.255.255.255'))->toBe(32);
    expect(IPv4::netmask2cidr('255.255.255.252'))->toBe(30);
    expect(IPv4::netmask2cidr('255.255.255.192'))->toBe(26);
    expect(IPv4::netmask2cidr('255.255.0.0'))->toBe(16);
});

test('ip in network', function (): void {
    expect(IP::parse('192.168.1.0')->inNetwork('192.168.1.0/24'))->toBeTrue();
    expect(IP::parse('192.168.1.32')->inNetwork('192.168.1.0/24'))->toBeTrue();
    expect(IP::parse('192.168.1.254')->inNetwork('192.168.1.0/24'))->toBeTrue();
    expect(IP::parse('192.168.1.255')->inNetwork('192.168.1.0/24'))->toBeTrue();
    expect(IP::parse('192.168.1.1')->inNetwork('192.168.1.0'))->toBeFalse();
    expect(IP::parse('10.4.3.2')->inNetwork('192.168.1.0/16'))->toBeFalse();

    expect(IP::parse('::1')->inNetwork('::/64'))->toBeTrue();
    expect(IP::parse('2001:db7:85a3::8a2e:370:7334')->inNetwork('::/0'))->toBeTrue();
    expect(IP::parse('2001:db7:85a3::8a2e:370:7334')->inNetwork('2001:db8:85a3::/64'))->toBeFalse();
    expect(IP::parse('2001:db8:85a3::8a2e:370:7334')->inNetwork('2001:db8:85a3::/64'))->toBeTrue();
    expect(IP::parse('2001:db8:85a3::8a2e:370:7334')->inNetwork('2001:db8:85a3::8a2e:370:7334/128'))->toBeTrue();
    expect(IP::parse('2001:db8:85a3::8a2e:370:7335')->inNetwork('2001:db8:85a3::8a2e:370:7334/128'))->toBeFalse();

    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    IP::parse('42')->inNetwork('192.168.1.0/4');

    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    IP::parse('192.168.1.256')->inNetwork('192.168.1.0/24');

    $this->expectException(LibreNMS\Exceptions\InvalidIpException::class);
    IP::parse('192.168.1.0')->inNetwork('192.168.1.0');
});

test('ipv6 compress', function (): void {
    expect(IP::parse('0:0:0:0:0:0:0:1'))->toEqual('::1');
    expect(IP::parse('0:0:0:0:0:0:0:1')->compressed())->toBe('::1');
    expect(IP::parse('0:0:0:0:0:0:0:0')->compressed())->toBe('::');
    expect(IP::parse('0000:0000:0000:0000:0000:0000:0000:0000')->compressed())->toBe('::');
    expect(IP::parse('2001:0db8:85a3:0000:0000:8a2e:0370:7334')->compressed())->toBe('2001:db8:85a3::8a2e:370:7334');
    expect(IP::parse('0:0:0:0:0:0:a9fe:901')->compressed())->toBe('::169.254.9.1');
    expect(IP::parse('::169.254.12.3')->compressed())->toBe('::169.254.12.3');
    expect(IP::parse('::ffff:169.254.12.3')->compressed())->toBe('::ffff:169.254.12.3');
});

test('ipv6 uncompress', function (): void {
    expect(IP::parse('::1')->uncompressed())->toBe('0000:0000:0000:0000:0000:0000:0000:0001');
    expect(IP::parse('::')->uncompressed())->toBe('0000:0000:0000:0000:0000:0000:0000:0000');
    expect(IP::parse('2001:db8:85a3::8a2e:370:7334')->uncompressed())->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
    expect(IP::parse('2001:db8:85a3:1:1:8a2e:370:7334')->uncompressed())->toBe('2001:0db8:85a3:0001:0001:8a2e:0370:7334');
    expect(IP::parse('0:0:0:0:0:0:a9fe:901')->uncompressed())->toBe('0000:0000:0000:0000:0000:0000:a9fe:0901');
    expect(IP::parse('::169.254.12.3')->uncompressed())->toBe('0000:0000:0000:0000:0000:0000:a9fe:0c03');
    expect(IP::parse('::ffff:169.254.12.3')->uncompressed())->toBe('0000:0000:0000:0000:0000:ffff:a9fe:0c03');
});

test('network from ip', function (): void {
    expect(IP::parse('192.168.1.34')->getNetwork(24))->toBe('192.168.1.0/24');
    expect(IP::parse('192.168.1.0/24')->getNetwork())->toBe('192.168.1.0/24');
    expect(IP::parse('192.168.1.255/24')->getNetwork())->toBe('192.168.1.0/24');
    expect(IP::parse('192.168.1.34')->getNetworkAddress(24))->toBe('192.168.1.0');
    expect(IP::parse('192.168.23.45')->getNetwork(20))->toBe('192.168.16.0/20');

    expect(IP::parse('2001:db8:85a3:0:341a:8a2e:0370:7334')->getNetwork(64))->toBe('2001:db8:85a3::/64');
    expect(IP::parse('2001:db8:85a3:369a::370:7334/54')->getNetwork())->toBe('2001:db8:85a3:3400::/54');
    expect(IP::parse('2001:db8:85a3:369a::370:7334/55')->getNetwork())->toBe('2001:db8:85a3:3600::/55');
    expect(IP::parse('2001:db8:85a3:341a::370:7334')->getNetwork())->toBe('2001:db8:85a3:341a::370:7334/128');
    expect(IP::parse('2001:db8:85a3:341a::370:7334/128')->getNetworkAddress())->toBe('2001:db8:85a3:341a::370:7334');
});

test('to snmp index', function (): void {
    expect(IP::parse('192.168.1.5')->toSnmpIndex())->toBe('192.168.1.5');
    expect(IP::parse('2001:878:e000:82e2:86a1:0000:0000:0000')->toSnmpIndex())->toBe('32.1.8.120.224.0.130.226.134.161.0.0.0.0.0.0');
    expect(IP::parse('::1')->toSnmpIndex())->toBe('0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.1');
    expect(IP::parse('2001:0878:0000:e000:0082:00e2:0088:00a1')->toSnmpIndex())->toBe('32.1.8.120.0.0.224.0.0.130.0.226.0.136.0.161');
});
