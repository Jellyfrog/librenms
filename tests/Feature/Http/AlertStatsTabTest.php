<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

test('authorized user can render alert stats tab', function () {
    $device = Device::factory()->create();

    $ruleId = DB::table('alert_rules')->insertGetId([
        'severity' => 'critical',
        'extra' => '',
        'disabled' => 0,
        'name' => 'Test Rule',
        'query' => '',
        'builder' => '',
    ]);

    DB::table('alert_log')->insert([
        'device_id' => $device->device_id,
        'rule_id' => $ruleId,
        'state' => 1,
        'time_logged' => now(),
        'details' => '',
    ]);

    $this->actingAs(admin())
        ->get(route('device.faults.stats', $device->device_id))
        ->assertOk()
        ->assertSee('Alert Stats')
        ->assertSee('critical');
});

test('legacy url redirects', function () {
    $device = Device::factory()->create();

    $this->actingAs(admin())
        ->get("/device/$device->device_id/alert-stats")
        ->assertRedirect(route('device.faults.stats', $device->device_id));
});

test('user without access gets forbidden', function () {
    $device = Device::factory()->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('device.faults.stats', $device->device_id))
        ->assertForbidden();
});

test('global stats only count accessible devices', function () {
    LibrenmsConfig::set('alert_graph_date_format', '%Y-%m-%d');
    $device = Device::factory()->create();
    $critical = AlertRule::factory()->create(['severity' => 'critical']);
    $warning = AlertRule::factory()->create(['severity' => 'warning']);
    AlertLog::factory()->create(['device_id' => $device->device_id, 'rule_id' => $critical->id, 'state' => 1, 'time_logged' => '2026-10-01 12:00:00']);
    AlertLog::factory()->create(['device_id' => $device->device_id, 'rule_id' => $critical->id, 'state' => 0, 'time_logged' => '2026-10-01 13:00:00']);
    // cleared, not counted
    AlertLog::factory()->create(['rule_id' => $warning->id, 'state' => 1, 'time_logged' => '2026-10-02 12:00:00']);

    // another device
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->devicesOwned()->attach($device->device_id);

    $stats = $this->actingAs($user)->get(route('alert-stats'))->assertOk()->viewData('stats');
    expect($stats['groups'])->toBe(['critical']);
    expect($stats['items'])->toBe([['x' => '2026-10-01', 'y' => 1, 'group' => 'critical']]);

    $stats = $this->actingAs(admin())->get(route('alert-stats'))->assertOk()->viewData('stats');
    expect($stats['groups'])->toEqualCanonicalizing(['critical', 'warning']);
});