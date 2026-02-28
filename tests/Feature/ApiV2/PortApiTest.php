<?php

namespace LibreNMS\Tests\Feature\ApiV2;

use App\Models\ApiToken;
use App\Models\Device;
use App\Models\Port;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

class PortApiTest extends DBTestCase
{
    use DatabaseTransactions;

    private function authHeaders(User $user): array
    {
        $token = ApiToken::generateToken($user);

        return ['X-Auth-Token' => $token->token_hash];
    }

    public function testListPortsAsAdmin(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        $port = Port::factory()->create(['device_id' => $device->device_id]);

        $response = $this->getJson('/api/v2/ports', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testGetSinglePort(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        $port = Port::factory()->create(['device_id' => $device->device_id]);

        $response = $this->getJson("/api/v2/ports/{$port->port_id}", $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testFilterPortsByDeviceId(): void
    {
        $user = User::factory()->admin()->create();
        $device1 = Device::factory()->create();
        $device2 = Device::factory()->create();
        Port::factory()->create(['device_id' => $device1->device_id]);
        Port::factory()->create(['device_id' => $device2->device_id]);

        $response = $this->getJson("/api/v2/ports?device_id={$device1->device_id}", $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testFilterPortsByIfName(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        Port::factory()->create([
            'device_id' => $device->device_id,
            'ifName' => 'GigabitEthernet0/1',
        ]);

        $response = $this->getJson('/api/v2/ports?ifName=Gigabit', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testUpdatePortAsAdmin(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();
        $port = Port::factory()->create(['device_id' => $device->device_id]);

        $response = $this->patchJson(
            "/api/v2/ports/{$port->port_id}",
            ['ifAlias' => 'Updated alias via API v2'],
            $this->authHeaders($user)
        );

        $response->assertStatus(200);
    }

    public function testUnauthenticatedPortAccess(): void
    {
        $this->getJson('/api/v2/ports')
            ->assertStatus(401);
    }
}
