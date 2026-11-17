<?php

/**
 * SyslogDeviceLookupTest.php
 *
 * Tests that a syslog sender is matched to the right device
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
 * @copyright  2026 Jacob Wilkins
 * @author     Jacob Wilkins <jacob@9.nz>
 */

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\DeviceCache;
use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Ipv6Address;
use App\Models\Port;
use LibreNMS\Syslog\Entry;
use LibreNMS\Syslog\Processor;
use LibreNMS\Util\IPv6;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    DeviceCache::flush();
});

test('sender is found by any identifier it might use', function () {
    $ipv6 = new IPv6('2001:db8::20');

    /** @var Device $device */
    $device = Device::factory()->create([
        'hostname' => 'syslog-sender.example.com',
        'sysName' => 'syslog-sender',
        'ip' => '192.0.2.10',
    ]);

    /** @var Port $port */
    $port = Port::factory()->create(['device_id' => $device->device_id]);
    Ipv4Address::factory()->create(['port_id' => $port->port_id, 'ipv4_address' => '198.51.100.20']);
    Ipv6Address::factory()->create([
        'port_id' => $port->port_id,
        'ipv6_address' => $ipv6->uncompressed(),
        'ipv6_compressed' => $ipv6->compressed(),
    ]);

    $senders = [
        'hostname' => 'syslog-sender.example.com',
        'sysName' => 'syslog-sender',
        'device address' => '192.0.2.10',
        'port ipv4 address' => '198.51.100.20',
        'port ipv6 address' => '2001:db8::20',
    ];

    foreach ($senders as $identifier => $sender) {
        expect(resolveSyslogSender($sender))->toEqual($device->device_id, "A sender identifying itself by $identifier should be matched to the device.");
    }
});

test('device address takes precedence over another devices port address', function () {
    $ipv6 = new IPv6('2001:db8::30');

    // created first, so an unordered lookup would be likely to return it
    /** @var Device $other */
    $other = Device::factory()->create();

    /** @var Port $port */
    $port = Port::factory()->create(['device_id' => $other->device_id]);
    Ipv4Address::factory()->create(['port_id' => $port->port_id, 'ipv4_address' => '192.0.2.30']);
    Ipv6Address::factory()->create([
        'port_id' => $port->port_id,
        'ipv6_address' => $ipv6->uncompressed(),
        'ipv6_compressed' => $ipv6->compressed(),
    ]);

    /** @var Device $sender */
    $sender = Device::factory()->create(['ip' => '192.0.2.30']);

    /** @var Device $sender6 */
    $sender6 = Device::factory()->create(['ip' => '2001:db8::30']);

    expect(resolveSyslogSender('192.0.2.30'))->toEqual($sender->device_id);
    expect(resolveSyslogSender('2001:db8::30'))->toEqual($sender6->device_id);
    expect(Device::findByIp('192.0.2.30')?->device_id)->toEqual($sender->device_id);
    expect(Device::findByIp('2001:db8::30')?->device_id)->toEqual($sender6->device_id);
});

test('sender name takes precedence over another devices port address', function () {
    $ipv6 = new IPv6('2001:db8::40');

    // created first, so an unordered lookup would be likely to return it
    /** @var Device $other */
    $other = Device::factory()->create();

    /** @var Port $port */
    $port = Port::factory()->create(['device_id' => $other->device_id]);
    Ipv4Address::factory()->create(['port_id' => $port->port_id, 'ipv4_address' => '192.0.2.40']);
    Ipv6Address::factory()->create([
        'port_id' => $port->port_id,
        'ipv6_address' => $ipv6->uncompressed(),
        'ipv6_compressed' => $ipv6->compressed(),
    ]);

    /** @var Device $bySysName */
    $bySysName = Device::factory()->create(['sysName' => '192.0.2.40']);

    /** @var Device $byHostname */
    $byHostname = Device::factory()->create(['hostname' => $ipv6->compressed()]);

    expect(resolveSyslogSender('192.0.2.40'))->toEqual($bySysName->device_id);
    expect(resolveSyslogSender($ipv6->compressed()))->toEqual($byHostname->device_id);
});

test('device added later is picked up once the miss expires', function () {
    $processor = new Processor;

    expect(resolveSyslogSender('192.0.2.99', $processor))->toBeNull();

    $device = Device::factory()->create(['hostname' => '192.0.2.99']);

    // the miss is held briefly, so the receiver does not query on every message
    expect(resolveSyslogSender('192.0.2.99', $processor))->toBeNull();

    $this->travel(61)->seconds();

    expect(resolveSyslogSender('192.0.2.99', $processor))->toEqual($device->device_id);
});

/**
 * Run a message through the processor without storing it, and report which device it
 * was attributed to.
 */
function resolveSyslogSender(string $host, ?Processor $processor = null): ?int
{
    $entry = ($processor ?? new Processor)->parse(new Entry(
        host: $host,
        facility: 'local7',
        priority: 'info',
        level: 'info',
        tag: '0e',
        timestamp: '2024-01-01 00:00:00',
        msg: 'lookup test',
        program: 'TEST',
    ));

    return $entry->device_id;
}