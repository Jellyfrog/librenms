<?php

use App\Models\AlertSchedule;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\Location;
use Carbon\Carbon;
use LibreNMS\Cache\DeviceMaintenanceCache;
use LibreNMS\Enum\MaintenanceBehavior;
use LibreNMS\Enum\MaintenanceStatus;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

test('returns none when no schedules active', function (): void {
    $device = Device::factory()->create();

    $cache = new DeviceMaintenanceCache();
    expect($cache->statusFor($device->device_id))->toEqual(MaintenanceStatus::None);
    expect($device->getMaintenanceStatus())->toEqual(MaintenanceStatus::None);
});

test('direct device maintenance schedule', function (): void {
    $device = Device::factory()->create();
    $schedule = AlertSchedule::factory()->create([
        'start' => Carbon::now()->subHour(),
        'end' => Carbon::now()->addHour(),
        'behavior' => MaintenanceBehavior::MuteAlerts,
    ]);
    $schedule->devices()->attach($device);

    $cache = new DeviceMaintenanceCache();
    expect($cache->statusFor($device->device_id))->toEqual(MaintenanceStatus::MuteAlerts);
    expect($device->getMaintenanceStatus())->toEqual(MaintenanceStatus::MuteAlerts);
});

test('location maintenance schedule', function (): void {
    $location = Location::factory()->create();
    $device = Device::factory()->create(['location_id' => $location->id]);
    $schedule = AlertSchedule::factory()->create([
        'start' => Carbon::now()->subHour(),
        'end' => Carbon::now()->addHour(),
        'behavior' => MaintenanceBehavior::SkipAlerts,
    ]);
    $schedule->locations()->attach($location);

    $cache = new DeviceMaintenanceCache();
    expect($cache->statusFor($device->device_id))->toEqual(MaintenanceStatus::SkipAlerts);
    expect($device->getMaintenanceStatus())->toEqual(MaintenanceStatus::SkipAlerts);
});

test('device group maintenance schedule', function (): void {
    $group = DeviceGroup::factory()->create();
    $device = Device::factory()->create();
    $group->devices()->attach($device);

    $schedule = AlertSchedule::factory()->create([
        'start' => Carbon::now()->subHour(),
        'end' => Carbon::now()->addHour(),
        'behavior' => MaintenanceBehavior::RunAlerts,
    ]);
    $schedule->deviceGroups()->attach($group);

    $cache = new DeviceMaintenanceCache();
    expect($cache->statusFor($device->device_id))->toEqual(MaintenanceStatus::RunAlerts);
    expect($device->getMaintenanceStatus())->toEqual(MaintenanceStatus::RunAlerts);
});