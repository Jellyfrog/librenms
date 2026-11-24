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

    public function testWidgetParams(): void
    {
        $this->assertTotal(2, ['search' => 'rebooted']);
        $this->assertTotal(1, ['search' => 'rebooted', 'device' => $this->device->device_id]);
        $this->assertTotal(3, ['age' => 3600]);
    }

    public function testPaginationAndSort(): void
    {
        $url = '/ajax/table/eventlog?' . http_build_query(['per_page' => 2, 'page' => 9, 'sort' => ['message' => 'asc', 'type' => 'desc']]);
        $response = $this->actingAs($this->admin)->getJson($url)->assertOk();

        // a page past the end returns the last page
        $response->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Port up');

        $this->actingAs($this->admin)->getJson('/ajax/table/eventlog?per_page=-1')
            ->assertOk()->assertJsonCount(3, 'data')->assertJsonPath('meta.last_page', 1);

        $this->actingAs($this->admin)->getJson('/ajax/table/eventlog?per_page=0')->assertUnprocessable();
    }

    public function testWidgetRenders(): void
    {
        $html = view('widgets.eventlog', [
            'id' => 1,
            'device' => $this->device->device_id,
            'device_group' => null,
            'eventtype' => 'dt-system',
            'message_filter' => 'rebooted',
            'age' => '1h',
            'hidenavigation' => 1,
        ])->render();

        $this->assertStringContainsString('dataTable(', $html);
        $this->assertStringContainsString('data-reload="false"', $html);
        $this->assertStringContainsString('\u0022search\u0022:\u0022rebooted\u0022', $html);
        $this->assertStringNotContainsString('bootgrid(', $html);
    }

    private function assertTotal(int $expected, array $params): void
    {
        $this->actingAs($this->admin)
            ->getJson('/ajax/table/eventlog?' . http_build_query([...$params, 'page' => 1, 'per_page' => 50]))
            ->assertOk()
            ->assertJsonPath('meta.total', $expected);
    }
}
