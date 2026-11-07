<?php

namespace LibreNMS\Tests\Feature;

use App\Actions\Device\SetDeviceAvailability;
use App\Actions\Device\UpdateDeviceOutage;
use App\Facades\LibrenmsConfig;
use App\Models\AlertSchedule;
use App\Models\Device;
use App\Models\DeviceOutage;
use App\Models\DevicePollingMethod;
use Carbon\Carbon;
use LibreNMS\Cache\DeviceMaintenanceCache;
use LibreNMS\Enum\MaintenanceBehavior;
use LibreNMS\Enum\OutageAction;
use LibreNMS\Enum\OutageState;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Tests\InMemoryDbTestCase;

final class DeviceOutageLifecycleTest extends InMemoryDbTestCase
{
    public function testOutageOpensWhenMaintenanceEndsWhileDeviceIsStillDown(): void
    {
        LibrenmsConfig::set('graphing.availability_consider_maintenance', true);
        $device = $this->deviceWithIcmp(up: true);
        $schedule = AlertSchedule::factory()->create([
            'start' => Carbon::now()->subHour(),
            'end' => Carbon::now()->addHour(),
            'behavior' => MaintenanceBehavior::MuteAlerts,
        ]);
        $schedule->devices()->attach($device);

        // goes down during maintenance: not an outage
        $this->setIcmp($device, false);
        $this->assertTrue(app(SetDeviceAvailability::class)->execute($device));
        $this->assertSame(0, $device->outages()->count());

        // maintenance ends, device still down, next check (no status change) opens the outage
        $schedule->end = Carbon::now()->subMinute();
        $schedule->save();
        app()->forgetInstance(DeviceMaintenanceCache::class);

        $this->assertFalse(app(SetDeviceAvailability::class)->execute($device));
        $this->assertSame(1, $device->outages()->whereNull('up_again')->count());

        // further checks don't add more
        app(SetDeviceAvailability::class)->execute($device);
        $this->assertSame(1, $device->outages()->count());
    }

    public function testOpeningTwiceLeavesOneOpenOutage(): void
    {
        $device = Device::factory()->create(['status' => 0]);
        // a second process (PingCheck vs PollDevice) that looked at outages before the first one opened it
        $racing = Device::with('outages')->find($device->device_id);

        DeviceOutage::factory()->open()->create(['device_id' => $device->device_id, 'going_down' => time() - 60]);

        app(UpdateDeviceOutage::class)->execute($device);
        app(UpdateDeviceOutage::class)->execute($racing);

        $this->assertSame(1, $device->outages()->whereNull('up_again')->count());
    }

    public function testComingUpClosesOutage(): void
    {
        $device = $this->deviceWithIcmp(up: true);

        $this->setIcmp($device, false);
        app(SetDeviceAvailability::class)->execute($device);
        $this->assertSame(1, $device->outages()->whereNull('up_again')->count());

        $this->setIcmp($device, true);
        app(SetDeviceAvailability::class)->execute($device);
        $this->assertSame(0, $device->outages()->whereNull('up_again')->count());
        $this->assertNotNull($device->outages()->first()->up_again);
    }

    public function testDisablingDownDeviceClosesOutageAndNextPollAfterEnableReopens(): void
    {
        $device = $this->deviceWithIcmp(up: false);
        DeviceOutage::factory()->open()->create(['device_id' => $device->device_id, 'going_down' => time() - 600]);

        $device->disabled = true;
        $device->save();
        $this->assertSame(0, $device->outages()->whereNull('up_again')->count());

        $device->disabled = false;
        $device->save();
        $this->assertSame(0, $device->outages()->whereNull('up_again')->count(), 'stale status must not reopen on enable');

        app(SetDeviceAvailability::class)->execute($device);
        $this->assertSame(1, $device->outages()->whereNull('up_again')->count());
    }

    public function testTransitionTable(): void
    {
        $this->assertSame(OutageAction::Open, OutageState::Down->outageAction(false));
        $this->assertSame(OutageAction::None, OutageState::Down->outageAction(true));
        $this->assertSame(OutageAction::Close, OutageState::Up->outageAction(true));
        $this->assertSame(OutageAction::None, OutageState::Up->outageAction(false));
        $this->assertSame(OutageAction::Close, OutageState::Disabled->outageAction(true));
        $this->assertSame(OutageAction::None, OutageState::DownInMaintenance->outageAction(false));
        $this->assertSame(OutageAction::None, OutageState::DownInMaintenance->outageAction(true));
    }

    private function deviceWithIcmp(bool $up): Device
    {
        $device = Device::factory()->create(['status' => (int) $up, 'disabled' => 0]);
        DevicePollingMethod::factory()->create([
            'device_id' => $device->device_id,
            'method_type' => PollingMethodType::Icmp,
            'last_check_successful' => $up,
        ]);

        return $device->fresh();
    }

    private function setIcmp(Device $device, bool $up): void
    {
        $method = $device->pollingMethod(PollingMethodType::Icmp);
        $method->last_check_successful = $up;
        $method->save();
    }
}
