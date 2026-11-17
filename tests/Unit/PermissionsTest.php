<?php

/**
 * PermissionsTest.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2019 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Models\Bill;
use App\Models\Device;
use App\Models\Port;
use App\Models\User;

uses(LibreNMS\Tests\TestCase::class);
function devicePermissionData($user)
{
    $user_id = $user instanceof User ? $user->user_id : (is_numeric($user) ? (int) $user : Auth::id());

    $data = null;

    switch ($user_id) {
        case 43:
            $data = [
                (object) ['user_id' => 43, 'device_id' => 54],
                (object) ['user_id' => 43, 'device_id' => 32],
            ];
            break;
        case 14:
            $data = [
                (object) ['user_id' => 14, 'device_id' => 54],
            ];
            break;
    }

    return new Illuminate\Support\Collection($data);
}

test('user can access device', function (): void {
    $perms = Mockery::mock(LibreNMS\Cache\PermissionsCache::class)->makePartial();
    $perms->shouldReceive('getDevicePermissions')->andReturnUsing(fn ($user) => devicePermissionData($user));

    $device = Device::factory()->make(['device_id' => 54]);
    /** @var Device $device */
    $user = User::factory()->make(['user_id' => 43]);
    /** @var User $user */
    expect($perms->canAccessDevice($device, 43))->toBeTrue();
    expect($perms->canAccessDevice($device, $user))->toBeTrue();
    expect($perms->canAccessDevice(54, $user))->toBeTrue();
    expect($perms->canAccessDevice(54, 43))->toBeTrue();
    expect($perms->canAccessDevice(54, 43))->toBeTrue();
    expect($perms->canAccessDevice(54, 23))->toBeFalse();
    expect($perms->canAccessDevice(23, 43))->toBeFalse();
    expect($perms->canAccessDevice(54))->toBeFalse();

    Auth::shouldReceive('id')->once()->andReturn(43);
    expect($perms->canAccessDevice(54))->toBeTrue();
    Auth::shouldReceive('id')->once()->andReturn(23);
    expect($perms->canAccessDevice(54))->toBeFalse();
});

test('devices for user', function (): void {
    $perms = Mockery::mock(LibreNMS\Cache\PermissionsCache::class)->makePartial();
    $perms->shouldReceive('getDevicePermissions')->andReturnUsing(fn ($user) => devicePermissionData($user));

    expect($perms->devicesForUser(43))->toEqual(collect([54, 32]));
    $user = User::factory()->make(['user_id' => 43]);
    /** @var User $user */
    expect($perms->devicesForUser($user))->toEqual(collect([54, 32]));
    expect($perms->devicesForUser(9))->toBeEmpty();
    expect($perms->devicesForUser())->toEqual(collect());
    Auth::shouldReceive('id')->once()->andReturn(14);
    expect($perms->devicesForUser())->toEqual(collect([54]));
});

test('user can access port', function (): void {
    $perms = Mockery::mock(LibreNMS\Cache\PermissionsCache::class)->makePartial();
    $perms->shouldReceive('getPortPermissions')->andReturn(collect([
        (object) ['user_id' => 43, 'port_id' => 54],
        (object) ['user_id' => 43, 'port_id' => 32],
        (object) ['user_id' => 14, 'port_id' => 54],
    ]));

    $port = Port::factory()->make(['port_id' => 54]);
    /** @var Port $port */
    $user = User::factory()->make(['user_id' => 43]);
    /** @var User $user */
    expect($perms->canAccessPort($port, 43))->toBeTrue();
    expect($perms->canAccessPort($port, $user))->toBeTrue();
    expect($perms->canAccessPort(54, $user))->toBeTrue();
    expect($perms->canAccessPort(54, 43))->toBeTrue();
    expect($perms->canAccessPort(54, 43))->toBeTrue();
    expect($perms->canAccessPort(54, 23))->toBeFalse();
    expect($perms->canAccessPort(23, 43))->toBeFalse();
    expect($perms->canAccessPort(54))->toBeFalse();

    Auth::shouldReceive('id')->once()->andReturn(43);
    expect($perms->canAccessPort(54))->toBeTrue();
    Auth::shouldReceive('id')->once()->andReturn(23);
    expect($perms->canAccessPort(54))->toBeFalse();
});

