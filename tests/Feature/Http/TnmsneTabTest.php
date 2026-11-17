<?php

use App\Models\Device;
use App\Models\TnmsneInfo;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function tnmsneAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

test('authorized user can render tnmsne tab', function (): void {
    $device = Device::factory()->create(['os' => 'coriant']);
    TnmsneInfo::factory()->for($device)->create([
        'neName' => 'Coriant-Node-Alpha',
    ]);

    $this->actingAs(tnmsneAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'tnmsne']))
        ->assertOk()
        ->assertSee('Coriant NE Hardware')
        ->assertSee('tnmsne')
        ->assertSee(route('table.tnmsne'));
});

test('user without access gets forbidden', function (): void {
    $device = Device::factory()->create(['os' => 'coriant']);
    TnmsneInfo::factory()->for($device)->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device', ['device' => $device, 'tab' => 'tnmsne']))
        ->assertForbidden();
});