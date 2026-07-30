<?php

/**
 * CommonTrapTest.php
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
 * @copyright  2019 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Models\Device;
use App\Models\Eventlog;
use App\Models\Ipv4Address;
use App\Models\Port;
use LibreNMS\Enum\Severity;
use LibreNMS\Snmptrap\Dispatcher;
use LibreNMS\Snmptrap\Trap;

uses(LibreNMS\Tests\Feature\SnmpTraps\SnmpTrapTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

// replicates LibreNMS\Tests\Traits\RequiresDatabase::setUpBeforeClass (the trait collides with Pest's Testable trait)
beforeEach(function (): void {
    if (! getenv('DBTEST')) {
        $this->markTestSkipped('Database tests not enabled.  Set DBTEST=1 to enable.');
    }
});

test('garbage', function (): void {
    $trapText = "Garbage\n";

    $trap = new Trap($trapText);
    expect(Dispatcher::handle($trap))->toBeFalse('Found handler for trap with no snmpTrapOID');
});

test('find by ip', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make();
    /** @var Port $port */
    $device->ports()->save($port);

    // test ipv4 lookup of device
    $ipv4 = Ipv4Address::factory()->make();
    /** @var Ipv4Address $ipv4 */
    $port->ipv4()->save($ipv4);

    $trapText = "something
UDP: [$ipv4->ipv4_address]:64610->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 198:2:10:48.91\n";

    Log::partialMock()->shouldReceive('info')->once()->with('Unhandled trap snmptrap', ['device' => $device->hostname, 'oid' => null]);

    $trap = new Trap($trapText);
    expect(Dispatcher::handle($trap))->toBeFalse('Found handler for trap with no snmpTrapOID');

    // check that the device was found
    expect($trap->getDevice()->hostname)->toEqual($device->hostname);

    // check that eventlog was logged
    $eventlog = Eventlog::latest('event_id')->first();
    expect($eventlog->device_id)->toEqual($device->device_id, 'Trap eventlog device incorrect');
    expect($eventlog->message)->toEqual('', 'Trap eventlog message incorrect');
    expect($eventlog->type)->toEqual('trap', 'Trap eventlog type incorrect');
    expect($eventlog->severity)->toEqual(Severity::Info, 'Trap eventlog severity incorrect');
});

test('generic trap', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $trapText = "$device->hostname
UDP: [$device->ip]:64610->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 198:2:10:48.91
SNMPv2-MIB::snmpTrapOID.0 SNMPv2-MIB::someOid\n";

    Log::partialMock()->shouldReceive('info')->once()->with('Unhandled trap snmptrap', ['device' => $device->hostname, 'oid' => 'SNMPv2-MIB::someOid']);

    $trap = new Trap($trapText);
    expect(Dispatcher::handle($trap))->toBeFalse();

    // check that eventlog was logged
    $eventlog = Eventlog::latest('event_id')->first();
    expect($eventlog->device_id)->toEqual($device->device_id, 'Trap eventlog device incorrect');
    expect($eventlog->message)->toEqual('SNMPv2-MIB::someOid', 'Trap eventlog message incorrect');
    expect($eventlog->type)->toEqual('trap', 'Trap eventlog type incorrect');
    expect($eventlog->severity)->toEqual(Severity::Info, 'Trap eventlog severity incorrect');
});

test('authorization', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $trapText = "$device->hostname
UDP: [$device->ip]:64610->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 198:2:10:48.91
SNMPv2-MIB::snmpTrapOID.0 SNMPv2-MIB::authenticationFailure\n";

    $trap = new Trap($trapText);
    expect(Dispatcher::handle($trap))->toBeTrue();

    // check that the device was found
    expect($trap->getDevice()->hostname)->toEqual($device->hostname);

    // check that eventlog was logged
    $eventlog = Eventlog::latest('event_id')->first();
    expect($eventlog->device_id)->toEqual($device->device_id, 'Trap eventlog device incorrect');
    expect($eventlog->message)->toEqual('SNMP Trap: Authentication Failure: ' . $device->displayName(), 'Trap eventlog message incorrect');
    expect($eventlog->type)->toEqual('auth', 'Trap eventlog type incorrect');
    expect($eventlog->severity)->toEqual(Severity::Notice, 'Trap eventlog severity incorrect');
});

test('bridge new root', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $trapText = "$device->hostname
UDP: [$device->ip]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 3:4:17:32.35
SNMPv2-MIB::snmpTrapOID.0 BRIDGE-MIB::newRoot";

    $trap = new Trap($trapText);
    expect(Dispatcher::handle($trap))->toBeTrue();

    // check that the device was found
    expect($trap->getDevice()->hostname)->toEqual($device->hostname);

    // check that eventlog was logged
    $eventlog = Eventlog::latest('event_id')->first();
    expect($eventlog->device_id)->toEqual($device->device_id, 'Trap eventlog device incorrect');
    expect($eventlog->message)->toEqual('SNMP Trap: Device ' . $device->displayName() . ' was elected as new root on one of its Spanning Tree Instances', 'Trap eventlog message incorrect');
    expect($eventlog->type)->toEqual('stp', 'Trap eventlog type incorrect');
    expect($eventlog->severity)->toEqual(Severity::Notice, 'Trap eventlog severity incorrect');
});

test('bridge topology changed', function (): void {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 3:4:17:32.35
SNMPv2-MIB::snmpTrapOID.0 BRIDGE-MIB::topologyChange
TRAP,
        'SNMP Trap: Topology of Spanning Tree Instance on device {{ hostname }} was changed', // assertTrapLogsMessage sets display to hostname
        'Failed to handle BRIDGE-MIB::topologyChange',
        [Severity::Notice, 'stp'],
    );
});

test('cold start', function (): void {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:0:1:12.7
SNMPv2-MIB::snmpTrapOID.0 SNMPv2-MIB::coldStart
TRAP,
        'SNMP Trap: Device {{ hostname }} cold booted',
        'Failed to handle SNMPv2-MIB::coldStart',
        [Severity::Warning, 'reboot'],
    );
});

test('warm start', function (): void {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:0:2:12.7
SNMPv2-MIB::snmpTrapOID.0 SNMPv2-MIB::warmStart
TRAP,
        'SNMP Trap: Device {{ hostname }} warm booted',
        'Failed to handle SNMPv2-MIB::warmStart',
        [Severity::Warning, 'reboot'],
    );
});

test('entity database changed', function (): void {
    $this->assertTrapLogsMessage(<<<'TRAP'
{{ hostname }}
UDP: [{{ ip }}]:44298->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 3:4:17:32.35
SNMPv2-MIB::snmpTrapOID.0 ENTITY-MIB::entConfigChange
TRAP,
        'SNMP Trap: Configuration of Entity Database on device {{ hostname }} was changed',
        'Failed to handle ENTITY-MIB::entConfigChange',
        [Severity::Notice, 'system'],
    );
});
