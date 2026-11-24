<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Device;
use App\Models\Sensor;
use App\Models\User;
use App\Models\WirelessSensor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;

/**
 * Pages moved from bootgrid to x-data-table render the component and their endpoint answers x-data-table requests.
 */
final class DataTablePagesTest extends TestCase
{
    use RefreshDatabase;

    public static function pages(): array
    {
        return [
            'devices' => ['/devices', '/ajax/table/device', []],
            'storage' => ['/health/storage', '/ajax/table/storages', ['view' => 'detail', 'status' => '']],
            'processor' => ['/health/processor', '/ajax/table/processors', ['view' => 'detail', 'status' => '']],
            'mempool' => ['/health/mempool', '/ajax/table/mempools', ['view' => 'detail', 'status' => '']],
            'diskio' => ['/health/diskio', '/ajax/table/diskio', ['view' => 'detail']],
            'printer-supply' => ['/health/printer-supply', '/ajax/table/printer-supply', ['view' => 'detail']],
            'sensors' => ['/health/temperature', '/ajax/table/sensors', ['view' => 'detail', 'class' => 'temperature', 'status' => '']],
            'wireless' => ['/wireless/clients', '/ajax/table/wireless', ['view' => 'detail', 'class' => 'clients']],
            'ports' => ['/ports', '/ajax/table/ports', ['sort' => ['errors' => 'desc', 'secondsIfLastChange' => 'asc']]],
            'eventlog' => ['/eventlog', '/ajax/table/eventlog', []],
            'syslog' => ['/syslog', '/ajax/table/syslog', []],
            'alerts' => ['/alerts', '/ajax/table/alerts', []],
            'alert-log' => ['/alert-log', '/ajax/table/alertlog', []],
            'inventory' => ['/inventory', '/ajax/table/inventory', ['filter' => ['entPhysicalDescr' => ['contains' => 'x']]]],
            'vminfo' => ['/vminfo', '/ajax/table/vminfo', ['sort' => ['hostname' => 'asc']]],
            'outages' => ['/outages', '/ajax/table/outages', ['status' => 'all', 'from' => '-1d', 'sort' => ['device_id' => 'asc']]],
            'nac' => ['/nac', '/ajax/table/port-nac', ['historical' => 1, 'search' => 'aa', 'sort' => ['port_id' => 'desc']]],
        ];
    }

    #[DataProvider('pages')]
    public function testPageUsesDataTable(string $page, string $endpoint, array $params): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create(['enabled' => 1])->assignRole('admin');
        // the health and wireless pages only list sensor classes that exist
        $device = Device::factory()->create();
        Sensor::factory()->for($device)->create(['sensor_class' => 'temperature']);
        WirelessSensor::factory()->for($device)->create(['sensor_class' => 'clients']);

        $this->actingAs($admin)->get($page)
            ->assertOk()
            ->assertSee('dataTable(', false)
            ->assertDontSee('bootgrid(', false);

        $this->actingAs($admin)->getJson($endpoint . '?' . http_build_query([...$params, 'page' => 1, 'per_page' => 50]))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function testHealthSearchMatchesDeviceAndDescription(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create(['enabled' => 1])->assignRole('admin');
        $core = Device::factory()->create(['hostname' => 'core-router', 'display' => 'core-router']);
        $edge = Device::factory()->create(['hostname' => 'edge-router', 'display' => 'edge-router']);
        Sensor::factory()->for($core)->create(['sensor_class' => 'temperature', 'sensor_descr' => 'CPU']);
        Sensor::factory()->for($edge)->create(['sensor_class' => 'temperature', 'sensor_descr' => 'Inlet 100%']);

        $total = fn (string $search) => $this->actingAs($admin)
            ->getJson('/ajax/table/sensors?' . http_build_query(['class' => 'temperature', 'search' => $search]))
            ->assertOk()
            ->json('meta.total');

        $this->assertSame(2, $total('router'));
        $this->assertSame(1, $total('core'));
        $this->assertSame(1, $total('inlet'));
        $this->assertSame(1, $total('100%'));
        $this->assertSame(0, $total('1_0'));
    }

    public function testUsersPageListsUsersInALocalTable(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create(['enabled' => 1, 'username' => 'table-admin'])->assignRole('admin');

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('dataTable(', false)
            ->assertSee('\u0022mode\u0022:\u0022local\u0022', false)
            ->assertSee('table-admin')
            ->assertDontSee('bootgrid(', false);
    }
}
