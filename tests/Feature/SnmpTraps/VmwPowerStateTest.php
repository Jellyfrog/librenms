<?php

/**
 * VmwPowerStateTest.php
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
 *
 * Tests vmwVmPoweredOff, vmwVmPoweredOn, and vmwVmSuspended traps from VMWare ESXi hosts.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2019 KanREN, Inc
 * @author     Heath Barnhart <hbarnhart@kanren.net>
 */

use App\Models\Device;
use App\Models\Vminfo;
use LibreNMS\Enum\PowerState;

uses(\LibreNMS\Tests\Feature\SnmpTraps\SnmpTrapTestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

// replicates LibreNMS\Tests\Traits\RequiresDatabase::setUpBeforeClass (the trait collides with Pest's Testable trait)
beforeEach(function () {
    if (! getenv('DBTEST')) {
        $this->markTestSkipped('Database tests not enabled.  Set DBTEST=1 to enable.');
    }
});


test('vmw vm powered off trap', function () {
    $device = Device::factory()->create();
    /** @var Device $device */
    $guest = Vminfo::factory()->make();
    /** @var Vminfo $guest */
    $device->vminfo()->save($guest);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:28386->[10.10.10.100]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 5:18:30:26.00
SNMPv2-MIB::snmpTrapOID.0 VMWARE-VMINFO-MIB::vmwVmPoweredOff
VMWARE-VMINFO-MIB::vmwVmID.0 28 VMWARE-VMINFO-MIB::vmwVmConfigFilePath.0 /vmfs/volumes/50101bda-eaf6ac7e-7e44-d4ae5267fb9f/$guest->vmwVmDisplayName/$guest->vmwVmDisplayName.vmx
VMWARE-VMINFO-MIB::vmwVmDisplayName.28 $guest->vmwVmDisplayName
SNMP-COMMUNITY-MIB::snmpTrapAddress.0 $device->ip
SNMP-COMMUNITY-MIB::snmpTrapCommunity.0 \"public\"
SNMPv2-MIB::snmpTrapEnterprise.0 VMWARE-PRODUCTS-MIB::vmwESX",
        "Guest $guest->vmwVmDisplayName was powered off",
        'Could not handle VmwVmPoweredOffTrap',
        device: $device,
    );

    $guest->refresh();
    expect($guest->vmwVmState)->toEqual(PowerState::OFF);
});

test('vmw vm powered ontrap', function () {
    $device = Device::factory()->create();
    /** @var Device $device */
    $guest = Vminfo::factory()->make();
    /** @var Vminfo $guest */
    $device->vminfo()->save($guest);

    $this->assertTrapLogsMessage("$device->hostname
UDP: [$device->ip]:28386->[10.10.10.100]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 5:18:30:26.00
SNMPv2-MIB::snmpTrapOID.0 VMWARE-VMINFO-MIB::vmwVmPoweredOn
VMWARE-VMINFO-MIB::vmwVmID.0 28 VMWARE-VMINFO-MIB::vmwVmConfigFilePath.0 /vmfs/volumes/50101bda-eaf6ac7e-7e44-d4ae5267fb9f/$guest->vmwVmDisplayName/$guest->vmwVmDisplayName.vmx
VMWARE-VMINFO-MIB::vmwVmDisplayName.28 $guest->vmwVmDisplayName
SNMP-COMMUNITY-MIB::snmpTrapAddress.0 $device->ip
SNMP-COMMUNITY-MIB::snmpTrapCommunity.0 \"public\"
SNMPv2-MIB::snmpTrapEnterprise.0 VMWARE-PRODUCTS-MIB::vmwESX",
        "Guest $guest->vmwVmDisplayName was powered on",
        'Could not handle VmwVmPoweredOnTrap',
        device: $device,
    );

    $guest->refresh();
    expect($guest->vmwVmState)->toEqual(PowerState::ON);
});

test('vmw vm suspended trap', function () {
    $device = Device::factory()->create();
    /** @var Device $device */
    $guest = Vminfo::factory()->make();
    /** @var Vminfo $guest */
    $device->vminfo()->save($guest);

    $this->assertTrapLogsMessage("{{ hostname }}
UDP: [{{ ip }}]:28386->[10.10.10.100]:162
DISMAN-EVENT-MIB::sysUpTimeInstance 5:18:30:26.00
SNMPv2-MIB::snmpTrapOID.0 VMWARE-VMINFO-MIB::vmwVmSuspended
VMWARE-VMINFO-MIB::vmwVmID.0 28 VMWARE-VMINFO-MIB::vmwVmConfigFilePath.0 /vmfs/volumes/50101bda-eaf6ac7e-7e44-d4ae5267fb9f/$guest->vmwVmDisplayName/$guest->vmwVmDisplayName.vmx
VMWARE-VMINFO-MIB::vmwVmDisplayName.28 $guest->vmwVmDisplayName
SNMP-COMMUNITY-MIB::snmpTrapAddress.0 $device->ip
SNMP-COMMUNITY-MIB::snmpTrapCommunity.0 \"public\"
SNMPv2-MIB::snmpTrapEnterprise.0 VMWARE-PRODUCTS-MIB::vmwESX",
        "Guest $guest->vmwVmDisplayName has been suspended",
        'Could not handle VmwVmSuspendedTrap',
        device: $device,
    );

    $guest->refresh();
    expect($guest->vmwVmState)->toEqual(PowerState::SUSPENDED);
});