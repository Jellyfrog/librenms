<?php

/**
 * DeviceTest.php
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

use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Port;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('find by hostname', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $found = Device::findByHostname($device->hostname);
    expect($found)->not->toBeNull();
    expect($found->device_id)->toEqual($device->device_id, 'Did not find the correct device');
});

test('find by ip fail', function (): void {
    $found = Device::findByIp('this is not an ip');
    expect($found)->toBeNull();
});

test('find by ipv4 fail', function (): void {
    $found = Device::findByIp('182.43.219.43');
    expect($found)->toBeNull();
});

test('find by ipv6 fail', function (): void {
    $found = Device::findByIp('341a:234d:3429:9845:909f:fd32:1930:32dc');
    expect($found)->toBeNull();
});

test('find ip but no port', function (): void {
    $ipv4 = Ipv4Address::factory()->create();
    /** @var Ipv4Address $ipv4 */
    Port::destroy($ipv4->port_id);

    $found = Device::findByIp($ipv4->ipv4_address);
    expect($found)->toBeNull();
});

test('find by ip', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $found = Device::findByIp($device->ip);
    expect($found)->not->toBeNull();
    expect($found->device_id)->toEqual($device->device_id, 'Did not find the correct device');
});

test('find by ip hostname', function (): void {
    $ip = '192.168.234.32';
    $device = Device::factory()->create(['hostname' => $ip]);
    /** @var Device $device */
    $found = Device::findByIp($ip);
    expect($found)->not->toBeNull();
    expect($found->device_id)->toEqual($device->device_id, 'Did not find the correct device');
});

test('find by ip through port', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make();
    /** @var Port $port */
    $device->ports()->save($port);

    // test ipv4 lookup of device
    $ipv4 = Ipv4Address::factory()->make();
    /** @var Ipv4Address $ipv4 */
    $port->ipv4()->save($ipv4);

    $found = Device::findByIp($ipv4->ipv4_address);
    expect($found)->not->toBeNull();
    expect($found->device_id)->toEqual($device->device_id, 'Did not find the correct device');
});