test('ports for user', function (): void {
    $perms = Mockery::mock(LibreNMS\Cache\PermissionsCache::class)->makePartial();
    $perms->shouldReceive('getPortPermissions')->andReturn(collect([
        (object) ['user_id' => 3, 'port_id' => 7],
        (object) ['user_id' => 3, 'port_id' => 2],
        (object) ['user_id' => 4, 'port_id' => 5],
    ]));

    expect($perms->portsForUser(3))->toEqual(collect([7, 2]));
    $user = User::factory()->make(['user_id' => 3]);
    /** @var User $user */
    expect($perms->portsForUser($user))->toEqual(collect([7, 2]));
    expect($perms->portsForUser(9))->toBeEmpty();
    expect($perms->portsForUser())->toEqual(collect());
    Auth::shouldReceive('id')->once()->andReturn(4);
    expect($perms->portsForUser())->toEqual(collect([5]));
});

test('users for port', function (): void {
    $perms = Mockery::mock(LibreNMS\Cache\PermissionsCache::class)->makePartial();
    $perms->shouldReceive('getPortPermissions')->andReturn(collect([
        (object) ['user_id' => 3, 'port_id' => 7],
        (object) ['user_id' => 3, 'port_id' => 2],
        (object) ['user_id' => 4, 'port_id' => 5],
        (object) ['user_id' => 6, 'port_id' => 5],
    ]));

    $port = Port::factory()->make(['port_id' => 7]);
    /** @var Port $port */
    expect($perms->usersForPort(5))->toEqual(collect([4, 6]));
    expect($perms->usersForPort($port))->toEqual(collect([3]));
    expect($perms->usersForPort(6))->toEqual(collect());
    expect($perms->usersForPort(9))->toBeEmpty();
});

test('user can access bill', function (): void {
    $perms = Mockery::mock(LibreNMS\Cache\PermissionsCache::class)->makePartial();
    $perms->shouldReceive('getBillPermissions')->andReturn(collect([
        (object) ['user_id' => 43, 'bill_id' => 54],
        (object) ['user_id' => 43, 'bill_id' => 32],
        (object) ['user_id' => 14, 'bill_id' => 54],
    ]));

    $bill = Bill::factory()->make(['bill_id' => 54]);
    /** @var Bill $bill */
    $user = User::factory()->make(['user_id' => 43]);
    /** @var User $user */
    expect($perms->canAccessBill($bill, 43))->toBeTrue();
    expect($perms->canAccessBill($bill, $user))->toBeTrue();
    expect($perms->canAccessBill(54, $user))->toBeTrue();
    expect($perms->canAccessBill(54, 43))->toBeTrue();
    expect($perms->canAccessBill(54, 43))->toBeTrue();
    expect($perms->canAccessBill(54, 23))->toBeFalse();
    expect($perms->canAccessBill(23, 43))->toBeFalse();
    expect($perms->canAccessBill(54))->toBeFalse();

    Auth::shouldReceive('id')->once()->andReturn(43);
    expect($perms->canAccessBill(54))->toBeTrue();
    Auth::shouldReceive('id')->once()->andReturn(23);
    expect($perms->canAccessBill(54))->toBeFalse();
});

test('bills for user', function (): void {
    $perms = Mockery::mock(LibreNMS\Cache\PermissionsCache::class)->makePartial();
    $perms->shouldReceive('getBillPermissions')->andReturn(collect([
        (object) ['user_id' => 3, 'bill_id' => 7],
        (object) ['user_id' => 3, 'bill_id' => 2],
        (object) ['user_id' => 4, 'bill_id' => 5],
    ]));

    expect($perms->billsForUser(3))->toEqual(collect([7, 2]));
    $user = User::factory()->make(['user_id' => 3]);
    /** @var User $user */
    expect($perms->billsForUser($user))->toEqual(collect([7, 2]));
    expect($perms->billsForUser(9))->toBeEmpty();
    expect($perms->billsForUser())->toEqual(collect());
    Auth::shouldReceive('id')->once()->andReturn(4);
    expect($perms->billsForUser())->toEqual(collect([5]));
});

test('users for bill', function (): void {
    $perms = Mockery::mock(LibreNMS\Cache\PermissionsCache::class)->makePartial();
    $perms->shouldReceive('getBillPermissions')->andReturn(collect([
        (object) ['user_id' => 3, 'bill_id' => 7],
        (object) ['user_id' => 3, 'bill_id' => 2],
        (object) ['user_id' => 4, 'bill_id' => 5],
        (object) ['user_id' => 6, 'bill_id' => 5],
    ]));

    expect($perms->usersForBill(5))->toEqual(collect([4, 6]));
    $bill = Bill::factory()->make(['bill_id' => 7]);
    /** @var Bill $bill */
    expect($perms->usersForBill($bill))->toEqual(collect([3]));
    expect($perms->usersForBill(6))->toEqual(collect());
    expect($perms->usersForBill(9))->toBeEmpty();
});
