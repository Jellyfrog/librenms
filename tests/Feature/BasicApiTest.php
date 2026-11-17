<?php

/**
 * BasicApiTest.php
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

use App\Facades\LibrenmsConfig;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\DevicePollingMethod;
use App\Models\Location;
use App\Models\Secret;
use App\Models\User;
use App\Models\Vminfo;
use App\Models\WirelessSensor;
use Illuminate\Support\Facades\File;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Enum\SecretType;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('list devices', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $device = Device::factory()->create();

    $res = $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('count', 1);

    $deviceData = $res->json('devices.0');
    expect($deviceData)->toBeArray();
    $this->assertArrayNotHasKey('parents', $deviceData);
    expect($deviceData['hostname'])->toBe($device->hostname);
    expect((int) $deviceData['status'])->toBe((int) $device->status);
    expect($deviceData['status'])->toBeInt();
});

test('device dates include timezone', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $device = Device::factory()->create([
        'last_polled' => '2026-10-20 06:32:01',
        'last_discovered' => '2026-10-20 06:30:00',
    ]);

    $headers = ['X-Auth-Token' => $token->plainTextToken];
    $res = $this->json('GET', "/api/v0/devices/{$device->hostname}", [], $headers);
    $res->assertStatus(200);

    // Dates must carry their zone so clients don't have to guess the server's timezone.
    expect($res->json('devices.0.last_polled'))->toBe($device->last_polled->toISOString());
    expect($res->json('devices.0.last_discovered'))->toBe($device->last_discovered->toISOString());
    expect($res->json('devices.0.last_polled'))->toEndWith('Z');
});

test('list devices with parents shape', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');

    $parent1 = Device::factory()->create(['hostname' => 'parent1.example.com']);
    $parent2 = Device::factory()->create(['hostname' => 'parent2.example.com']);
    $child = Device::factory()->create(['hostname' => 'child.example.com']);

    $child->parents()->attach([$parent1->device_id, $parent2->device_id]);

    $res = $this->json('GET', '/api/v0/devices?type=hostname&query=child', [], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('count', 1);

    $childData = $res->json('devices.0');
    $this->assertArrayNotHasKey('parents', $childData);
    $this->assertStringContainsString((string) $parent1->device_id, (string) $childData['dependency_parent_id']);
    $this->assertStringContainsString((string) $parent2->device_id, (string) $childData['dependency_parent_id']);
    $this->assertStringContainsString('parent1.example.com', (string) $childData['dependency_parent_hostname']);
    $this->assertStringContainsString('parent2.example.com', (string) $childData['dependency_parent_hostname']);
});

test('disabled user token cannot access api', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create(['enabled' => false]);
    $token = $user->createToken('test');

    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(401);
});

test('disabled token cannot access api', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $token->accessToken->expires_at = now()->subDay();
    $token->accessToken->save();

    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(401);
});

test('prefixed and unprefixed tokens both authenticate', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $device = Device::factory()->create();

    [$id, $secret] = explode('|', $token->plainTextToken, 2);

    // Test with X-Auth-Token (ID prefix)
    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');

    auth()->forgetGuards();

    // Test with X-Auth-Token (without ID prefix)
    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $secret])
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');

    auth()->forgetGuards();

    // Test with Bearer token
    $this->json('GET', '/api/v0/devices', [], ['Authorization' => "Bearer {$token->plainTextToken}"])
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');

    auth()->forgetGuards();

    // Test with query parameter api_token
    $this->json('GET', "/api/v0/devices?api_token={$token->plainTextToken}")
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');

    auth()->forgetGuards();

    // Test with HTTP Basic auth password
    $this->json('GET', '/api/v0/devices', [], ['Authorization' => 'Basic ' . base64_encode("X-Auth-Token:{$token->plainTextToken}")])
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');

    auth()->forgetGuards();

    // Test with invalid HTTP Basic auth password
    $this->json('GET', '/api/v0/devices', [], ['Authorization' => 'Basic ' . base64_encode('X-Auth-Token:invalid')])
        ->assertStatus(401);

    auth()->forgetGuards();

    // Test with invalid ID prefix
    $mismatchedId = ((int) $id) + 999;
    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => "{$mismatchedId}|{$secret}"])
        ->assertStatus(401);
});

test('migrated legacy tokens authenticate', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $legacyRawToken = bin2hex(random_bytes(16));

    // 32-char hex legacy token
    $token = $user->tokens()->create([
        'name' => 'Legacy Token',
        'token' => hash('sha256', $legacyRawToken), // as migrated
        'abilities' => ['*'],
    ]);

    // Legacy unprefixed token continues to authenticate via X-Auth-Token
    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $legacyRawToken])
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');

    // Prefixed with ID also authenticates
    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => "{$token->id}|{$legacyRawToken}"])
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');
});

test('rotate token invalidates old token and generates new prefixed token', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $oldPlainToken = $token->plainTextToken;

    $token->accessToken->delete();
    $newToken = $user->createToken('test');
    $newPlainToken = $newToken->plainTextToken;

    $this->assertNotSame($oldPlainToken, $newPlainToken);
    expect($newPlainToken)->toStartWith("{$newToken->accessToken->id}|");

    // Old token no longer valid
    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $oldPlainToken])
        ->assertStatus(401);

    // New token is valid
    $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $newPlainToken])
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok');
});

test('get device wireless sensors', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $device = Device::factory()->create();

    $rssi = WirelessSensor::factory()->for($device)->create([
        'sensor_class' => 'rssi',
        'sensor_index' => '1.1',
        'sensor_descr' => 'Subscriber 1 UL RSSI',
        'sensor_current' => -62,
    ]);
    WirelessSensor::factory()->for($device)->create([
        'sensor_class' => 'snr',
        'sensor_index' => '1.2',
        'sensor_descr' => 'Subscriber 1 UL SNR',
        'sensor_current' => 31,
    ]);
    WirelessSensor::factory()->for($device)->create([
        'sensor_class' => 'rssi',
        'sensor_index' => '1.3',
        'sensor_descr' => 'Deleted Sensor',
        'sensor_current' => 7,
        'sensor_deleted' => 1,
    ]);

    $response = $this->json('GET', "/api/v0/devices/{$device->device_id}/wireless-sensors", [], ['X-Auth-Token' => $token->plainTextToken]);

    $response->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('count', 2)
        ->assertJsonCount(2, 'wireless_sensors');

    expect($response->json('wireless_sensors.0.sensor_id'))->toBe($rssi->sensor_id);
    expect($response->json('wireless_sensors.0.sensor_class'))->toBe('rssi');
    expect($response->json('wireless_sensors.1.sensor_class'))->toBe('snr');
});

test('get device wireless sensors supports filtering and columns', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $device = Device::factory()->create();

    $rssi = WirelessSensor::factory()->for($device)->create([
        'sensor_class' => 'rssi',
        'sensor_index' => '2.1',
        'sensor_descr' => 'Subscriber 2 UL RSSI',
        'sensor_current' => -55,
    ]);
    WirelessSensor::factory()->for($device)->create([
        'sensor_class' => 'snr',
        'sensor_index' => '2.2',
        'sensor_descr' => 'Subscriber 2 UL SNR',
        'sensor_current' => 31,
    ]);

    $response = $this->json(
        'GET',
        "/api/v0/devices/{$device->device_id}/wireless-sensors?class=rssi&columns=sensor_id,sensor_class,sensor_descr,sensor_current,lastupdate",
        [],
        ['X-Auth-Token' => $token->plainTextToken]
    );

    $response->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('count', 1)
        ->assertJsonCount(1, 'wireless_sensors');

    $row = $response->json('wireless_sensors.0');

    expect($row['sensor_id'])->toBe($rssi->sensor_id);
    expect($row['sensor_class'])->toBe('rssi');
    expect($row['sensor_descr'])->toBe('Subscriber 2 UL RSSI');
    expect($row['sensor_current'])->toEqual(-55);
    expect($row)->toHaveKey('lastupdate');
    $this->assertArrayNotHasKey('sensor_type', $row);
});

test('get device wireless sensors rejects invalid class', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $device = Device::factory()->create();

    $this->json(
        'GET',
        "/api/v0/devices/{$device->device_id}/wireless-sensors?class=bogus",
        [],
        ['X-Auth-Token' => $token->plainTextToken]
    )->assertStatus(400)
        ->assertJson([
            'status' => 'error',
            'message' => "Invalid wireless sensor class 'bogus'",
        ]);
});

test('v1 only accepts bearer token', function (): void {
    \App\Facades\LibrenmsConfig::set('api.v1.enabled', true);

    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');

    // Bearer token should authenticate on v1
    $this->json('GET', '/api/v1/system', [], ['Authorization' => "Bearer {$token->plainTextToken}"])
        ->assertStatus(200);

    auth()->forgetGuards();

    // X-Auth-Token should be rejected on v1
    $this->json('GET', '/api/v1/system', [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(401);

    auth()->forgetGuards();

    // Query parameter api_token should be rejected on v1
    $this->json('GET', "/api/v1/system?api_token={$token->plainTextToken}")
        ->assertStatus(401);

    auth()->forgetGuards();

    // HTTP Basic auth password should be rejected on v1
    $this->json('GET', '/api/v1/system', [], ['Authorization' => 'Basic ' . base64_encode("X-Auth-Token:{$token->plainTextToken}")])
        ->assertStatus(401);
});

test('add rule stores procedure url', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');

    $this->json('POST', '/api/v0/rules', [
        'devices' => ['-1'],
        'name' => 'proc rule',
        'severity' => 'critical',
        'proc' => 'https://example.org/runbook',
        'builder' => alertRuleBuilder(),
    ], ['X-Auth-Token' => $token->plainTextToken])->assertStatus(200);

    expect(AlertRule::query()->where('name', 'proc rule')->value('proc'))->toBe('https://example.org/runbook');
});

test('edit rule updates procedure url', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $rule = AlertRule::factory()->create(['proc' => 'https://example.org/old']);

    $this->json('PUT', '/api/v0/rules', [
        'rule_id' => $rule->id,
        'devices' => ['-1'],
        'name' => $rule->name,
        'severity' => 'critical',
        'proc' => 'https://example.org/new',
        'builder' => alertRuleBuilder(),
    ], ['X-Auth-Token' => $token->plainTextToken])->assertStatus(200);

    expect($rule->fresh()->proc)->toBe('https://example.org/new');
});

test('edit rule leaves procedure url alone when omitted', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $rule = AlertRule::factory()->create(['proc' => 'https://example.org/keep']);

    $this->json('PUT', '/api/v0/rules', [
        'rule_id' => $rule->id,
        'devices' => ['-1'],
        'name' => $rule->name,
        'severity' => 'critical',
        'builder' => alertRuleBuilder(),
    ], ['X-Auth-Token' => $token->plainTextToken])->assertStatus(200);

    expect($rule->fresh()->proc)->toBe('https://example.org/keep');
});

test('get device', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');

    $location = Location::factory()->create(['location' => 'Server Room A', 'lat' => 10.5, 'lng' => 20.5]);
    $device = Device::factory()->create([
        'hostname' => 'test-device-1.domain.local',
        'location_id' => $location->id,
    ]);
    $vmHost = Device::factory()->create(['hostname' => 'esxi-host.domain.local']);
    Vminfo::factory()->create([
        'device_id' => $vmHost->device_id,
        'vmwVmDisplayName' => $device->hostname,
    ]);

    // Get by hostname
    $response = $this->json('GET', "/api/v0/devices/{$device->hostname}", [], ['X-Auth-Token' => $token->plainTextToken]);
    $response->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('devices.0.hostname', $device->hostname)
        ->assertJsonPath('devices.0.location', 'Server Room A')
        ->assertJsonPath('devices.0.parent_id', $vmHost->device_id);

    $getDeviceData = $response->json('devices.0');
    expect($getDeviceData)->toBeArray();
    $this->assertArrayNotHasKey('parents', $getDeviceData);
    expect((int) $getDeviceData['status'])->toBe((int) $device->status);
    expect($getDeviceData['status'])->toBeInt();

    // Get by device_id
    $this->json('GET', "/api/v0/devices/{$device->device_id}", [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('devices.0.device_id', $device->device_id);

    // Nonexistent device
    $this->json('GET', '/api/v0/devices/nonexistent-device.local', [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(404);
});

test('get device unauthorized', function (): void {
    $device = Device::factory()->create(['hostname' => 'unauthorized-device.domain.local']);

    /** @var User $normalUser */
    $normalUser = User::factory()->create();
    $normalToken = $normalUser->createToken('normal');

    $this->json('GET', "/api/v0/devices/{$device->device_id}", [], ['X-Auth-Token' => $normalToken->plainTextToken])
        ->assertStatus(403);
});

