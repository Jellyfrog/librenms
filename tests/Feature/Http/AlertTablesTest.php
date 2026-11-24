<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Role;

final class AlertTablesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Device $device;
    private AlertRule $critical;
    private AlertRule $warning;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        $this->admin = User::factory()->create(['enabled' => 1])->assignRole('admin');
        $this->device = Device::factory()->create();
        $this->critical = AlertRule::factory()->create(['severity' => 'critical', 'name' => 'Device down']);
        $this->warning = AlertRule::factory()->create(['severity' => 'warning', 'name' => 'Port errors']);

        AlertLog::factory()->create(['device_id' => $this->device->device_id, 'rule_id' => $this->critical->id, 'state' => 1, 'time_logged' => now()->subHours(3)]);
        AlertLog::factory()->create(['device_id' => $this->device->device_id, 'rule_id' => $this->critical->id, 'state' => 1, 'time_logged' => now()->subHours(2)]);
        AlertLog::factory()->create(['device_id' => $this->device->device_id, 'rule_id' => $this->warning->id, 'state' => 1, 'time_logged' => now()->subHour()]);
        AlertLog::factory()->create(['device_id' => $this->device->device_id, 'rule_id' => $this->warning->id, 'state' => 0, 'time_logged' => now()]);
    }

    public function testAlertLog(): void
    {
        $response = $this->list('table.alertlog', ['device_id' => $this->device->device_id])
            ->assertJsonPath('meta.total', 4)
            ->assertJsonStructure(['data' => [['id', 'time_logged', 'status', 'hostname', 'alert_rule', 'severity']]]);
        // newest first
        $this->assertSame('Port errors', strip_tags($response->json('data.0.alert_rule')));

        $this->list('table.alertlog', ['state' => 0])->assertJsonPath('meta.total', 1);
        $this->list('table.alertlog', ['severity' => [3]])->assertJsonPath('meta.total', 2);
        $this->list('table.alertlog', ['search' => 'port err'])->assertJsonPath('meta.total', 2);
        $this->list('table.alertlog', ['sort' => ['alert_rule' => 'asc']])->assertJsonPath('data.0.severity', 'critical');
    }

    public function testAlertLogStats(): void
    {
        $response = $this->list('table.alertlog-stats', [])->assertJsonPath('meta.total', 2);
        $this->assertSame([2, 1], array_column($response->json('data'), 'count'));
        $this->assertSame(['Device down', 'Port errors'], array_column($response->json('data'), 'alert_rule'));

        $this->list('table.alertlog-stats', ['min_severity' => 'critical only'])->assertJsonPath('meta.total', 1);
        $this->list('table.alertlog-stats', ['min_severity' => 'warning'])->assertJsonPath('meta.total', 2);
    }

    public function testAlertsEndpoint(): void
    {
        $this->list('table.alerts', ['device_id' => $this->device->device_id])->assertJsonPath('meta.total', 0);
    }

    private function list(string $route, array $query): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->getJson(route($route, $query))->assertOk();
    }
}
