<?php

namespace LibreNMS\Tests\Feature\ApiV2;

use App\Models\ApiToken;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

class RrdApiTest extends DBTestCase
{
    use DatabaseTransactions;

    private function authHeaders(User $user): array
    {
        $token = ApiToken::generateToken($user);

        return ['X-Auth-Token' => $token->token_hash];
    }

    public function testRrdGraphEndpointRequiresAuth(): void
    {
        $this->getJson('/api/v2/rrd/graph')
            ->assertStatus(401);
    }

    public function testRrdGraphEndpointRequiresType(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/rrd/graph', $this->authHeaders($user));

        $response->assertStatus(422);
    }

    public function testRrdDataEndpointRequiresAuth(): void
    {
        $this->getJson('/api/v2/rrd/data')
            ->assertStatus(401);
    }

    public function testRrdDataEndpointRequiresDeviceIdAndFilename(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/rrd/data', $this->authHeaders($user));

        $response->assertStatus(422);
    }

    public function testRrdFilesEndpointRequiresAuth(): void
    {
        $device = Device::factory()->create();

        $this->getJson("/api/v2/rrd/files/{$device->device_id}")
            ->assertStatus(401);
    }

    public function testRrdFilesEndpointForDevice(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        $response = $this->getJson(
            "/api/v2/rrd/files/{$device->device_id}",
            $this->authHeaders($user)
        );

        // Will return 200 with empty files array if no RRD dir exists
        $response->assertStatus(200)
            ->assertJsonStructure([
                'device_id',
                'hostname',
                'files',
            ]);
    }

    public function testRrdGraphTypesEndpoint(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        $response = $this->getJson(
            "/api/v2/rrd/graph-types/{$device->device_id}",
            $this->authHeaders($user)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'device_id',
                'hostname',
                'graphs',
                'count',
            ]);
    }

    public function testRrdDataEndpointValidatesFilename(): void
    {
        $user = User::factory()->admin()->create();
        $device = Device::factory()->create();

        // Test with path traversal attempt
        $response = $this->getJson(
            '/api/v2/rrd/data?device_id=' . $device->device_id . '&filename=../../etc/passwd',
            $this->authHeaders($user)
        );

        $response->assertStatus(422);
    }

    public function testRrdFilesEndpointForNonExistentDevice(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson(
            '/api/v2/rrd/files/99999',
            $this->authHeaders($user)
        );

        $response->assertStatus(404);
    }
}
