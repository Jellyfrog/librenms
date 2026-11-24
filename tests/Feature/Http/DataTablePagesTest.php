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
 * Pages moved from bootgrid to x-data-table render the component and their endpoint answers paginator requests.
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
        ];
    }

    /**
     * Pages with a CSV export button => export route
     *
     * @return array<string, string>
     */
    private static function exports(): array
    {
        return [
            '/devices' => 'table.device.export',
            '/health/storage' => 'table.storages.export',
            '/health/processor' => 'table.processors.export',
            '/health/mempool' => 'table.mempools.export',
            '/health/temperature' => 'table.sensors.export',
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

        if ($export = self::exports()[$page] ?? null) {
            $this->actingAs($admin)->get($page)->assertSee('exportUrl', false)->assertSee(str_replace('/', '\\\\\\/', route($export)), false);
            $this->actingAs($admin)->get(route($export, [...$params, 'current' => 1, 'rowCount' => 10, 'sort' => ['device_hostname' => 'asc']]))->assertOk();
        }

        $this->actingAs($admin)->postJson($endpoint, [...$params, 'page' => 1, 'per_page' => 50])
            ->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'per_page', 'total']);
    }
}
