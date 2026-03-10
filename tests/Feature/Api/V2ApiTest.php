<?php

namespace LibreNMS\Tests\Feature\Api;

use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\Location;
use App\Models\Port;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

final class V2ApiTest extends DBTestCase
{
    use DatabaseTransactions;

    private function sanctumHeaders(User $user, array $scopes = ['read']): array
    {
        $token = $user->createToken('test', $scopes);

        return [
            'Authorization' => 'Bearer ' . $token->plainTextToken,
            'Accept' => 'application/ld+json',
        ];
    }

    // ---- Authentication ----

    public function testUnauthenticatedRequestReturns401(): void
    {
        Device::factory()->create();

        $this->getJson('/api/v2/devices')
            ->assertStatus(401);
    }

    public function testTokenWithoutReadScopeReturns403(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('test', ['invalid-scope']);

        $this->getJson('/api/v2/devices', ['Authorization' => 'Bearer ' . $token->plainTextToken])
            ->assertStatus(403);
    }

    // ---- Devices ----

    public function testListDevices(): void
    {
        $user = User::factory()->admin()->create();
        $existing = Device::count();
        Device::factory()->count(3)->create();

        $response = $this->getJson('/api/v2/devices', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'member' => [
                    '*' => ['@id', '@type'],
                ],
                'totalItems',
            ])
            ->assertJsonPath('totalItems', $existing + 3);
    }

    public function testGetDevice(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        $response = $this->getJson('/api/v2/devices/' . $device->device_id, $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('@type', 'Device')
            ->assertJsonPath('hostname', $device->hostname);
    }

    public function testDeviceNotFoundReturns404(): void
    {
        $user = User::factory()->admin()->create();

        $this->getJson('/api/v2/devices/999999', $this->sanctumHeaders($user))
            ->assertStatus(404);
    }

    public function testDeviceFilterByHostname(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create(['hostname' => 'testfilter.example.com']);
        Device::factory()->create(['hostname' => 'other.example.com']);

        $response = $this->getJson('/api/v2/devices?hostname=testfilter', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('totalItems', 1);
    }

    public function testDevicePagination(): void
    {
        $user = User::factory()->admin()->create();
        $existing = Device::count();
        Device::factory()->count(5)->create();

        $response = $this->getJson('/api/v2/devices?itemsPerPage=2', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('totalItems', $existing + 5)
            ->assertJsonCount(2, 'member');
    }

    public function testCreateDeviceReturns405(): void
    {
        $user = User::factory()->admin()->create();

        $this->postJson('/api/v2/devices', [
            'hostname' => 'new-device.example.com',
        ], $this->sanctumHeaders($user))
            ->assertStatus(405);
    }

    public function testDeleteDeviceReturns405(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        $this->deleteJson('/api/v2/devices/' . $device->device_id, [], $this->sanctumHeaders($user))
            ->assertStatus(405);
    }

    public function testPatchDeviceReturns405(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        $this->patchJson('/api/v2/devices/' . $device->device_id, [
            'hostname' => 'changed.example.com',
        ], $this->sanctumHeaders($user))
            ->assertStatus(405);
    }

    // ---- Ports ----

    public function testListPorts(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        Port::factory()->count(3)->for($device)->create();

        $response = $this->getJson('/api/v2/ports', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('totalItems', 3);
    }

    public function testGetPort(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        $port = Port::factory()->for($device)->create();

        $response = $this->getJson('/api/v2/ports/' . $port->port_id, $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('@type', 'Port')
            ->assertJsonPath('ifName', $port->ifName);
    }

    // ---- Locations ----

    public function testListLocations(): void
    {
        $user = User::factory()->admin()->create();
        Location::factory()->count(3)->create();

        $response = $this->getJson('/api/v2/locations', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('totalItems', 3);
    }

    public function testGetLocation(): void
    {
        $user = User::factory()->admin()->create();
        $location = Location::factory()->withCoordinates()->create();

        $response = $this->getJson('/api/v2/locations/' . $location->id, $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('@type', 'Location')
            ->assertJsonPath('location', $location->location);
    }

    public function testCreateLocationReturns405(): void
    {
        $user = User::factory()->admin()->create();

        $this->postJson('/api/v2/locations', [
            'location' => 'Test Location',
        ], $this->sanctumHeaders($user))
            ->assertStatus(405);
    }

    // ---- Device Groups ----

    public function testListDeviceGroups(): void
    {
        $user = User::factory()->admin()->create();
        DeviceGroup::factory()->count(2)->create();

        $response = $this->getJson('/api/v2/device_groups', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('totalItems', 2);
    }

    public function testGetDeviceGroup(): void
    {
        $user = User::factory()->admin()->create();
        $group = DeviceGroup::factory()->create();

        $response = $this->getJson('/api/v2/device_groups/' . $group->id, $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('@type', 'DeviceGroup')
            ->assertJsonPath('name', $group->name);
    }

    public function testCreateDeviceGroupReturns405(): void
    {
        $user = User::factory()->admin()->create();

        $this->postJson('/api/v2/device_groups', [
            'name' => 'Test Group',
        ], $this->sanctumHeaders($user))
            ->assertStatus(405);
    }

    // ---- Alert Rules ----

    public function testListAlertRules(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/alert_rules', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonStructure(['member', 'totalItems']);
    }

    // ---- Alerts ----

    public function testListAlerts(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/alerts', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonStructure(['member', 'totalItems']);
    }

    // ---- Sensors ----

    public function testListSensors(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        Sensor::factory()->count(2)->for($device)->create();

        $response = $this->getJson('/api/v2/sensors', $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('totalItems', 2);
    }

    public function testGetSensor(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        $sensor = Sensor::factory()->for($device)->create();

        $response = $this->getJson('/api/v2/sensors/' . $sensor->sensor_id, $this->sanctumHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('@type', 'Sensor');
    }

    // ---- JSON format ----

    public function testJsonFormatViaAcceptHeader(): void
    {
        $user = User::factory()->admin()->create();
        Device::factory()->create();

        $response = $this->getJson('/api/v2/devices', array_merge(
            $this->sanctumHeaders($user),
            ['Accept' => 'application/ld+json']
        ));

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/ld+json; charset=utf-8')
            ->assertJsonStructure(['@context', '@id', '@type']);
    }
}