test('list devices filters', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');

    Device::factory()->create([
        'hostname' => 'alpha.domain.local',
        'os' => 'linux',
        'status' => 1,
        'ignore' => 0,
        'disabled' => 0,
    ]);
    Device::factory()->create([
        'hostname' => 'beta.domain.local',
        'os' => 'cisco',
        'status' => 0,
        'ignore' => 0,
        'disabled' => 1,
    ]);

    // Filter by os
    $res = $this->json('GET', '/api/v0/devices?type=os&query=linux', [], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('devices.0.hostname', 'alpha.domain.local');

    // Filter by disabled
    $res = $this->json('GET', '/api/v0/devices?type=disabled', [], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('devices.0.hostname', 'beta.domain.local');

    // Filter by up
    $res = $this->json('GET', '/api/v0/devices?type=up', [], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('devices.0.hostname', 'alpha.domain.local');
});

test('list devices normal user permissions', function (): void {
    $device1 = Device::factory()->create(['hostname' => 'alpha.domain.local']);
    Device::factory()->create(['hostname' => 'beta.domain.local']);

    /** @var User $normalUser */
    $normalUser = User::factory()->create();
    $normalUser->assignRole('user');
    $normalUser->devicesOwned()->attach($device1->device_id);
    \App\Facades\Permissions::invalidateCache();
    $normalToken = $normalUser->createToken('normal');

    $res = $this->json('GET', '/api/v0/devices', [], ['X-Auth-Token' => $normalToken->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('count', 1)
        ->assertJsonPath('devices.0.hostname', 'alpha.domain.local');
});

test('device response does not leak secrets', function (): void {
    $device = Device::factory()->create(['hostname' => 'secret-device.domain.local']);
    $secret = Secret::create([
        'description' => 'API test secret',
        'secret_type' => SecretType::SnmpV3,
        'data' => ['authlevel' => 'authPriv', 'authname' => 'user', 'authpass' => 'auth-secret', 'cryptopass' => 'crypto-secret'],
    ]);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::Snmp,
        'secret_id' => $secret->id,
    ]);

    /** @var User $normalUser */
    $normalUser = User::factory()->create();
    $normalUser->assignRole('user');
    $normalUser->devicesOwned()->attach($device->device_id);
    \App\Facades\Permissions::invalidateCache();
    $normalToken = $normalUser->createToken('normal');

    $this->json('GET', "/api/v0/devices/{$device->device_id}", [], ['X-Auth-Token' => $normalToken->plainTextToken])
        ->assertStatus(200)
        ->assertDontSee('auth-secret')
        ->assertDontSee('crypto-secret');
});

test('add device validation and creation', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');

    // Empty body
    $this->json('POST', '/api/v0/devices', [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(400);

    // Missing hostname
    $this->json('POST', '/api/v0/devices', ['snmp_disable' => 1], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(400);

    // Invalid hostname
    $this->json('POST', '/api/v0/devices', ['hostname' => 'invalid hostname!'], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(400);

    // Successful ping device creation
    $res = $this->json('POST', '/api/v0/devices', [
        'hostname' => 'ping-host.test.local',
        'snmp_disable' => 1,
        'force_add' => 1,
        'os' => 'ping',
        'location' => 'Lab 1',
    ], ['X-Auth-Token' => $token->plainTextToken]);

    $res->assertStatus(200)
        ->assertJsonPath('status', 'ok');

    $addedDeviceData = $res->json('devices.0');
    expect($addedDeviceData)->toBeArray();
    $this->assertArrayNotHasKey('parents', $addedDeviceData);
    expect((int) $addedDeviceData['snmp_disable'])->toBe(1);
    expect($addedDeviceData['hostname'])->toBe('ping-host.test.local');
    expect($addedDeviceData['os'])->toBe('ping');

    $this->assertDatabaseHas('devices', ['hostname' => 'ping-host.test.local', 'os' => 'ping']);
    $this->assertDatabaseMissing('device_polling_methods', ['device_id' => $addedDeviceData['device_id'], 'method_type' => 'snmp']);
    $this->assertDatabaseHas('device_polling_methods', ['device_id' => $addedDeviceData['device_id'], 'method_type' => 'icmp', 'enabled' => 1]);

    // Forced SNMP add without credentials or default credentials
    LibrenmsConfig::set('snmp.default_credentials', []);
    $this->json('POST', '/api/v0/devices', [
        'hostname' => 'snmp-host.test.local',
        'force_add' => 1,
    ], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(400)
        ->assertJsonPath('message', trans('exceptions.missing_secret', ['method' => PollingMethodType::Snmp->label()]));
    $this->assertDatabaseMissing('devices', ['hostname' => 'snmp-host.test.local']);
});

test('add device validates snmp settings', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');

    foreach (['port_association_mode' => 'ifname', 'transport' => 'sctp', 'port' => 70000] as $key => $value) {
        $this->json('POST', '/api/v0/devices', [
            'hostname' => 'invalid-settings.test.local',
            'community' => 'public',
            'force_add' => 1,
            $key => $value,
        ], ['X-Auth-Token' => $token->plainTextToken])
            ->assertStatus(422);
    }
    $this->assertDatabaseMissing('devices', ['hostname' => 'invalid-settings.test.local']);

    // the legacy id format is still accepted
    $res = $this->json('POST', '/api/v0/devices', [
        'hostname' => 'legacy-mode.test.local',
        'community' => 'public',
        'force_add' => 1,
        'port_association_mode' => 2,
        'transport' => 'tcp',
    ], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200);

    $device = Device::findOrFail($res->json('devices.0.device_id'));
    $settings = $device->pollingMethod(PollingMethodType::Snmp)->settings;
    expect($settings['port_association_mode'])->toBe('ifName');
    expect($settings['transport'])->toBe('tcp');
});

test('device without polling methods reports snmp disabled', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');
    $device = Device::factory()->create(['hostname' => 'no-methods.domain.local']);
    expect($device->pollingMethods()->doesntExist())->toBeTrue();

    $this->json('GET', "/api/v0/devices/{$device->hostname}", [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(200)
        ->assertJsonPath('devices.0.community', null)
        ->assertJsonPath('devices.0.snmpver', null)
        ->assertJsonPath('devices.0.port', null)
        ->assertJsonPath('devices.0.snmp_disable', 1);
});

test('list devices with an undecryptable secret', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');

    $broken = Device::factory()->create(['hostname' => 'a-broken.domain.local']);
    $brokenSecret = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity]);
    \DB::table('secrets')->where('id', $brokenSecret->id)->update(['data' => 'encrypted-with-another-key']);
    DevicePollingMethod::factory()->create(['device_id' => $broken->device_id, 'method_type' => PollingMethodType::Snmp, 'secret_id' => $brokenSecret->id, 'settings' => ['port' => 1161]]);

    $working = Device::factory()->create(['hostname' => 'b-working.domain.local']);
    $workingSecret = Secret::factory()->create(['secret_type' => SecretType::SnmpCommunity, 'data' => ['community' => 'public']]);
    DevicePollingMethod::factory()->create(['device_id' => $working->device_id, 'method_type' => PollingMethodType::Snmp, 'secret_id' => $workingSecret->id]);

    $res = $this->json('GET', '/api/v0/devices', ['order' => 'hostname'], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(200)
        ->assertJsonPath('count', 2);

    $devices = collect($res->json('devices'))->keyBy('hostname');
    expect($devices['a-broken.domain.local']['community'])->toBeNull();
    // credentials are left out
    expect($devices['a-broken.domain.local']['snmpver'])->toBe('v2c');
    // the version is not encrypted
    expect($devices['a-broken.domain.local']['port'])->toBe(1161);
    // settings are not encrypted
    expect($devices['b-working.domain.local']['snmpver'])->toBe('v2c');
    expect($devices['b-working.domain.local']['community'])->toBe('public');
    expect($devices['b-working.domain.local']['authlevel'])->toBeNull();
    // no v3 values for a v2c device
    expect($devices['b-working.domain.local']['authalgo'])->toBeNull();
});

test('del device', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');

    $device = Device::factory()->create(['hostname' => 'delete-me.domain.local']);

    // Nonexistent
    $this->json('DELETE', '/api/v0/devices/unknown.local', [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(404);

    // Delete by hostname
    $res = $this->json('DELETE', "/api/v0/devices/{$device->hostname}", [], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('devices.0.hostname', 'delete-me.domain.local');

    $deletedDeviceData = $res->json('devices.0');
    expect($deletedDeviceData)->toBeArray();
    $this->assertArrayNotHasKey('parents', $deletedDeviceData);

    $this->assertDatabaseMissing('devices', ['device_id' => $device->device_id]);
});

test('update device', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');

    $device = Device::factory()->create([
        'hostname' => 'update-target.domain.local',
        'sysName' => 'Original Name',
    ]);

    // Nonexistent
    $this->json('PATCH', '/api/v0/devices/unknown.local', ['field' => 'sysName', 'data' => 'New'], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(404);

    // Missing field
    $this->json('PATCH', "/api/v0/devices/{$device->device_id}", [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(400);

    // Disallowed field
    $this->json('PATCH', "/api/v0/devices/{$device->device_id}", ['field' => 'hostname', 'data' => 'new.local'], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(500);

    // Single field update
    $res = $this->json('PATCH', "/api/v0/devices/{$device->device_id}", ['field' => 'sysName', 'data' => 'Updated Switch'], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('status', 'ok');
    expect($device->fresh()->sysName)->toBe('updated switch');

    // Update location field
    $res = $this->json('PATCH', "/api/v0/devices/{$device->device_id}", ['field' => 'location', 'data' => 'Datacenter 2'], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('status', 'ok');
    expect($device->fresh()->location->location)->toBe('Datacenter 2');

    // Multi field update
    $res = $this->json('PATCH', "/api/v0/devices/{$device->device_id}", [
        'field' => ['sysName', 'purpose'],
        'data' => ['Core Switch', 'Production Core'],
    ], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('status', 'ok');
    expect($device->fresh()->sysName)->toBe('core switch');
    expect($device->fresh()->purpose)->toBe('Production Core');
});

test('get devices by group', function (): void {
    /** @var User $admin */
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test');

    $group = DeviceGroup::factory()->create(['name' => 'Edge Routers']);
    $device = Device::factory()->create(['hostname' => 'edge1.domain.local']);

    // Group not found
    $this->json('GET', '/api/v0/devicegroups/NonexistentGroup', [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(404);

    // Empty group
    $this->json('GET', "/api/v0/devicegroups/{$group->name}", [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(404);

    // Attach device to group
    $group->devices()->attach($device);

    // Get devices by group name
    $res = $this->json('GET', "/api/v0/devicegroups/{$group->name}", [], ['X-Auth-Token' => $token->plainTextToken]);
    $res->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('count', 1)
        ->assertJsonPath('devices.0.device_id', $device->device_id);

    // Get devices by group ID with full=1
    $resFull = $this->json('GET', "/api/v0/devicegroups/{$group->id}?full=1", [], ['X-Auth-Token' => $token->plainTextToken]);
    $resFull->assertStatus(200)
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('devices.0.hostname', 'edge1.domain.local');
    $this->assertArrayNotHasKey('parents', $resFull->json('devices.0'));
});

test('rename device reports success', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $device = Device::factory()->create();

    $rrd_dir = sys_get_temp_dir() . '/librenms-rename-' . uniqid();
    mkdir($rrd_dir . '/' . $device->hostname, 0777, true);
    LibrenmsConfig::set('rrd_dir', $rrd_dir);

    try {
        $this->json('PATCH', "/api/v0/devices/{$device->device_id}/rename/renamed.example.com", [], ['X-Auth-Token' => $token->plainTextToken])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Device has been renamed');

        expect($device->fresh()->hostname)->toBe('renamed.example.com');
        expect($rrd_dir . '/renamed.example.com')->toBeDirectory();
    } finally {
        File::deleteDirectory($rrd_dir);
    }
});

test('rename device without rrd directory reports success', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $device = Device::factory()->create();

    // rrd_dir exists, but the device has no RRD folder (e.g. disabled, never polled)
    $rrd_dir = sys_get_temp_dir() . '/librenms-rename-' . uniqid();
    mkdir($rrd_dir, 0777, true);
    LibrenmsConfig::set('rrd_dir', $rrd_dir);

    try {
        $this->json('PATCH', "/api/v0/devices/{$device->device_id}/rename/renamed.example.com", [], ['X-Auth-Token' => $token->plainTextToken])
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Device has been renamed');

        expect($device->fresh()->hostname)->toBe('renamed.example.com');
        $this->assertDirectoryDoesNotExist($rrd_dir . '/renamed.example.com');
    } finally {
        File::deleteDirectory($rrd_dir);
    }
});

test('rename device reports duplicate hostname', function (): void {
    /** @var User $user */
    $user = User::factory()->admin()->create();
    $token = $user->createToken('test');
    $existing = Device::factory()->create();
    $device = Device::factory()->create();

    $this->json('PATCH', "/api/v0/devices/{$device->device_id}/rename/{$existing->hostname}", [], ['X-Auth-Token' => $token->plainTextToken])
        ->assertStatus(500)
        ->assertJsonPath('status', 'error')
        ->assertJsonPath('message', "Renaming of {$device->hostname} failed because there is already a device with the hostname {$existing->hostname}");

    expect($device->fresh()->hostname)->toBe($device->hostname);
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