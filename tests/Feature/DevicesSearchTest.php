<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('search returns devices', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create(['hostname' => 'test-device.example.com']);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.devices', ['search' => 'test-device']));

    $response->assertStatus(200)
        ->assertJsonPath('groups.0.type', 'devices')
        ->assertJsonPath('groups.0.results.0.name', 'test-device.example.com');
});

test('search respects permissions', function () {
    $user = User::factory()->create(['enabled' => true]);
    // Normal user, no roles
    $device = Device::factory()->create(['hostname' => 'test-device.example.com']);

    // No permission given yet
    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.devices', ['search' => 'test-device']));

    $response->assertStatus(200)
        ->assertJson(['groups' => []]);
});

test('search includes location match', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create([
        'hostname' => 'other',
        'location_id' => \App\Models\Location::factory()->create(['location' => 'Sweet Potato Warehouse'])->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.devices', ['search' => 'potato']));

    $response->assertStatus(200)
        ->assertJsonPath('groups.0.type', 'devices')
        ->assertJsonPath('groups.0.results.0.name', 'other');
});

test('search includes serial match', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create([
        'hostname' => 'other',
        'serial' => 'SN12345',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.devices', ['search' => 'SN12345']));

    $response->assertStatus(200)
        ->assertJsonPath('groups.0.type', 'devices')
        ->assertJsonPath('groups.0.results.0.name', 'other');
});

test('search excludes fdb match', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create(['hostname' => 'switch']);
    $mac = '001122334455';

    // Create an FDB entry for this device
    \App\Models\PortsFdb::forceCreate([
        'device_id' => $device->device_id,
        'port_id' => \App\Models\Port::factory()->create(['device_id' => $device->device_id])->port_id,
        'mac_address' => $mac,
        'vlan' => 1,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.devices', ['search' => '00:11:22:33:44:55']));

    $response->assertStatus(200)
        ->assertJson(['groups' => []]);
});