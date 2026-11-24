<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Device;
use App\Models\Syslog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Enum\SyslogSeverity;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Role;

final class SyslogTableTest extends TestCase
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

        Syslog::factory()->for($this->device)->create(['program' => 'sshd', 'priority' => 'err', 'level' => SyslogSeverity::ERROR, 'msg' => 'Login failed', 'timestamp' => '2026-01-01 10:00:00']);
        Syslog::factory()->for($this->device)->create(['program' => 'cron', 'priority' => 'info', 'level' => SyslogSeverity::INFO, 'msg' => 'Job done', 'timestamp' => '2026-01-02 10:00:00']);
        Syslog::factory()->for($other)->create(['program' => 'sshd', 'priority' => 'err', 'level' => SyslogSeverity::ERROR, 'msg' => 'Login failed', 'timestamp' => '2026-01-03 10:00:00']);
    }

    public function testPagesRenderDataTable(): void
    {
        $this->actingAs($this->admin)->get('/syslog')
            ->assertOk()->assertSee('dataTable(', false)->assertDontSee('bootgrid(', false);

        $this->actingAs($this->admin)->get("/device/{$this->device->device_id}/logs/syslog?program=sshd")
            ->assertOk()->assertSee('dataTable(', false)->assertDontSee('bootgrid(', false)
            ->assertSee('"program":{"eq":"sshd"}', false);
    }

    public function testFilters(): void
    {
        $this->assertTotal(3, []);
        $this->assertTotal(2, ['filter' => ['program' => ['eq' => 'sshd']]]);
        $this->assertTotal(1, ['filter' => ['program' => ['eq' => 'sshd'], 'device_id' => ['eq' => $this->device->device_id]]]);
        $this->assertTotal(2, ['filter' => ['search' => ['contains' => 'login']]]);
        $this->assertTotal(1, ['filter' => ['timestamp' => ['on' => '2026-01-02']]]);
        $this->assertTotal(2, ['filter' => ['timestamp' => ['after' => '2026-01-01 12:00']]]);
        $this->assertTotal(1, ['filter' => ['timestamp' => ['before' => '2026-01-01 12:00']]]);
        $this->assertTotal(2, ['device' => $this->device->device_id]);
        // widget level: error and more severe
        $this->assertTotal(2, ['level' => 3]);
    }

    public function testSort(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/ajax/table/syslog?' . http_build_query(['sort' => ['timestamp' => 'asc']]))
            ->assertOk();

        $this->assertSame(['Login failed', 'Job done', 'Login failed'], array_column($response->json('data'), 'msg'));
    }

    private function assertTotal(int $expected, array $params): void
    {
        $this->actingAs($this->admin)
            ->getJson('/ajax/table/syslog?' . http_build_query($params))
            ->assertOk()
            ->assertJsonPath('meta.total', $expected);
    }
}
