<?php

/**
 * OspfIfStateChangeTest.php
 *
 * -Description-
 *
 * Unit test for the OspfIfStateChange SNMP trap handler. Will verify
 * trap is properly logged and ospf_ports.ospfIfState is updated in the
 * database.
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
 * @copyright  2020 KanREN, Inc
 * @author     Heath Barnhart <hbarnhart@kanren.net>
 */

use App\Models\Device;
use App\Models\OspfPort;
use App\Models\Port;
use LibreNMS\Enum\Severity;

uses(Illuminate\Foundation\Testing\DatabaseTransactions::class);

// replicates LibreNMS\Tests\Traits\RequiresDatabase::setUpBeforeClass (the trait collides with Pest's Testable trait)
beforeEach(function (): void {
    if (! getenv('DBTEST')) {
        $this->markTestSkipped('Database tests not enabled.  Set DBTEST=1 to enable.');
    }
});

test('ospf if down', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make(['ifAdminStatus' => 'up', 'ifOperStatus' => 'up']);
    /** @var Port $port */
    $device->ports()->save($port);

    $ospfIf = OspfPort::factory()->make(['port_id' => $port->port_id, 'ospfIfState' => 'designatedRouter']);
    /** @var OspfPort $ospfIf */
    $device->ospfPorts()->save($ospfIf);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:57602->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:6:11:31.55
SNMPv2-MIB::snmpTrapOID.0 OSPF-TRAP-MIB::ospfIfStateChange
OSPF-MIB::ospfRouterId.0 $device->ip
OSPF-MIB::ospfIfIpAddress.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfIfIpAddress
OSPF-MIB::ospfAddressLessIf.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfAddressLessIf
OSPF-MIB::ospfIfState.$ospfIf->ospfIfIpAddress.0 down
SNMPv2-MIB::snmpTrapEnterprise.0 JUNIPER-CHASSIS-DEFINES-MIB::jnxProductNameSRX240",
        "OSPF interface $port->ifName is down",
        'Could not handle ospfIfStateChange down',
        [Severity::Error],
        $device,
    );

    $ospfIf = $ospfIf->fresh();
    expect('down')->toEqual($ospfIf->ospfIfState);
});

test('ospf if dr', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make(['ifAdminStatus' => 'up', 'ifOperStatus' => 'up']);
    /** @var Port $port */
    $device->ports()->save($port);

    $ospfIf = OspfPort::factory()->make(['port_id' => $port->port_id, 'ospfIfState' => 'down']);
    /** @var OspfPort $ospfIf */
    $device->ospfPorts()->save($ospfIf);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:57602->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:6:11:31.55
SNMPv2-MIB::snmpTrapOID.0 OSPF-TRAP-MIB::ospfIfStateChange
OSPF-MIB::ospfRouterId.0 $device->ip
OSPF-MIB::ospfIfIpAddress.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfIfIpAddress
OSPF-MIB::ospfAddressLessIf.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfAddressLessIf
OSPF-MIB::ospfIfState.$ospfIf->ospfIfIpAddress.0 designatedRouter
SNMPv2-MIB::snmpTrapEnterprise.0 JUNIPER-CHASSIS-DEFINES-MIB::jnxProductNameSRX240
",
        "OSPF interface $port->ifName is designatedRouter",
        'Could not handle ospfIfStateChange designatedRouter',
        [Severity::Ok],
        $device,
    );

    $ospfIf = $ospfIf->fresh();
    expect('designatedRouter')->toEqual($ospfIf->ospfIfState);
});

test('ospf if bdr', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make(['ifAdminStatus' => 'up', 'ifOperStatus' => 'up']);
    /** @var Port $port */
    $device->ports()->save($port);

    $ospfIf = OspfPort::factory()->make(['port_id' => $port->port_id, 'ospfIfState' => 'down']);
    /** @var OspfPort $ospfIf */
    $device->ospfPorts()->save($ospfIf);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:57602->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:6:11:31.55
SNMPv2-MIB::snmpTrapOID.0 OSPF-TRAP-MIB::ospfIfStateChange
OSPF-MIB::ospfRouterId.0 $device->ip
OSPF-MIB::ospfIfIpAddress.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfIfIpAddress
OSPF-MIB::ospfAddressLessIf.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfAddressLessIf
OSPF-MIB::ospfIfState.$ospfIf->ospfIfIpAddress.0 backupDesignatedRouter
SNMPv2-MIB::snmpTrapEnterprise.0 JUNIPER-CHASSIS-DEFINES-MIB::jnxProductNameSRX240",
        "OSPF interface $port->ifName is backupDesignatedRouter",
        'Could not handle ospfIfStateChange backupDesignatedRouter',
        [Severity::Ok],
        $device,
    );

    $ospfIf = $ospfIf->fresh();
    expect('backupDesignatedRouter')->toEqual($ospfIf->ospfIfState);
});

