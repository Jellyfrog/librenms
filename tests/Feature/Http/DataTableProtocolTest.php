<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Role;

/**
 * The x-data-table request contract (App\Http\Requests\DataTableRequest), tested on the device list endpoint
 */
final class DataTableProtocolTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        $this->admin = User::factory()->create(['enabled' => 1])->assignRole('admin');
    }

    public function testReturnsResourceCollection(): void
    {
        Device::factory()->count(3)->create();

        $this->list(['page' => 2, 'per_page' => 2])
            ->assertOk()
            ->assertJsonStructure(['data' => [['device_id', 'status', 'hostname', 'actions']], 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(1, 'data');
    }

    public function testAllRowsAndPagePastTheEnd(): void
    {
        Device::factory()->count(3)->create();

        $this->list(['per_page' => -1])->assertOk()->assertJsonPath('meta.total', 3)->assertJsonCount(3, 'data');
        $this->list(['per_page' => 2, 'page' => 7])->assertOk()->assertJsonPath('meta.current_page', 2)->assertJsonCount(1, 'data');
        $this->list(['per_page' => 0])->assertUnprocessable();
        $this->list(['sort' => ['display' => 'sideways']])->assertUnprocessable();
    }

    public function testMultiSortKeepsPriorityOrder(): void
    {
        Device::factory()->create(['hardware' => 'b', 'hostname' => 'host-1', 'display' => 'host-1']);
        Device::factory()->create(['hardware' => 'a', 'hostname' => 'host-2', 'display' => 'host-2']);
        Device::factory()->create(['hardware' => 'b', 'hostname' => 'host-3', 'display' => 'host-3']);

        $response = $this->list(['per_page' => 10, 'sort' => ['hardware' => 'desc', 'display' => 'asc', 'bogus' => 'asc']])->assertOk();

        $expected = Device::orderBy('hardware', 'desc')->orderBy('display')->pluck('device_id')->all();
        $this->assertSame($expected, array_column($response->json('data'), 'device_id'));
    }

    public function testSortByLocation(): void
    {
        Device::factory()->count(2)->create();

        $this->list(['sort' => ['location' => 'desc']])->assertOk()->assertJsonPath('meta.total', 2);
    }

    public function testFilter(): void
    {
        Device::factory()->create(['hostname' => 'core-router', 'display' => 'core-router', 'os' => 'ios']);
        Device::factory()->create(['hostname' => 'core-switch', 'display' => 'core-switch', 'os' => 'nxos']);
        Device::factory()->create(['hostname' => 'edge-router', 'display' => 'edge-router', 'os' => 'ios']);

        $response = $this->list(['filter' => ['os' => ['eq' => 'ios'], 'search' => ['contains' => 'core']]])
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
        $this->assertStringContainsString('core-router', $response->json('data.0.hostname'));
    }

    public function testExport(): void
    {
        Device::factory()->create(['hostname' => 'core-router', 'display' => 'core-router']);

        $response = $this->actingAs($this->admin)->get('/ajax/table/device/export');
        $response->assertOk();
        $this->assertStringContainsString('core-router', $response->streamedContent());
    }

    private function list(array $query): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->getJson('/ajax/table/device?' . http_build_query($query));
    }
}
