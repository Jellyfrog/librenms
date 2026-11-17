<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Role;

/**
 * TableController answers both x-data-table (Laravel paginator) and bootgrid requests.
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

    public function testPaginatorRequestReturnsPaginatorJson(): void
    {
        Device::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->postJson('/ajax/table/device', [
            'page' => 2,
            'per_page' => 2,
            'search' => '',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'per_page', 'total'])
            ->assertJsonMissingPath('rows')
            ->assertJson(['current_page' => 2, 'last_page' => 2, 'per_page' => 2, 'total' => 3])
            ->assertJsonCount(1, 'data');
    }

    public function testPaginatorRequestAllRows(): void
    {
        Device::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->postJson('/ajax/table/device', ['per_page' => -1]);

        $response->assertOk()->assertJson(['total' => 3])->assertJsonCount(3, 'data');
    }

    public function testMultiSortKeepsPriorityOrder(): void
    {
        Device::factory()->create(['hardware' => 'b', 'hostname' => 'host-1', 'display' => 'host-1']);
        Device::factory()->create(['hardware' => 'a', 'hostname' => 'host-2', 'display' => 'host-2']);
        Device::factory()->create(['hardware' => 'b', 'hostname' => 'host-3', 'display' => 'host-3']);

        $response = $this->actingAs($this->admin)->postJson('/ajax/table/device', [
            'per_page' => 10,
            'sort' => ['hardware' => 'desc', 'display' => 'asc'],
        ]);

        $response->assertOk();
        $ids = array_column($response->json('data'), 'device_id');
        $expected = Device::orderBy('hardware', 'desc')->orderBy('display')->pluck('device_id')->all();
        $this->assertSame($expected, $ids);
    }

    public function testSearchAndFilter(): void
    {
        Device::factory()->create(['hostname' => 'core-router', 'display' => 'core-router', 'os' => 'ios']);
        Device::factory()->create(['hostname' => 'core-switch', 'display' => 'core-switch', 'os' => 'nxos']);
        Device::factory()->create(['hostname' => 'edge-router', 'display' => 'edge-router', 'os' => 'ios']);

        $response = $this->actingAs($this->admin)->postJson('/ajax/table/device', [
            'per_page' => 10,
            'search' => 'core',
            'filter' => ['os' => ['eq' => 'ios']],
        ]);

        $response->assertOk()->assertJson(['total' => 1]);
        $this->assertStringContainsString('core-router', $response->json('data.0.hostname'));
    }

    public function testBootgridRequestStillWorks(): void
    {
        Device::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->postJson('/ajax/table/device', [
            'current' => 1,
            'rowCount' => 2,
            'searchPhrase' => '',
        ]);

        $response->assertOk()
            ->assertJson(['current' => 1, 'rowCount' => 2, 'total' => 3])
            ->assertJsonCount(2, 'rows')
            ->assertJsonMissingPath('data');
    }
}
