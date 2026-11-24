<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;

class DeviceLogTabsTest extends TestCase
{
    use DatabaseTransactions;

    private const string PAYLOAD = '"></option></select><img src=x onerror=alert(1)>';

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
    }

    /**
     * @return array<string, array{string, array<int, string>}>
     */
    public static function logTabs(): array
    {
        return [
            'syslog' => ['device.syslog', ['program', 'priority', 'from', 'to']],
            'eventlog' => ['device.eventlog', ['eventtype']],
            'graylog' => ['device.graylog', ['stream']],
        ];
    }

    /**
     * @param  array<int, string>  $params
     */
    #[DataProvider('logTabs')]
    public function testFilterParametersAreNotRenderedRaw(string $route, array $params): void
    {
        $admin = User::factory()->create(['enabled' => 1]);
        $admin->assignRole('admin');
        $device = Device::factory()->create();

        $query = array_fill_keys($params, self::PAYLOAD);

        $this->actingAs($admin)
            ->get(route($route, ['device' => $device] + $query))
            ->assertOk()
            ->assertSee('dataTable(', false)
            ->assertDontSee('bootgrid(', false)
            ->assertDontSee(self::PAYLOAD, false);
    }

    public function testGlobalGraylogStreamIsEscaped(): void
    {
        $admin = User::factory()->create(['enabled' => 1]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/graylog?stream=' . urlencode(self::PAYLOAD))
            ->assertOk()
            ->assertSee('dataTable(', false)
            ->assertDontSee(self::PAYLOAD, false);
    }
}
