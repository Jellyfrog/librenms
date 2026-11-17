<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\User;
use LibreNMS\Enum\PollingMethodType;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

private const array DETAILS = ['sysName' => 'manual', 'hardware' => 'Rack PDU', 'os' => ''];

test('details can be set without snmp', function () {
    $device = Device::factory()->create(['os' => 'linux', 'sysName' => 'old', 'hardware' => 'old']);
    DevicePollingMethod::factory()->create(['device_id' => $device->device_id, 'method_type' => PollingMethodType::Icmp]);
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $this->actingAs($admin)->get(route('device.edit', $device))
        ->assertOk()
        ->assertSee('name="hardware"', false);

    $this->actingAs($admin)->put(route('device.edit.update', $device), self::DETAILS)->assertRedirect();

    $device->refresh();
    expect($device->sysName)->toBe('manual');
    expect($device->hardware)->toBe('Rack PDU');
    expect($device->os)->toBe('ping');

    // no OS given
    $this->actingAs($admin)->put(route('device.edit.update', $device), ['os' => 'not-an-os'])->assertSessionHasErrors('os');
});

test('details are discovered with snmp', function () {
    $device = Device::factory()->create(['os' => 'linux', 'sysName' => 'detected', 'hardware' => 'detected']);
    DevicePollingMethod::factory()->create(['device_id' => $device->device_id, 'method_type' => PollingMethodType::Snmp, 'enabled' => false]);
    $admin = User::factory()->admin()->create(['enabled' => 1]);

    $this->actingAs($admin)->get(route('device.edit', $device))
        ->assertOk()
        ->assertDontSee('name="hardware"', false);

    $this->actingAs($admin)->put(route('device.edit.update', $device), self::DETAILS)->assertRedirect();

    $device->refresh();
    expect($device->sysName)->toBe('detected');
    expect($device->hardware)->toBe('detected');
    expect($device->os)->toBe('linux');
});