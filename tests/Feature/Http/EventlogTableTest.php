<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Device;
use App\Models\Eventlog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Enum\Severity;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Role;

final class EventlogTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        $this->admin = User::factory()->create(['enabled' => 1])->assignRole('admin');
        $this->device = Device::factory()->create();
        $other = Device::factory()->create();

        Eventlog::factory()->for($this->device)->create(['type' => 'dt-system', 'message' => 'Device rebooted', 'severity' => Severity::Warning]);
        Eventlog::factory()->for($this->device)->create(['type' => 'dt-iface', 'message' => 'Port up', 'severity' => Severity::Ok]);
        Eventlog::factory()->for($other)->create(['type' => 'dt-system', 'message' => 'Device rebooted', 'severity' => Severity::Warning]);
    }

    public function testPagesRenderDataTable(): void
    {
        $this->actingAs($this->admin)->get('/eventlog')
            ->assertOk()->assertSee('dataTable(', false)->assertDontSee('bootgrid(', false);

        $this->actingAs($this->admin)->get("/device/{$this->device->device_id}/logs/eventlog?eventtype=dt-system")
            ->assertOk()->assertSee('dataTable(', false)->assertDontSee('bootgrid(', false)
            ->assertSee('\u0022filter\u0022:{\u0022type\u0022:{\u0022eq\u0022:\u0022dt-system\u0022}}', false);
    }

    public function testFilters(): void
    {
                $this->assertTotal(2, ['filter' => ['type' => ['eq' => 'dt-system']]]);
        $this->assertTotal(1, ['filter' => ['type' => ['eq' => 'dt-system'], 'device_id' => ['eq' => $this->device->device_id]]]);
        $this->assertTotal(1, ['filter' => ['search' => ['contains' => 'port up']]]);
        $this->assertTotal(2, ['filter' => ['severity' => ['eq' => Severity::Warning->value], 'type' => ['starts_with' => 'dt-']]]);
        // device tab scope
        $this->assertTotal(2, ['device' => $this->device->device_id, 'filter' => ['type' => ['starts_with' => 'dt-']]]);
    }

    private function assertTotal(int $expected, array $params): void
    {
        $this->actingAs($this->admin)
            ->postJson('/ajax/table/eventlog', [...$params, 'page' => 1, 'per_page' => 50])
            ->assertOk()
            ->assertJsonPath('total', $expected);
    }
}
