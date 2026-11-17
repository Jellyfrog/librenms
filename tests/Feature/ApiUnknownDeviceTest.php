<?php

/**
 * ApiUnknownDeviceTest.php
 *
 * API endpoints should return 404 when a hostname or device ID is provided but
 * does not match any device, instead of silently dropping the device filter
 * (returning data of all devices) or failing with 500.
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 */

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Device;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $this->headers = ['X-Auth-Token' => $token->plainTextToken];

    $this->device = Device::factory()->create(['hostname' => 'existing.example.test']);
});

test('list endpoint returns404 for unknown device id', function (string $uri) {
    $this->json('GET', $uri . '?hostname=99999', [], $this->headers)
        ->assertStatus(404)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'Device 99999 not found');
})->with('unknownDeviceListEndpoints');

test('list endpoint returns404 for unknown hostname', function (string $uri) {
    $this->json('GET', $uri . '?hostname=unknown.example.test', [], $this->headers)
        ->assertStatus(404)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', 'Device unknown.example.test not found');
})->with('unknownDeviceListEndpoints');

/**
 * @return array<string, array{0: string}>
 */
dataset('unknownDeviceListEndpoints', function () {
    return [
        'bgp' => ['/api/v0/bgp'],
        'bgp counters' => ['/api/v0/routing/bgp/cbgp'],
        'ospf' => ['/api/v0/ospf'],
        'vrf' => ['/api/v0/routing/vrf'],
        'mpls services' => ['/api/v0/routing/mpls/services'],
        'mpls saps' => ['/api/v0/routing/mpls/saps'],
        'vlans' => ['/api/v0/resources/vlans'],
    ];
});

test('bgp without hostname still lists everything', function () {
    $this->json('GET', '/api/v0/bgp', [], $this->headers)
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');
});

test('eventlog returns404 for unknown device', function () {
    $this->json('GET', '/api/v0/logs/eventlog/99999', [], $this->headers)
        ->assertStatus(404)
        ->assertJsonPath('message', 'Device 99999 not found');

    $this->json('GET', '/api/v0/logs/eventlog', [], $this->headers)
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');
});

test('services returns404 for unknown device', function () {
    $this->json('GET', '/api/v0/services/99999', [], $this->headers)
        ->assertStatus(404)
        ->assertJsonPath('message', 'Device 99999 not found');

    $this->json('GET', '/api/v0/services', [], $this->headers)
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');
});

test('links returns404 for unknown device', function () {
    $this->json('GET', '/api/v0/devices/99999/links', [], $this->headers)
        ->assertStatus(404)
        ->assertJsonPath('message', 'Device 99999 not found');
});

test('ipsec returns400 for unknown device', function () {
    $this->json('GET', '/api/v0/routing/ipsec/data/99999', [], $this->headers)
        ->assertStatus(400)
        ->assertJsonPath('message', 'No valid hostname or device ID provided');
});

test('maintenance returns404 for unknown device', function () {
    $this->json('GET', '/api/v0/devices/99999/maintenance', [], $this->headers)
        ->assertStatus(404)
        ->assertJsonPath('message', 'Device 99999 not found');
});

test('inventory returns400 for unknown device', function () {
    $this->json('GET', '/api/v0/inventory/99999', [], $this->headers)
        ->assertStatus(400)
        ->assertJsonPath('message', 'Invalid device provided');
});

test('parents returns400 for unknown device', function () {
    $this->json('POST', '/api/v0/devices/99999/parents', ['parent_ids' => ''], $this->headers)
        ->assertStatus(400)
        ->assertJsonPath('message', 'Check your parent and device IDs');

    $this->json('DELETE', '/api/v0/devices/99999/parents', [], $this->headers)
        ->assertStatus(400)
        ->assertJsonPath('message', 'Check your device ID!');
});

test('add parents still works for existing device', function () {
    $parent = Device::factory()->create(['hostname' => 'parent.example.test']);

    $this->json('POST', "/api/v0/devices/{$this->device->device_id}/parents", ['parent_ids' => $parent->hostname], $this->headers)
        ->assertStatus(201)
        ->assertJsonPath('status', 'ok');

    expect($this->device->fresh()->parents()->whereKey($parent->device_id)->exists())->toBeTrue();

    $this->json('DELETE', "/api/v0/devices/{$this->device->device_id}/parents", ['parent_ids' => $parent->hostname], $this->headers)
        ->assertStatus(201)
        ->assertJsonPath('status', 'ok');

    expect($this->device->fresh()->parents()->whereKey($parent->device_id)->exists())->toBeFalse();
});

test('add rule returns400 for unknown device', function () {
    foreach (['99999', 'unknown.example.test'] as $bad_device) {
        $this->json('POST', '/api/v0/rules', [
            'devices' => [$bad_device],
            'name' => 'unknown device rule',
            'severity' => 'critical',
            'builder' => alertRuleBuilder(),
        ], $this->headers)->assertStatus(400)->assertJsonPath('message', "Device $bad_device does not exist");
    }
});

test('add rule still works for existing device', function () {
    $this->json('POST', '/api/v0/rules', [
        'devices' => [$this->device->device_id],
        'name' => 'existing device rule',
        'severity' => 'critical',
        'builder' => alertRuleBuilder(),
    ], $this->headers)->assertStatus(200);
});

/**
 * @return array<string, mixed>
 */
function alertRuleBuilder(): array
{
    return [
        'condition' => 'AND',
        'rules' => [[
            'id' => 'devices.hostname',
            'field' => 'devices.hostname',
            'type' => 'string',
            'input' => 'text',
            'operator' => 'equal',
            'value' => 'localhost',
        ]],
        'valid' => true,
    ];
}