<?php

use App\Actions\Device\UpdateDeviceOutage;
use App\Facades\LibrenmsConfig;
use App\Models\AlertSchedule;
use App\Models\Device;
use App\Models\DeviceOutage;
use Carbon\Carbon;
use LibreNMS\Enum\MaintenanceBehavior;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    $this->action = app(UpdateDeviceOutage::class);
});

test('closes open outage when device comes up', function (): void {
    $device = Device::factory()->create(['status' => 1]);
    DeviceOutage::factory()->open()->create(['device_id' => $device->device_id]);

    $this->action->execute($device);

    expect($device->outages()->whereNull('up_again')->first())->toBeNull();
    expect($device->outages()->first()->up_again)->not->toBeNull();
});

test('closes open outage when device comes up during maintenance', function (): void {
    LibrenmsConfig::set('graphing.availability_consider_maintenance', true);

    $device = Device::factory()->create(['status' => 1]);
    DeviceOutage::factory()->open()->create(['device_id' => $device->device_id]);

    $schedule = AlertSchedule::factory()->create([
        'start' => Carbon::now()->subHour(),
        'end' => Carbon::now()->addHour(),
        'behavior' => MaintenanceBehavior::MuteAlerts,
    ]);
    $schedule->devices()->attach($device);

    $this->action->execute($device);

    expect($device->outages()->whereNull('up_again')->first())->toBeNull('Outage should be closed even when device is in maintenance');
    expect($device->outages()->first()->up_again)->not->toBeNull();
});

test('does nothing when device up with no open outage', function (): void {
    $device = Device::factory()->create(['status' => 1]);
    DeviceOutage::factory()->closed()->create(['device_id' => $device->device_id]);

    $this->action->execute($device);

    expect($device->outages)->toHaveCount(1);
    expect($device->outages()->first()->up_again)->not->toBeNull();
});

test('opens new outage when device goes down', function (): void {
    $device = Device::factory()->create(['status' => 0]);

    $this->action->execute($device);

    expect($device->outages()->get())->toHaveCount(1);
    expect($device->outages()->first()->up_again)->toBeNull();
});

test('does not open duplicate outage when one already open', function (): void {
    $device = Device::factory()->create(['status' => 0]);
    DeviceOutage::factory()->open()->create(['device_id' => $device->device_id]);

    $this->action->execute($device);

    expect($device->outages()->get())->toHaveCount(1);
});

test('does not open outage during maintenance when consider maintenance enabled', function (): void {
    LibrenmsConfig::set('graphing.availability_consider_maintenance', true);

    $device = Device::factory()->create(['status' => 0]);
    $schedule = AlertSchedule::factory()->create([
        'start' => Carbon::now()->subHour(),
        'end' => Carbon::now()->addHour(),
        'behavior' => MaintenanceBehavior::MuteAlerts,
    ]);
    $schedule->devices()->attach($device);

    $this->action->execute($device);

    expect($device->outages()->get())->toHaveCount(0);
});

test('saving status change to up closes open outage', function (): void {
    $device = Device::factory()->create(['status' => 0]);
    DeviceOutage::factory()->open()->create(['device_id' => $device->device_id]);

    $device->status = 1;
    $device->save();

    expect($device->outages()->whereNull('up_again')->first())->toBeNull('Outage must be closed when the device status is saved as up');
});

test('saving status change to down opens outage', function (): void {
    $device = Device::factory()->create(['status' => 1]);

    $device->status = 0;
    $device->save();

    expect($device->outages()->get())->toHaveCount(1);
    expect($device->outages()->first()->up_again)->toBeNull();
});

test('saving without status change leaves outages alone', function (): void {
    $device = Device::factory()->create(['status' => 0]);
    DeviceOutage::factory()->open()->create(['device_id' => $device->device_id]);

    $device->sysName = 'unrelated-change';
    $device->save();

    expect($device->outages()->get())->toHaveCount(1);
    expect($device->outages()->first()->up_again)->toBeNull();
});

test('opens outage during maintenance when consider maintenance disabled', function (): void {
    LibrenmsConfig::set('graphing.availability_consider_maintenance', false);

    $device = Device::factory()->create(['status' => 0]);
    $schedule = AlertSchedule::factory()->create([
        'start' => Carbon::now()->subHour(),
        'end' => Carbon::now()->addHour(),
        'behavior' => MaintenanceBehavior::MuteAlerts,
    ]);
    $schedule->devices()->attach($device);

    $this->action->execute($device);

    expect($device->outages()->get())->toHaveCount(1);
    expect($device->outages()->first()->up_again)->toBeNull();
});