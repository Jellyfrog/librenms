<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use LibreNMS\Enum\PollingMethodType;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('can ping only includes devices with enabled icmp', function () {
    $enabled = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $enabled->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);

    $disabled = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $disabled->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => false,
    ]);

    $snmpOnly = Device::factory()->create();
    DevicePollingMethod::factory()->create([
        'device_id' => $snmpOnly->device_id,
        'method_type' => PollingMethodType::Snmp,
        'enabled' => true,
    ]);

    $deviceDisabled = Device::factory()->create(['disabled' => 1]);
    DevicePollingMethod::factory()->create([
        'device_id' => $deviceDisabled->device_id,
        'method_type' => PollingMethodType::Icmp,
        'enabled' => true,
    ]);

    $ids = Device::canPing()
        ->whereIn('devices.device_id', [$enabled->device_id, $disabled->device_id, $snmpOnly->device_id, $deviceDisabled->device_id])
        ->pluck('devices.device_id')
        ->all();

    expect($ids)->toBe([$enabled->device_id]);
});