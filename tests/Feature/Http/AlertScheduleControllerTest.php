<?php

use App\Models\AlertSchedule;
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\Location;
use App\Models\User;
use App\Models\UserPref;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use LibreNMS\Enum\MaintenanceBehavior;
use Spatie\Permission\Models\Role;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

afterEach(function (): void {
    Carbon::setTestNow();

});

function alertScheduleAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function alertScheduleUser(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

test('admin can start device maintenance', function (): void {
    Carbon::setTestNow('2026-10-02 12:00:00');
    $device = Device::factory()->create();

    $response = $this->actingAs(alertScheduleAdmin())
        ->postJson(route('alert-schedule.store'), [
            'title' => 'Device maintenance',
            'notes' => 'Swapping PSU',
            'behavior' => MaintenanceBehavior::MuteAlerts->value,
            'recurring' => 0,
            'start' => Carbon::now()->toIso8601String(),
            'duration' => '1:30',
            'maps' => [$device->device_id],
        ])
        ->assertCreated()
        ->assertJsonStructure(['message', 'schedule_id']);

    $schedule = AlertSchedule::findOrFail($response->json('schedule_id'));
    expect($schedule->title)->toBe('Device maintenance');
    expect($schedule->notes)->toBe('Swapping PSU');
    expect($schedule->behavior)->toBe(MaintenanceBehavior::MuteAlerts);
    expect($schedule->recurring)->toEqual(0);
    expect($schedule->start->equalTo(Carbon::now()))->toBeTrue();
    expect($schedule->end->equalTo(Carbon::now()->addMinutes(90)))->toBeTrue();
    expect($schedule->devices()->pluck('devices.device_id')->all())->toEqual([$device->device_id]);
    expect($device->alertSchedules()->isActive()->exists())->toBeTrue();
});

test('store maps devices groups and locations', function (): void {
    $device = Device::factory()->create();
    $group = DeviceGroup::factory()->create();
    $location = Location::factory()->create();

    $response = $this->actingAs(alertScheduleAdmin())
        ->postJson(route('alert-schedule.store'), [
            'title' => 'Mixed',
            'notes' => '',
            'behavior' => MaintenanceBehavior::SkipAlerts->value,
            'start' => '2026-10-02 12:00',
            'end' => '2026-10-02 14:00',
            'maps' => [(string) $device->device_id, 'g' . $group->id, 'l' . $location->id],
        ])
        ->assertCreated();

    $schedule = AlertSchedule::findOrFail($response->json('schedule_id'));
    expect($schedule->devices()->pluck('devices.device_id')->all())->toEqual([$device->device_id]);
    expect($schedule->deviceGroups()->pluck('device_groups.id')->all())->toEqual([$group->id]);
    expect($schedule->locations()->pluck('locations.id')->all())->toEqual([$location->id]);
});

test('store recurring schedule', function (): void {
    $device = Device::factory()->create();

    $response = $this->actingAs(alertScheduleAdmin())
        ->postJson(route('alert-schedule.store'), [
            'title' => 'Nightly',
            'behavior' => MaintenanceBehavior::SkipAlerts->value,
            'recurring' => 1,
            'start_recurring_dt' => '2026-10-02',
            'start_recurring_hr' => '23:00',
            'end_recurring_hr' => '01:00',
            'recurring_day' => [1, 3, 5],
            'maps' => [$device->device_id],
        ])
        ->assertCreated();

    $schedule = AlertSchedule::findOrFail($response->json('schedule_id'));
    expect($schedule->recurring)->toEqual(1);
    expect($schedule->start_recurring_dt)->toBe('2026-10-02');
    expect($schedule->start_recurring_hr)->toBe('23:00');
    expect($schedule->end_recurring_dt)->toBeNull();
    expect($schedule->end_recurring_hr)->toBe('01:00');
    expect($schedule->recurring_day)->toBe(['Mo', 'We', 'Fr']);
});

test('store appends note to devices when preference enabled', function (): void {
    Carbon::setTestNow('2026-10-02 12:00:00');
    $admin = alertScheduleAdmin();
    UserPref::setPref($admin, 'add_schedule_note_to_device', 1);
    $device = Device::factory()->create(['notes' => 'existing']);

    $this->actingAs($admin)
        ->postJson(route('alert-schedule.store'), [
            'title' => 'Noted',
            'notes' => 'Upgrading firmware',
            'behavior' => MaintenanceBehavior::SkipAlerts->value,
            'start' => '2026-10-02T12:00:00Z',
            'duration' => '0:30',
            'maps' => [$device->device_id],
        ])
        ->assertCreated();

    expect($device->fresh()->notes)->toBe("existing\n2026-10-02 12:00 Alerts delayed: Upgrading firmware");
});

test('store validates input', function (): void {
    $schedule_count = AlertSchedule::count();
    $this->actingAs(alertScheduleAdmin())
        ->postJson(route('alert-schedule.store'), [
            'behavior' => 9,
            'start' => 'not a date',
            'maps' => ['x1'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'behavior', 'start', 'end', 'maps.0']);

    $this->actingAs(alertScheduleAdmin())
        ->postJson(route('alert-schedule.store'), [
            'title' => 'Recurring',
            'behavior' => MaintenanceBehavior::SkipAlerts->value,
            'recurring' => 1,
            'maps' => [1],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_recurring_dt', 'start_recurring_hr', 'end_recurring_hr'])
        ->assertJsonMissingValidationErrors(['start', 'end']);

    expect(AlertSchedule::count())->toBe($schedule_count);
});

test('user cannot start maintenance', function (): void {
    $schedule_count = AlertSchedule::count();
    $device = Device::factory()->create();

    $this->actingAs(alertScheduleUser())
        ->postJson(route('alert-schedule.store'), [
            'title' => 'Nope',
            'behavior' => MaintenanceBehavior::SkipAlerts->value,
            'start' => '2026-10-02T12:00:00Z',
            'duration' => '0:30',
            'maps' => [$device->device_id],
        ])
        ->assertForbidden();

    expect(AlertSchedule::count())->toBe($schedule_count);
});

test('admin can end maintenance', function (): void {
    Carbon::setTestNow('2026-10-02 12:00:00');
    $device = Device::factory()->create();
    $schedule = AlertSchedule::factory()->create([
        'start' => '2026-10-02 11:00:00',
        'end' => '2026-10-02 14:00:00',
    ]);
    $schedule->devices()->attach($device);
    expect($device->alertSchedules()->isActive()->exists())->toBeTrue();

    $this->actingAs(alertScheduleAdmin())
        ->postJson(route('alert-schedule.end', $schedule))
        ->assertOk()
        ->assertJson(['message' => 'Maintenance has been ended']);

    expect($schedule->fresh()->end->equalTo(Carbon::now()))->toBeTrue();
});

test('user cannot end maintenance', function (): void {
    $schedule = AlertSchedule::factory()->create();

    $this->actingAs(alertScheduleUser())
        ->postJson(route('alert-schedule.end', $schedule))
        ->assertForbidden();
});

test('show returns schedule with targets', function (): void {
    $device = Device::factory()->create();
    $group = DeviceGroup::factory()->create(['name' => 'Core']);
    $location = Location::factory()->create(['location' => 'Rack 1']);
    $schedule = AlertSchedule::factory()->create([
        'title' => 'Weekly',
        'recurring' => 1,
        'start' => '2026-10-02 23:00:00',
        'end' => '9000-09-09 01:00:00',
        'recurring_day' => '1,7',
    ]);
    $schedule->devices()->attach($device);
    $schedule->deviceGroups()->attach($group);
    $schedule->locations()->attach($location);

    $this->actingAs(alertScheduleAdmin())
        ->getJson(route('alert-schedule.show', $schedule))
        ->assertOk()
        ->assertJson([
            'title' => 'Weekly',
            'recurring' => 1,
            'recurring_day' => ['Mo', 'Su'],
            'start_recurring_hr' => '23:00',
            'end_recurring_dt' => null,
            'targets' => [
                ['id' => $device->device_id, 'text' => $device->display],
                ['id' => 'g' . $group->id, 'text' => 'Core'],
                ['id' => 'l' . $location->id, 'text' => 'Rack 1'],
            ],
        ]);
});

test('user cannot show schedule', function (): void {
    $schedule = AlertSchedule::factory()->create();

    $this->actingAs(alertScheduleUser())
        ->getJson(route('alert-schedule.show', $schedule))
        ->assertForbidden();
});

test('admin can update schedule', function (): void {
    $schedule_count = AlertSchedule::count();
    $old_device = Device::factory()->create();
    $new_device = Device::factory()->create();
    $schedule = AlertSchedule::factory()->create([
        'title' => 'Old',
        'recurring' => 1,
        'recurring_day' => '1',
    ]);
    $schedule->devices()->attach($old_device);

    $this->actingAs(alertScheduleAdmin())
        ->putJson(route('alert-schedule.update', $schedule), [
            'title' => 'New',
            'notes' => 'changed',
            'behavior' => MaintenanceBehavior::RunAlerts->value,
            'start' => '2026-10-02 12:00',
            'end' => '2026-10-02 14:00',
            'maps' => [$new_device->device_id],
        ])
        ->assertOk()
        ->assertJson(['schedule_id' => $schedule->schedule_id]);

    $schedule->refresh();
    expect($schedule->title)->toBe('New');
    expect($schedule->notes)->toBe('changed');
    expect($schedule->behavior)->toBe(MaintenanceBehavior::RunAlerts);
    expect($schedule->recurring)->toEqual(0);
    expect($schedule->recurring_day)->toBe([]);
    expect($schedule->devices()->pluck('devices.device_id')->all())->toEqual([$new_device->device_id]);
    expect(AlertSchedule::count())->toBe($schedule_count + 1);
    // updated in place, not duplicated
});

test('user cannot update schedule', function (): void {
    $schedule = AlertSchedule::factory()->create(['title' => 'Old']);

    $this->actingAs(alertScheduleUser())
        ->putJson(route('alert-schedule.update', $schedule), [
            'title' => 'New',
            'behavior' => MaintenanceBehavior::SkipAlerts->value,
            'start' => '2026-10-02 12:00',
            'end' => '2026-10-02 14:00',
            'maps' => [1],
        ])
        ->assertForbidden();

    expect($schedule->fresh()->title)->toBe('Old');
});

test('admin can delete schedule', function (): void {
    $device = Device::factory()->create();
    $schedule = AlertSchedule::factory()->create();
    $schedule->devices()->attach($device);

    $this->actingAs(alertScheduleAdmin())
        ->deleteJson(route('alert-schedule.destroy', $schedule))
        ->assertOk();

    expect(AlertSchedule::find($schedule->schedule_id))->toBeNull();
    expect(DB::table('alert_schedulables')->where('schedule_id', $schedule->schedule_id)->count())->toBe(0);
});

test('user cannot delete schedule', function (): void {
    $schedule = AlertSchedule::factory()->create();

    $this->actingAs(alertScheduleUser())
        ->deleteJson(route('alert-schedule.destroy', $schedule))
        ->assertForbidden();

    expect($schedule->fresh())->not->toBeNull();
});

test('device edit page renders maintenance button', function (): void {
    $device = Device::factory()->create();

    $this->actingAs(alertScheduleAdmin())
        ->get(route('device.edit', $device))
        ->assertOk()
        ->assertSee('id="maintenance"', false)
        ->assertSee("route('alert-schedule.end'", false)
        ->assertDontSee('<maintenance-mode', false);
});

test('table shows behavior label', function (): void {
    AlertSchedule::factory()->create([
        'title' => 'Muted window',
        'behavior' => MaintenanceBehavior::MuteAlerts,
    ]);

    $this->actingAs(alertScheduleAdmin())
        ->postJson('/ajax/table/alert-schedule', ['searchPhrase' => 'Muted window'])
        ->assertOk()
        ->assertJsonPath('rows.0.title', 'Muted window')
        ->assertJsonPath('rows.0.behavior', MaintenanceBehavior::MuteAlerts->descr());
});