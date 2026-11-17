<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Process;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function processesTabAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

test('authorized user can render processes tab', function () {
    $device = Device::factory()->create();
    Process::factory()->for($device)->create([
        'pid' => 1234,
        'vsz' => 2048,
        'rss' => 1024,
        'cputime' => '00:05:12',
        'user' => 'root',
        'command' => '/usr/sbin/snmpd -Lsd -Lf /dev/null',
    ]);

    $this->actingAs(processesTabAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'processes']))
        ->assertOk()
        ->assertSee('1234')
        ->assertSee('root')
        ->assertSee('00:05:12')
        ->assertSee('/usr/sbin/snmpd -Lsd -Lf /dev/null')
        ->assertSee(\LibreNMS\Util\Number::formatSi(2048 * 1024, 2, 0, ''));
});

test('authorized user can sort processes', function () {
    $device = Device::factory()->create();
    Process::factory()->for($device)->create([
        'pid' => 10,
        'user' => 'zebra',
    ]);
    Process::factory()->for($device)->create([
        'pid' => 20,
        'user' => 'alpha',
    ]);

    $response = $this->actingAs(processesTabAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'processes', 'order' => 'user', 'by' => 'asc']))
        ->assertOk();

    $content = $response->getContent();
    $alphaPos = strpos($content, 'alpha');
    $zebraPos = strpos($content, 'zebra');

    expect($alphaPos !== false && $zebraPos !== false && $alphaPos < $zebraPos)->toBeTrue();
});

test('invalid sort parameters fail validation', function () {
    $device = Device::factory()->create();
    Process::factory()->for($device)->create();

    $this->actingAs(processesTabAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'processes', 'order' => 'invalid_col', 'by' => 'invalid_dir']))
        ->assertSessionHasErrors(['order', 'by']);
});

test('user without access gets forbidden', function () {
    $device = Device::factory()->create();
    Process::factory()->for($device)->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device', ['device' => $device, 'tab' => 'processes']))
        ->assertForbidden();
});