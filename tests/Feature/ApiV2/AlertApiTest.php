<?php

namespace LibreNMS\Tests\Feature\ApiV2;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

class AlertApiTest extends DBTestCase
{
    use DatabaseTransactions;

    private function authHeaders(User $user): array
    {
        $token = ApiToken::generateToken($user);

        return ['X-Auth-Token' => $token->token_hash];
    }

    public function testListAlertsAsAdmin(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/alerts', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testListAlertRulesAsAdmin(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/alert_rules', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testCreateAlertRuleAsAdmin(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->postJson('/api/v2/alert_rules', [
            'name' => 'Test Alert Rule v2',
            'severity' => 'critical',
            'disabled' => 0,
            'query' => 'devices.status = 0',
            'builder' => '{}',
            'extra' => '{}',
        ], $this->authHeaders($user));

        $response->assertStatus(201);
    }

    public function testCreateAlertRuleDeniedForReadUser(): void
    {
        $user = User::factory()->read()->create();

        $response = $this->postJson('/api/v2/alert_rules', [
            'name' => 'Test Alert Rule Denied',
            'severity' => 'warning',
        ], $this->authHeaders($user));

        $response->assertStatus(403);
    }

    public function testListAlertLogsAsAdmin(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->getJson('/api/v2/alert_logs', $this->authHeaders($user));

        $response->assertStatus(200);
    }

    public function testUnauthenticatedAlertAccess(): void
    {
        $this->getJson('/api/v2/alerts')
            ->assertStatus(401);
    }
}
