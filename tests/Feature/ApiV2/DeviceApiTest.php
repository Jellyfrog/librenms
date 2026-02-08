<?php

namespace LibreNMS\Tests\Feature\ApiV2;

use App\Models\ApiToken;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

class DeviceApiTest extends DBTestCase
{
    use DatabaseTransactions;

    private function authHeaders(User $user): array
    {
        $token = ApiToken::generateToken($user);

        return ['X-Auth-Token' => $token->token_hash];
    }

    public function testUnauthenticatedRequestReturns401(): void
    {
        $this->getJson('/api/v2/devices')
            ->assertStatus(401);
    }

    public function testListDevicesAsAdmin(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        $response = $this->getJson('/api/v2/devices', $this->authHeaders($user));

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['device_id', 'hostname'],
            ]);
    }

    public function testListDevicesAsReadUser(): void
    {
        $user = User::factory()->read()->create();
        Device::factory()->create();

        $response = $this->getJson('/api/v2/devices', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testGetSingleDevice(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        $response = $this->getJson("/api/v2/devices/{$device->device_id}", $this->authHeaders($user));

        $response->assertStatus(200)
            ->assertJsonFragment(['hostname' => $device->hostname]);
    }

    public function testGetDeviceHidesSnmpCredentials(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create([
            'community' => 'secret_community',
            'authpass' => 'secret_auth',
            'cryptopass' => 'secret_crypto',
        ]);

        $response = $this->getJson("/api/v2/devices/{$device->device_id}", $this->authHeaders($user));

        $response->assertStatus(200)
            ->assertJsonMissing(['community' => 'secret_community'])
            ->assertJsonMissing(['authpass' => 'secret_auth'])
            ->assertJsonMissing(['cryptopass' => 'secret_crypto']);
    }

    public function testCreateDeviceAsAdmin(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->postJson('/api/v2/devices', [
            'hostname' => 'test-device-create.example.com',
            'ip' => '192.168.1.100',
            'type' => 'network',
            'status' => true,
        ], $this->authHeaders($user));

        $response->assertStatus(201)
            ->assertJsonFragment(['hostname' => 'test-device-create.example.com']);
    }

    public function testCreateDeviceDeniedForReadUser(): void
    {
        $user = User::factory()->read()->create();

        $response = $this->postJson('/api/v2/devices', [
            'hostname' => 'test-device-denied.example.com',
            'ip' => '192.168.1.101',
            'type' => 'network',
        ], $this->authHeaders($user));

        $response->assertStatus(403);
    }

    public function testUpdateDeviceAsAdmin(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        $response = $this->patchJson(
            "/api/v2/devices/{$device->device_id}",
            ['notes' => 'Updated via API v2'],
            $this->authHeaders($user)
        );

        $response->assertStatus(200)
            ->assertJsonFragment(['notes' => 'Updated via API v2']);
    }

    public function testDeleteDeviceAsAdmin(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        $deviceId = $device->device_id;

        $response = $this->deleteJson(
            "/api/v2/devices/{$deviceId}",
            [],
            $this->authHeaders($user)
        );

        $response->assertStatus(204);
    }

    public function testDeleteDeviceDeniedForReadUser(): void
    {
        $user = User::factory()->read()->create();
        $device = Device::factory()->create();

        $response = $this->deleteJson(
            "/api/v2/devices/{$device->device_id}",
            [],
            $this->authHeaders($user)
        );

        $response->assertStatus(403);
    }

    public function testFilterDevicesByHostname(): void
    {
        $user = User::factory()->admin()->create();
        $device1 = Device::factory()->create(['hostname' => 'router-core-01.example.com']);
        $device2 = Device::factory()->create(['hostname' => 'switch-access-01.example.com']);

        $response = $this->getJson('/api/v2/devices?hostname=router', $this->authHeaders($user));

        $response->assertStatus(200)
            ->assertJsonFragment(['hostname' => 'router-core-01.example.com']);
    }

    public function testFilterDevicesByStatus(): void
    {
        $user = User::factory()->admin()->create();
        Device::factory()->create(['status' => true]);
        Device::factory()->create(['status' => false, 'status_reason' => 'snmp']);

        $response = $this->getJson('/api/v2/devices?status=1', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testFilterDevicesByOs(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create(['os' => 'linux']);

        $response = $this->getJson('/api/v2/devices?os=linux', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testPaginationHeaders(): void
    {
        $user = User::factory()->admin()->create();
        Device::factory()->count(5)->create();

        $response = $this->getJson('/api/v2/devices?itemsPerPage=2&page=1', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testGetNonExistentDeviceReturns404(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/devices/99999', $this->authHeaders($user));

        $response->assertStatus(404);
    }
}
