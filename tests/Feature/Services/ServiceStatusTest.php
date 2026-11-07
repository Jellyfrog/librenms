<?php

namespace LibreNMS\Tests\Feature\Services;

use App\Facades\LibrenmsConfig;
use App\Http\Controllers\Widgets\AvailabilityMapController;
use App\Models\Device;
use App\Models\Eventlog;
use App\Models\Service;
use App\Models\User;
use LibreNMS\Enum\ServiceStatus;
use LibreNMS\Tests\InMemoryDbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;

final class ServiceStatusTest extends InMemoryDbTestCase
{
    private ?string $pluginDir = null;

    protected function tearDown(): void
    {
        if ($this->pluginDir) {
            array_map('unlink', glob($this->pluginDir . '/*') ?: []);
            @rmdir($this->pluginDir);
        }

        parent::tearDown();
    }

    public function testAvailabilityMapDoesNotShowUnknownServiceAsDown(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create(['enabled' => 1]);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $device = Device::factory()->create();
        foreach ([0 => 'ok', 1 => 'warning', 2 => 'critical', 3 => 'unknown'] as $status => $name) {
            Service::factory()->for($device)->create(['service_name' => $name, 'service_status' => $status]);
        }

        $controller = new AvailabilityMapController();
        (new \ReflectionProperty($controller, 'settings'))->setValue($controller, [
            'mode_select' => 1,
            'type' => 0,
            'color_only_select' => 0,
            'order_by' => 'status',
            'device_group' => null,
        ]);
        [$data, $totals] = (new \ReflectionMethod($controller, 'getServices'))->invoke($controller);

        $classes = array_column($data, 'labelClass', 'label');
        $this->assertSame('label-success', $classes['ok']);
        $this->assertSame('label-warning', $classes['warning']);
        $this->assertSame('label-danger', $classes['critical']);
        $this->assertNotSame('label-danger', $classes['unknown'], 'Unknown service must not be shown as down');
        $this->assertSame(['warn' => 2, 'up' => 1, 'down' => 1], $totals);
        $this->assertSame([0, 1, 2, 3], array_column($data, 'status'));
    }

    public function testModelExposesEnumAndToleratesLegacyOutOfRangeValues(): void
    {
        $device = Device::factory()->create();
        $legacy = Service::factory()->for($device)->create(['service_name' => 'legacy']);
        // rows written by older versions may hold raw exit codes such as 127
        Service::query()->toBase()->where('service_id', $legacy->service_id)->update(['service_status' => 127]);
        $critical = Service::factory()->for($device)->create(['service_name' => 'crit', 'service_status' => ServiceStatus::Critical]);

        $this->assertSame(ServiceStatus::Unknown, $legacy->fresh()->service_status);
        $this->assertSame(ServiceStatus::Critical, $critical->fresh()->service_status);
        $this->assertSame(2, Service::query()->toBase()->where('service_id', $critical->service_id)->value('service_status'));
        $this->assertSame(2, json_decode($critical->fresh()->toJson(), true)['service_status']);
        $this->assertSame([$critical->service_id], Service::isCritical()->pluck('service_id')->all());

        $component = new \App\View\Components\Device\Overview\Services($device->fresh());
        $this->assertSame(['total' => 2, 'ok' => 0, 'warning' => 0, 'critical' => 1], $component->counts);
        $this->assertSame(['default', 'danger'], array_column($component->serviceLabels, 'status'));
    }

    public static function statusChanges(): array
    {
        return [
            'critical' => [2, ServiceStatus::Critical, 'changed status from OK to Critical'],
            'unknown' => [3, ServiceStatus::Unknown, 'changed status from OK to Unknown'],
            'command not found is unknown' => [127, ServiceStatus::Unknown, 'changed status from OK to Unknown'],
        ];
    }

    #[DataProvider('statusChanges')]
    public function testPollServiceLogsAndStoresNormalizedStatus(int $exitCode, ServiceStatus $expected, string $logText): void
    {
        $this->pluginDir = sys_get_temp_dir() . '/librenms-service-test-' . uniqid();
        mkdir($this->pluginDir);
        file_put_contents($this->pluginDir . '/check_fake', "#!/bin/sh\necho 'FAKE output'\nexit $exitCode\n");
        chmod($this->pluginDir . '/check_fake', 0755);
        LibrenmsConfig::set('nagios_plugins', $this->pluginDir);

        $device = Device::factory()->create();
        $service = Service::factory()->for($device)->create([
            'service_type' => 'fake',
            'service_name' => 'svc',
            'service_desc' => 'desc',
            'service_status' => 0,
            'service_message' => 'old',
        ]);

        // poll_service() is loaded by tests/bootstrap.php; check-services.php passes a raw row of devices joined with services
        poll_service(array_merge($device->fresh()->getRawOriginal(), $service->fresh()->getRawOriginal()));

        $raw = Service::query()->toBase()->where('service_id', $service->service_id)->value('service_status');
        $this->assertSame($expected->value, (int) $raw);
        $this->assertTrue(
            Eventlog::where('type', 'service')->where('message', 'like', "%$logText%")->exists(),
            'Event log: ' . Eventlog::pluck('message')->implode(', ')
        );
    }
}
