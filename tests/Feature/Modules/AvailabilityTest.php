<?php

/**
 * AvailabilityTest.php
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

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Facades\DeviceCache;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use LibreNMS\Data\Source\Icmp\Fping;
use LibreNMS\Data\Source\Icmp\FpingResponse;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Modules\Availability;
use LibreNMS\OS;
use Mockery\MockInterface;

test('polling methods are the methods that affect availability', function (): void {
    $device = Device::factory()->create();
    DevicePollingMethod::factory()->for($device)->create(['method_type' => PollingMethodType::Icmp, 'affects_availability' => true]);
    DevicePollingMethod::factory()->for($device)->create(['method_type' => PollingMethodType::Snmp, 'affects_availability' => true, 'enabled' => false]);
    DevicePollingMethod::factory()->for($device)->create(['method_type' => PollingMethodType::UnixAgent, 'affects_availability' => false]);

    expect((new Availability)->pollingMethods($device))->toBe([PollingMethodType::Icmp, PollingMethodType::Snmp]);
});

test('mtu is tested when the device answers ping', function (): void {
    $fping = mockAvailabilityFping(FpingResponse::artificialUp());
    $fping->shouldReceive('testMtu')->once()->with('192.0.2.1', 1500, null)->andReturn(false);

    $os = availabilityOs();
    (new Availability)->poll($os, Mockery::mock(DataStorageInterface::class)->shouldIgnoreMissing());

    expect($os->getDevice()->mtu_status)->toBeFalse();
});

test('mtu is not tested when the device does not answer ping', function (): void {
    $fping = mockAvailabilityFping(FpingResponse::artificialDown());
    $fping->shouldNotReceive('testMtu');

    $os = availabilityOs();
    (new Availability)->poll($os, Mockery::mock(DataStorageInterface::class)->shouldIgnoreMissing());

    expect($os->getDevice()->isDirty('mtu_status'))->toBeFalse();
});

function mockAvailabilityFping(FpingResponse $response): Fping&MockInterface
{
    LibrenmsConfig::set('mtu_options.bytes', 1500);

    $fping = Mockery::mock(Fping::class);
    $fping->shouldReceive('ping')->once()->andReturn($response);
    app()->instance(Fping::class, $fping);

    return $fping;
}

function availabilityOs(): OS
{
    $device = Device::factory()->create(['hostname' => '192.0.2.1', 'os' => 'ping', 'mtu_status' => true]);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Icmp,
        'affects_availability' => true,
    ]);
    DeviceCache::setPrimary($device->device_id);
    $deviceArray = DeviceCache::refresh($device->device_id)->toArray();

    return OS::make($deviceArray);
}