test('ospf if odr', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make(['ifAdminStatus' => 'up', 'ifOperStatus' => 'up']);
    /** @var Port $port */
    $device->ports()->save($port);

    $ospfIf = OspfPort::factory()->make(['port_id' => $port->port_id, 'ospfIfState' => 'down']);
    /** @var OspfPort $ospfIf */
    $device->ospfPorts()->save($ospfIf);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:57602->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:6:11:31.55
SNMPv2-MIB::snmpTrapOID.0 OSPF-TRAP-MIB::ospfIfStateChange
OSPF-MIB::ospfRouterId.0 $device->ip
OSPF-MIB::ospfIfIpAddress.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfIfIpAddress
OSPF-MIB::ospfAddressLessIf.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfAddressLessIf
OSPF-MIB::ospfIfState.$ospfIf->ospfIfIpAddress.0 otherDesignatedRouter
SNMPv2-MIB::snmpTrapEnterprise.0 JUNIPER-CHASSIS-DEFINES-MIB::jnxProductNameSRX240",
        "OSPF interface $port->ifName is otherDesignatedRouter",
        'Could not handle ospfIfStateChange otherDesignatedRouter',
        [Severity::Ok],
        $device,
    );

    $ospfIf = $ospfIf->fresh();
    expect('otherDesignatedRouter')->toEqual($ospfIf->ospfIfState);
});

test('ospf if ptp', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make(['ifAdminStatus' => 'up', 'ifOperStatus' => 'up']);
    /** @var Port $port */
    $device->ports()->save($port);

    $ospfIf = OspfPort::factory()->make(['port_id' => $port->port_id, 'ospfIfState' => 'down']);
    /** @var OspfPort $ospfIf */
    $device->ospfPorts()->save($ospfIf);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:57602->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:6:11:31.55
SNMPv2-MIB::snmpTrapOID.0 OSPF-TRAP-MIB::ospfIfStateChange
OSPF-MIB::ospfRouterId.0 $device->ip
OSPF-MIB::ospfIfIpAddress.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfIfIpAddress
OSPF-MIB::ospfAddressLessIf.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfAddressLessIf
OSPF-MIB::ospfIfState.$ospfIf->ospfIfIpAddress.0 pointToPoint
SNMPv2-MIB::snmpTrapEnterprise.0 JUNIPER-CHASSIS-DEFINES-MIB::jnxProductNameSRX240",
        "OSPF interface $port->ifName is pointToPoint",
        'Could not handle ospfIfStateChange pointToPoint',
        [Severity::Ok],
        $device,
    );

    $ospfIf = $ospfIf->fresh();
    expect('pointToPoint')->toEqual($ospfIf->ospfIfState);
});

test('ospf if wait', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make(['ifAdminStatus' => 'up', 'ifOperStatus' => 'up']);
    /** @var Port $port */
    $device->ports()->save($port);

    $ospfIf = OspfPort::factory()->make(['port_id' => $port->port_id, 'ospfIfState' => 'designatedRouter']);
    /** @var OspfPort $ospfIf */
    $device->ospfPorts()->save($ospfIf);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:57602->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:6:11:31.55
SNMPv2-MIB::snmpTrapOID.0 OSPF-TRAP-MIB::ospfIfStateChange
OSPF-MIB::ospfRouterId.0 $device->ip
OSPF-MIB::ospfIfIpAddress.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfIfIpAddress
OSPF-MIB::ospfAddressLessIf.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfAddressLessIf
OSPF-MIB::ospfIfState.$ospfIf->ospfIfIpAddress.0 waiting
SNMPv2-MIB::snmpTrapEnterprise.0 JUNIPER-CHASSIS-DEFINES-MIB::jnxProductNameSRX240",
        "OSPF interface $port->ifName is waiting",
        'Could not handle ospfIfStateChange waiting',
        [Severity::Warning],
        $device,
    );

    $ospfIf = $ospfIf->fresh();
    expect('waiting')->toEqual($ospfIf->ospfIfState);
});

test('ospf if loop', function (): void {
    $device = Device::factory()->create();
    /** @var Device $device */
    $port = Port::factory()->make(['ifAdminStatus' => 'up', 'ifOperStatus' => 'up']);
    /** @var Port $port */
    $device->ports()->save($port);

    $ospfIf = OspfPort::factory()->make(['port_id' => $port->port_id, 'ospfIfState' => 'designatedRouter']);
    /** @var OspfPort $ospfIf */
    $device->ospfPorts()->save($ospfIf);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:57602->[192.168.5.5]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 0:6:11:31.55
SNMPv2-MIB::snmpTrapOID.0 OSPF-TRAP-MIB::ospfIfStateChange
OSPF-MIB::ospfRouterId.0 $device->ip
OSPF-MIB::ospfIfIpAddress.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfIfIpAddress
OSPF-MIB::ospfAddressLessIf.$ospfIf->ospfIfIpAddress.0 $ospfIf->ospfAddressLessIf
OSPF-MIB::ospfIfState.$ospfIf->ospfIfIpAddress.0 loopback
SNMPv2-MIB::snmpTrapEnterprise.0 JUNIPER-CHASSIS-DEFINES-MIB::jnxProductNameSRX240",
        "OSPF interface $port->ifName is loopback",
        'Could not handle ospfIfStateChange loopback',
        [Severity::Warning],
        $device,
    );

    $ospfIf = $ospfIf->fresh();
    expect('loopback')->toEqual($ospfIf->ospfIfState);
});
