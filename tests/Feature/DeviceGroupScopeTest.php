<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Models\Device;
use App\Models\DeviceGroup;

test('device group scopes accept collections', function (): void {
    $group = DeviceGroup::factory()->create();
    $inGroup = Device::factory()->create();
    $free = Device::factory()->create();
    $group->devices()->attach($inGroup);

    // empty collection: a service template with no device groups (#21039).
    // This used to throw "Nested arrays may not be passed to whereIn method"
    expect(Device::notInDeviceGroup(collect([]))->pluck('device_id')->all())->toEqualCanonicalizing([$inGroup->device_id, $free->device_id]);
    expect(Device::inDeviceGroup(collect([]))->pluck('device_id')->all())->toBe([]);

    // non-empty collections keep working, values are the group ids
    expect(Device::notInDeviceGroup(collect([$group->id]))->pluck('device_id')->all())->toBe([$free->device_id]);
    expect(Device::inDeviceGroup(collect([$group->id]))->pluck('device_id')->all())->toBe([$inGroup->device_id]);
});
