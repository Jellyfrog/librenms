<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Package;
use App\Models\User;
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

test('authorized user can render packages tab', function () {
    $device = Device::factory()->create();
    Package::factory()->for($device)->create([
        'name' => 'nginx-core',
        'version' => '1.24.0',
        'build' => '1ubuntu1',
        'arch' => 'amd64',
        'manager' => 'deb',
        'size' => 1048576,
    ]);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'packages']))
        ->assertOk()
        ->assertSee('nginx-core')
        ->assertSee('1.24.0-1ubuntu1')
        ->assertSee('amd64')
        ->assertSee('deb')
        ->assertSee(\LibreNMS\Util\Number::formatSi(1048576, 2, 0, ''));
});

test('user without access gets forbidden', function () {
    $device = Device::factory()->create();
    Package::factory()->for($device)->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device', ['device' => $device, 'tab' => 'packages']))
        ->assertForbidden();
});