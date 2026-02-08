<?php

namespace LibreNMS\Tests\Feature\ApiV2;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

class AuthApiTest extends DBTestCase
{
    use DatabaseTransactions;

    private function authHeaders(User $user): array
    {
        $token = ApiToken::generateToken($user);

        return ['X-Auth-Token' => $token->token_hash];
    }

    public function testPingEndpoint(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/ping', $this->authHeaders($user));

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'pong',
                'api_version' => 'v2',
            ]);
    }

    public function testPingUnauthenticatedReturns401(): void
    {
        $this->getJson('/api/v2/ping')
            ->assertStatus(401);
    }

    public function testInvalidTokenReturns401(): void
    {
        $this->getJson('/api/v2/devices', ['X-Auth-Token' => 'invalid-token-12345'])
            ->assertStatus(401);
    }

    public function testBearerTokenAuth(): void
    {
        $user = User::factory()->admin()->create();
        $token = ApiToken::generateToken($user);

        // The token guard also supports standard Bearer token
        $response = $this->getJson('/api/v2/devices', [
            'Authorization' => 'Bearer ' . $token->token_hash,
        ]);

        // Should work as the token guard falls back to standard token methods
        $response->assertStatus(200);
    }

    public function testAdminCanWriteResources(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->postJson('/api/v2/locations', [
            'location' => 'Admin Test Location',
        ], $this->authHeaders($user));

        $response->assertStatus(201);
    }

    public function testReadUserCannotWriteResources(): void
    {
        $user = User::factory()->read()->create();

        $response = $this->postJson('/api/v2/locations', [
            'location' => 'Read User Test Location',
        ], $this->authHeaders($user));

        $response->assertStatus(403);
    }

    public function testReadUserCanReadResources(): void
    {
        $user = User::factory()->read()->create();

        $response = $this->getJson('/api/v2/devices', $this->authHeaders($user));

        $response->assertStatus(200);
    }
}
