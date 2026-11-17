<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\User;
use App\Models\WirelessSensor;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function user(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function sensor(Device $device, array $attributes = []): WirelessSensor
{
    return WirelessSensor::factory()->for($device)->create(array_merge([
        'sensor_class' => 'clients',
        'sensor_descr' => 'Radio <b>1</b>',
        'sensor_current' => 12,
        'sensor_limit' => 50,
        'sensor_limit_warn' => 40,
        'sensor_limit_low_warn' => 5,
        'sensor_limit_low' => 1,
        'sensor_alert' => 1,
        'sensor_custom' => 'No',
    ], $attributes));
}

test('admin can view wireless sensors page', function () {
    $device = Device::factory()->create();
    sensor($device);
    sensor($device, ['sensor_descr' => 'Deleted sensor', 'sensor_deleted' => 1]);

    $this->actingAs(admin())
        ->get(route('device.edit.wireless-sensors', $device))
        ->assertOk()
        ->assertSee('Radio &lt;b&gt;1&lt;/b&gt;', false)
        ->assertDontSee('Radio <b>1</b>', false)
        ->assertDontSee('Deleted sensor')
        ->assertSee('value="50"', false)
        ->assertSee('value="40"', false)
        ->assertSee('value="5"', false)
        ->assertSee('value="1"', false);
});

test('user cannot view wireless sensors page', function () {
    $device = Device::factory()->create();

    $this->actingAs(user())
        ->get(route('device.edit.wireless-sensors', $device))
        ->assertForbidden();
});

test('admin can update limit', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device);

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_limit_warn' => '45.5'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $sensor->refresh();
    expect($sensor->sensor_limit_warn)->toEqual(45.5);
    expect($sensor->sensor_custom)->toBe('Yes');
});

test('empty limit clears it', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device);

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_limit_low' => ''])
        ->assertOk();

    expect($sensor->fresh()->sensor_limit_low)->toBeNull();
});

test('invalid limit is rejected', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device);

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_limit' => 'abc'])
        ->assertUnprocessable();

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_current' => '1'])
        ->assertUnprocessable();

    $sensor->refresh();
    expect($sensor->sensor_limit)->toEqual(50);
    expect($sensor->sensor_current)->toEqual(12);
    expect($sensor->sensor_custom)->toBe('No');
});

test('empty update is rejected', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device);

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), [])
        ->assertUnprocessable();

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_custom' => 'Yes'])
        ->assertUnprocessable();

    expect($sensor->fresh()->sensor_custom)->toBe('No');
});

test('sensor must belong to device', function () {
    $device = Device::factory()->create();
    $otherSensor = sensor(Device::factory()->create());

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $otherSensor]), ['sensor_limit' => 99])
        ->assertNotFound();

    expect($otherSensor->fresh()->sensor_limit)->toEqual(50);
});

test('user cannot update limit', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device);

    $this->actingAs(user())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_limit' => 99])
        ->assertForbidden();

    expect($sensor->fresh()->sensor_limit)->toEqual(50);
});

test('admin can toggle alerts', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device);

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_alert' => 0])
        ->assertOk()
        ->assertJson(['status' => 'ok']);
    expect($sensor->fresh()->sensor_alert)->toEqual(0);
    expect($sensor->fresh()->sensor_custom)->toBe('No');

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_alert' => 1])
        ->assertOk()
        ->assertJson(['status' => 'ok']);
    expect($sensor->fresh()->sensor_alert)->toEqual(1);
});

test('user cannot toggle alerts', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device);

    $this->actingAs(user())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_alert' => 0])
        ->assertForbidden();

    expect($sensor->fresh()->sensor_alert)->toEqual(1);
});

test('admin can remove custom limit', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device, ['sensor_custom' => 'Yes']);

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.update', [$device, $sensor]), ['sensor_custom' => 'No'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    $sensor->refresh();
    expect($sensor->sensor_custom)->toBe('No');
    assertLimitsCleared($sensor);
});

test('admin can reset all custom limits', function () {
    $device = Device::factory()->create();
    $first = sensor($device, ['sensor_custom' => 'Yes']);
    $second = sensor($device, ['sensor_custom' => 'Yes']);
    $other = sensor(Device::factory()->create(), ['sensor_custom' => 'Yes']);

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.reset', $device))
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    foreach ([$first->fresh(), $second->fresh()] as $sensor) {
        expect($sensor->sensor_custom)->toBe('No');
        assertLimitsCleared($sensor);
    }

    $other->refresh();
    expect($other->sensor_custom)->toBe('Yes');
    expect($other->sensor_limit)->toEqual(50);
});

test('reset all leaves discovered limits alone', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device, ['sensor_custom' => 'No']);

    $this->actingAs(admin())
        ->postJson(route('device.edit.wireless-sensors.reset', $device))
        ->assertOk();

    expect($sensor->fresh()->sensor_limit)->toEqual(50);
});

function assertLimitsCleared(WirelessSensor $sensor): void
{
    expect($sensor->sensor_limit)->toBeNull();
    expect($sensor->sensor_limit_warn)->toBeNull();
    expect($sensor->sensor_limit_low_warn)->toBeNull();
    expect($sensor->sensor_limit_low)->toBeNull();
}

test('user cannot reset custom limits', function () {
    $device = Device::factory()->create();
    $sensor = sensor($device, ['sensor_custom' => 'Yes']);

    $this->actingAs(user())
        ->postJson(route('device.edit.wireless-sensors.reset', $device))
        ->assertForbidden();

    expect($sensor->fresh()->sensor_custom)->toBe('Yes');
});