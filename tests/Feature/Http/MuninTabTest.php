<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\MuninPlugin;
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

test('authorized user can render munin tab', function () {
    $device = Device::factory()->create();
    MuninPlugin::factory()->for($device)->create([
        'mplug_category' => 'system',
        'mplug_type' => 'cpu',
        'mplug_title' => 'CPU Usage',
    ]);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'munin']))
        ->assertOk()
        ->assertSee('Munin Plugins')
        ->assertSee('System')
        ->assertSee('CPU Usage');
});

test('user without access gets forbidden', function () {
    $device = Device::factory()->create();
    MuninPlugin::factory()->for($device)->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device', ['device' => $device, 'tab' => 'munin']))
        ->assertForbidden();
});