<?php

/**
 * DeviceGroupScopeAuthorizationTest.php
 *
 * Verifies that device group actions respect the per-device access of the user,
 * not just the global permission.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

use App\Models\Device;
use App\Models\DeviceGroup;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

const DEVICE_GROUP_SCOPE_DISCOVERED = '2026-01-01 00:00:00';

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
    $this->accessible = Device::factory()->create(['last_discovered' => DEVICE_GROUP_SCOPE_DISCOVERED]);
    $this->other = Device::factory()->create(['last_discovered' => DEVICE_GROUP_SCOPE_DISCOVERED]);
    $this->group = DeviceGroup::factory()->create(['type' => 'static']);
    $this->group->devices()->attach($this->accessible);
});

function deviceGroupScopeAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

/**
 * User limited to the accessible device.
 *
 * @param  string[]  $permissions
 */
function deviceGroupScopeLimitedUser(array $permissions): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }
    $user->givePermissionTo($permissions);
    $user->devicesOwned()->attach(test()->accessible->device_id);

    return $user;
}

/**
 * @param  int[]  $devices
 * @return array{name: string, type: string, devices: int[]}
 */
function deviceGroupScopeStaticPayload(array $devices): array
{
    return ['name' => test()->group->name, 'type' => 'static', 'devices' => $devices];
}

/**
 * @return int[]
 */
function deviceGroupScopeMembers(DeviceGroup $group): array
{
    return $group->devices()->pluck('devices.device_id')->sort()->values()->all();
}

test('scoped user cannot rediscover group with inaccessible device', function (): void {
    $this->group->devices()->attach($this->other);

    $this->actingAs(deviceGroupScopeLimitedUser(['device.update']))
        ->postJson(route('device-groups.rediscover', $this->group))
        ->assertForbidden();

    expect($this->accessible->fresh()->last_discovered)->not->toBeNull();
    expect($this->other->fresh()->last_discovered)->not->toBeNull();
});

test('scoped user cannot rediscover inaccessible group', function (): void {
    $foreign = DeviceGroup::factory()->create(['type' => 'static']);
    $foreign->devices()->attach($this->other);

    $this->actingAs(deviceGroupScopeLimitedUser(['device.update']))
        ->postJson(route('device-groups.rediscover', $foreign))
        ->assertForbidden();

    expect($this->other->fresh()->last_discovered)->not->toBeNull();
});

test('scoped user can rediscover fully accessible group', function (): void {
    $this->actingAs(deviceGroupScopeLimitedUser(['device.update']))
        ->postJson(route('device-groups.rediscover', $this->group))
        ->assertOk();

    expect($this->accessible->fresh()->last_discovered)->toBeNull();
    expect($this->other->fresh()->last_discovered)->not->toBeNull();
});

test('admin can rediscover group', function (): void {
    $this->group->devices()->attach($this->other);

    $this->actingAs(deviceGroupScopeAdmin())
        ->postJson(route('device-groups.rediscover', $this->group))
        ->assertOk();

    expect($this->accessible->fresh()->last_discovered)->toBeNull();
    expect($this->other->fresh()->last_discovered)->toBeNull();
});

test('scoped user cannot add inaccessible device to static group', function (): void {
    $user = deviceGroupScopeLimitedUser(['device-group.update']);

    $this->actingAs($user)
        ->put(route('device-groups.update', $this->group), deviceGroupScopeStaticPayload([$this->accessible->device_id, $this->other->device_id]))
        ->assertSessionHasErrors('devices');

    expect(deviceGroupScopeMembers($this->group))->toBe([$this->accessible->device_id]);

    // ids that do not exist are rejected the same way
    $this->actingAs($user)
        ->put(route('device-groups.update', $this->group), deviceGroupScopeStaticPayload([$this->accessible->device_id, Device::max('device_id') + 1000]))
        ->assertSessionHasErrors('devices');

    expect(deviceGroupScopeMembers($this->group))->toBe([$this->accessible->device_id]);
});

test('scoped user update keeps inaccessible members', function (): void {
    $second = Device::factory()->create();
    $this->group->devices()->attach($this->other);
    $user = deviceGroupScopeLimitedUser(['device-group.update']);
    $user->devicesOwned()->attach($second->device_id);

    // inaccessible member omitted from the form: kept, accessible devices are synced
    $this->actingAs($user)
        ->put(route('device-groups.update', $this->group), deviceGroupScopeStaticPayload([$second->device_id]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('device-groups.index'));

    $expected = [$this->other->device_id, $second->device_id];
    sort($expected);
    expect(deviceGroupScopeMembers($this->group))->toBe($expected);

    // inaccessible member resubmitted by the form: accepted as it is not being added
    $this->actingAs($user)
        ->put(route('device-groups.update', $this->group), deviceGroupScopeStaticPayload([$second->device_id, $this->other->device_id]))
        ->assertSessionHasNoErrors();

    expect(deviceGroupScopeMembers($this->group))->toBe($expected);
});

test('scoped user cannot create static group with inaccessible device', function (): void {
    $payload = ['name' => 'scoped-create-test', 'type' => 'static', 'devices' => [$this->other->device_id]];

    $this->actingAs(deviceGroupScopeLimitedUser(['device-group.create']))
        ->post(route('device-groups.store'), $payload)
        ->assertSessionHasErrors('devices');

    expect(DeviceGroup::where('name', 'scoped-create-test')->exists())->toBeFalse();
});

test('admin can set any devices on static group', function (): void {
    $admin = deviceGroupScopeAdmin();

    $this->actingAs($admin)
        ->put(route('device-groups.update', $this->group), deviceGroupScopeStaticPayload([$this->other->device_id]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('device-groups.index'));

    expect(deviceGroupScopeMembers($this->group))->toBe([$this->other->device_id]);

    $this->actingAs($admin)
        ->post(route('device-groups.store'), ['name' => 'admin-create-test', 'type' => 'static', 'devices' => [$this->accessible->device_id, $this->other->device_id]])
        ->assertSessionHasNoErrors();

    $created = DeviceGroup::where('name', 'admin-create-test')->firstOrFail();
    $expected = [$this->accessible->device_id, $this->other->device_id];
    sort($expected);
    expect(deviceGroupScopeMembers($created))->toBe($expected);
});