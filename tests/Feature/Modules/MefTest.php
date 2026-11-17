<?php

/**
 * MefTest.php
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

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\DeviceCache;
use App\Models\Device;
use App\Models\Eventlog;
use App\Models\MefInfo;
use LibreNMS\Data\Source\Snmp\SnmpQueryBuilder;
use LibreNMS\Data\Source\Snmp\SnmpResponse;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Modules\Mef;
use LibreNMS\OS;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('discovery syncs evcs', function () {
    $os = os('generic');
    MefInfo::factory()->for($os->getDevice())->create(['mefID' => 9]);

    mockWalk(new SnmpResponse([
        ...evc(1, 'EVC-A', 1522),
        ...evc(2, 'EVC-B', 0),
    ]));
    (new Mef)->discover($os);

    $mefs = $os->getDevice()->mefInfo()->orderBy('mefID')->get();
    expect($mefs->pluck('mefID')->all())->toBe([1, 2]);
    expect($mefs->pluck('mefIdent')->all())->toBe(['EVC-A', 'EVC-B']);
    expect($mefs->pluck('mefMTU')->all())->toBe([1522, 0], 'Only Coriant corrects a zero MTU');
});

test('discovery keeps evcs when the device does not answer', function () {
    $os = os('generic');
    MefInfo::factory()->for($os->getDevice())->create(['mefID' => 1]);

    mockWalk(new SnmpResponse([], 'Timeout: No Response from udp:192.0.2.1:161', 1));
    (new Mef)->discover($os);

    expect($os->getDevice()->mefInfo()->pluck('mefID')->all())->toBe([1]);
});

test('polling updates evcs and logs changes', function () {
    $os = os('generic');
    $mef = MefInfo::factory()->for($os->getDevice())->create(['mefID' => 1, 'mefIdent' => 'EVC-A', 'mefMTU' => 1522, 'mefAdmState' => 'unlocked']);
    $gone = MefInfo::factory()->for($os->getDevice())->create(['mefID' => 2, 'mefMTU' => 9000]);

    mockWalk(new SnmpResponse(evc(1, 'EVC-A', 9100, 'locked')));
    (new Mef)->poll($os, Mockery::mock(DataStorageInterface::class));

    expect($mef->fresh()->mefMTU)->toBe(9100);
    expect($mef->fresh()->mefAdmState)->toBe('locked');
    expect($gone->fresh()->mefMTU)->toBe(9000, 'EVCs no longer reported are left alone');
    expect(Eventlog::where('device_id', $os->getDevice()->device_id)->where('message', 'like', 'MEF Link : %')->pluck('message')->all())->toEqualCanonicalizing(['MEF Link : EVC-A (1522) -> 9100', 'MEF Link : EVC-A (unlocked) -> locked']);
});

test('coriant corrects zero mtu', function () {
    $os = os('coriant');

    mockWalk(new SnmpResponse([
        ...evc(1, 'EVC-A', 0),
        ...evc(2, 'EVC-B', 1522),
    ]));
    (new Mef)->discover($os);

    $mefs = $os->getDevice()->mefInfo()->orderBy('mefID')->get();
    expect($mefs->pluck('mefMTU')->all())->toBe([1600, 1522]);

    mockWalk(new SnmpResponse([
        ...evc(1, 'EVC-A', 0),
        ...evc(2, 'EVC-B', 0),
    ]));
    (new Mef)->poll($os, Mockery::mock(DataStorageInterface::class));

    expect($os->getDevice()->mefInfo()->orderBy('mefID')->pluck('mefMTU')->all())->toBe([1600, 1600]);
});

function os(string $os): OS
{
    $device = Device::factory()->create(['os' => $os]);
    DeviceCache::setPrimary($device->device_id);
    $deviceArray = DeviceCache::refresh($device->device_id)->toArray();

    return OS::make($deviceArray);
}

function mockWalk(SnmpResponse $response): void
{
    $query = Mockery::mock(SnmpQueryBuilder::class);
    $query->shouldReceive('mibs', 'cache', 'enumStrings', 'abortOnFailure')->andReturnSelf();
    $query->shouldReceive('walk')->andReturn($response);
    $this->app->instance(SnmpQueryBuilder::class, $query);
}

/**
 * @return array<string, string>
 */
function evc(int $mefID, string $ident, int $mtu, string $adminState = 'unlocked'): array
{
    return [
        "MEF-UNI-EVC-MIB::mefServiceEvcCfgServiceType.$mefID" => 'epl',
        "MEF-UNI-EVC-MIB::mefServiceEvcCfgIdentifier.$mefID" => $ident,
        "MEF-UNI-EVC-MIB::mefServiceEvcCfgMtuSize.$mefID" => (string) $mtu,
        "MEF-UNI-EVC-MIB::mefServiceEvcCfgAdminState.$mefID" => $adminState,
        "MEF-UNI-EVC-MIB::mefServiceEvcCfgRowStatus.$mefID" => 'active',
    ];
}