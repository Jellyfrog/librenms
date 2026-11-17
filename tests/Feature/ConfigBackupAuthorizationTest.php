<?php

/**
 * ConfigBackupAuthorizationTest.php
 *
 * Verifies the configBackupView / configBackupRefresh policy abilities across roles, in particular
 * that a user restricted to some devices cannot show/refresh config of others.
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

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('global-read');
    Role::findOrCreate('user');
    Permission::findOrCreate('config-backup.view');
    Permission::findOrCreate('config-backup.refresh');
    Permission::findOrCreate('device.view');

    $this->accessible = Device::factory()->create();
    $this->other = Device::factory()->create();
});

function can(User $user, string $ability, Device $device): bool
{
    return Gate::forUser($user)->allows($ability, $device);
}

test('admin can show and refresh any device', function () {
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    expect(can($admin, 'configBackupView', $this->accessible))->toBeTrue();
    expect(can($admin, 'configBackupView', $this->other))->toBeTrue();
    expect(can($admin, 'configBackupRefresh', $this->accessible))->toBeTrue();
    expect(can($admin, 'configBackupRefresh', $this->other))->toBeTrue();
});

test('global read needs explicit config backup permissions', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('global-read');

    expect(can($user, 'configBackupView', $this->accessible))->toBeFalse();
    expect(can($user, 'configBackupView', $this->other))->toBeFalse();
    expect(can($user, 'configBackupRefresh', $this->accessible))->toBeFalse();
    expect(can($user, 'configBackupRefresh', $this->other))->toBeFalse();
});

test('global read with explicit config backup permissions can show and refresh any device', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('global-read');
    $user->givePermissionTo('config-backup.view', 'config-backup.refresh');

    // global-read's view access covers any device, including ones the user does not own
    expect(can($user, 'configBackupView', $this->accessible))->toBeTrue();
    expect(can($user, 'configBackupView', $this->other))->toBeTrue();
    expect(can($user, 'configBackupRefresh', $this->accessible))->toBeTrue();
    expect(can($user, 'configBackupRefresh', $this->other))->toBeTrue();
});

test('user is restricted to accessible devices', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->givePermissionTo('config-backup.view', 'config-backup.refresh');
    $user->devicesOwned()->attach($this->accessible->device_id);

    // device the user can access
    expect(can($user, 'configBackupView', $this->accessible))->toBeTrue();
    expect(can($user, 'configBackupRefresh', $this->accessible))->toBeTrue();

    // device the user cannot access — must be denied
    expect(can($user, 'configBackupView', $this->other))->toBeFalse();
    expect(can($user, 'configBackupRefresh', $this->other))->toBeFalse();
});

test('user without config permission cannot show or refresh', function () {
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->devicesOwned()->attach($this->accessible->device_id);

    expect(can($user, 'configBackupView', $this->accessible))->toBeFalse();
    expect(can($user, 'configBackupRefresh', $this->accessible))->toBeFalse();
});

test('customer role is restricted to accessible devices', function () {
    $customer = Role::findOrCreate('customer');
    $customer->givePermissionTo('config-backup.view', 'config-backup.refresh', 'device.view');

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('customer');
    $user->devicesOwned()->attach($this->accessible->device_id);

    expect(can($user, 'configBackupView', $this->accessible))->toBeTrue();
    expect(can($user, 'configBackupRefresh', $this->accessible))->toBeTrue();

    expect(can($user, 'configBackupView', $this->other))->toBeFalse();
    expect(can($user, 'configBackupRefresh', $this->other))->toBeFalse();
});