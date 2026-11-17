<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\PrinterSupply;
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

test('authorized user can render printer tab', function () {
    $device = Device::factory()->create();
    PrinterSupply::factory()->for($device)->create();

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'printer']))
        ->assertOk()
        ->assertSee('Toner')
        ->assertSee('device_toner')
        ->assertSee('legend=yes');
});

test('user without access gets forbidden', function () {
    $device = Device::factory()->create();
    PrinterSupply::factory()->for($device)->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device', ['device' => $device, 'tab' => 'printer']))
        ->assertForbidden();
});