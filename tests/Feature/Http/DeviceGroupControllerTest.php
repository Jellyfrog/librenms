<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\ServiceTemplate;
use App\Models\User;
use Illuminate\Support\Js;
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

test('index renders alpine actions', function () {
    $group = DeviceGroup::factory()->create();

    $this->actingAs(admin())
        ->get(route('device-groups.index'))
        ->assertOk()
        ->assertSee('rediscover(', false)
        ->assertSee('destroy($el', false)
        ->assertSee(Js::from(route('device-groups.rediscover', $group->id))->toHtml(), false)
        ->assertDontSee('ajax_form.php', false);
});

test('admin can rediscover group', function () {
    $group = DeviceGroup::factory()->create(['name' => 'Core <b>']);
    $members = Device::factory()->count(2)->create(['last_discovered' => '2026-01-01 00:00:00']);
    $other = Device::factory()->create(['last_discovered' => '2026-01-01 00:00:00']);
    $group->devices()->attach($members->pluck('device_id'));

    $this->actingAs(admin())
        ->postJson(route('device-groups.rediscover', $group))
        ->assertOk()
        ->assertJson(['message' => 'Devices of group Core &lt;b&gt; will be rediscovered']);

    foreach ($members as $member) {
        expect($member->fresh()->last_discovered)->toBeNull();
    }
    expect($other->fresh()->last_discovered)->not->toBeNull();
});

test('user cannot rediscover group', function () {
    $group = DeviceGroup::factory()->create();
    $device = Device::factory()->create(['last_discovered' => '2026-01-01 00:00:00']);
    $group->devices()->attach($device);

    $this->actingAs(user())
        ->postJson(route('device-groups.rediscover', $group))
        ->assertForbidden();

    expect($device->fresh()->last_discovered)->not->toBeNull();
});

test('admin can delete group', function () {
    $group = DeviceGroup::factory()->create(['name' => 'Edge']);

    $this->actingAs(admin())
        ->deleteJson(route('device-groups.destroy', $group))
        ->assertOk()
        ->assertJson(['message' => 'Device Group Edge deleted']);

    expect(DeviceGroup::find($group->id))->toBeNull();
});

test('delete is rejected while service templates use group', function () {
    $group = DeviceGroup::factory()->create();
    $template = ServiceTemplate::factory()->create();
    $group->serviceTemplates()->attach($template);

    $this->actingAs(admin())
        ->deleteJson(route('device-groups.destroy', $group))
        ->assertUnprocessable()
        ->assertJsonStructure(['message']);

    expect(DeviceGroup::find($group->id))->not->toBeNull();
});

test('user cannot delete group', function () {
    $group = DeviceGroup::factory()->create();

    $this->actingAs(user())
        ->deleteJson(route('device-groups.destroy', $group))
        ->assertForbidden();

    expect(DeviceGroup::find($group->id))->not->toBeNull();
});