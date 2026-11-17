<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\MefInfo;
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

test('authorized user can render mef tab', function () {
    $device = Device::factory()->create();
    MefInfo::factory()->for($device)->create([
        'mefIdent' => 'EPL-Cust-101',
        'mefType' => 'epl',
        'mefMTU' => 9000,
        'mefAdmState' => 'unlocked',
        'mefRowState' => 'active',
    ]);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'mef']))
        ->assertOk()
        ->assertSee('EPL-Cust-101')
        ->assertSee('epl')
        ->assertSee('9000')
        ->assertSee('fa-unlock')
        ->assertSee('active');
});

test('user without access gets forbidden', function () {
    $device = Device::factory()->create();
    MefInfo::factory()->for($device)->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device', ['device' => $device, 'tab' => 'mef']))
        ->assertForbidden();
});