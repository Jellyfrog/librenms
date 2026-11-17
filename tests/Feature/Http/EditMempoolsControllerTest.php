<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Mempool;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
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

function user(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

test('admin can view mempools page', function () {
    $device = Device::factory()->create();
    Mempool::factory()->for($device)->create([
        'mempool_descr' => 'Physical <b>Memory</b>',
        'mempool_perc' => 42,
        'mempool_perc_warn' => 85,
    ]);

    $this->actingAs(admin())
        ->get(route('device.edit.mempools', $device))
        ->assertOk()
        ->assertSee('Physical &lt;b&gt;Memory&lt;/b&gt;', false)
        ->assertDontSee('Physical <b>Memory</b>', false)
        ->assertSee('42%')
        ->assertSee('value="85"', false);
});

test('null warn threshold renders empty', function () {
    $device = Device::factory()->create();
    Mempool::factory()->for($device)->create(['mempool_perc_warn' => null]);

    $this->actingAs(admin())
        ->get(route('device.edit.mempools', $device))
        ->assertOk()
        ->assertSee('value=""', false);
});

test('user cannot view mempools page', function () {
    $device = Device::factory()->create();

    $this->actingAs(user())
        ->get(route('device.edit.mempools', $device))
        ->assertForbidden();
});

test('admin can update warn threshold', function () {
    $device = Device::factory()->create();
    $mempool = Mempool::factory()->for($device)->create(['mempool_perc_warn' => 75]);

    $this->actingAs(admin())
        ->postJson(route('device.edit.mempools.update', [$device, $mempool]), ['mempool_perc_warn' => '90'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect($mempool->fresh()->mempool_perc_warn)->toBe(90);
});

test('empty warn threshold clears it', function () {
    $device = Device::factory()->create();
    $mempool = Mempool::factory()->for($device)->create(['mempool_perc_warn' => 75]);

    $this->actingAs(admin())
        ->postJson(route('device.edit.mempools.update', [$device, $mempool]), ['mempool_perc_warn' => ''])
        ->assertOk();

    expect($mempool->fresh()->mempool_perc_warn)->toBeNull();
});

/**
 * @return array<string, array{mixed}>
 */
dataset('invalidThresholds', function () {
    return [
        'non-numeric' => ['abc'],
        'negative' => [-1],
        'over 100' => [101],
    ];
});

test('invalid warn threshold is rejected', function (mixed $value) {
    $device = Device::factory()->create();
    $mempool = Mempool::factory()->for($device)->create(['mempool_perc_warn' => 75]);

    $this->actingAs(admin())
        ->postJson(route('device.edit.mempools.update', [$device, $mempool]), ['mempool_perc_warn' => $value])
        ->assertUnprocessable();

    expect($mempool->fresh()->mempool_perc_warn)->toBe(75);
})->with('invalidThresholds');

test('mempool must belong to device', function () {
    $device = Device::factory()->create();
    $otherMempool = Mempool::factory()->create(['mempool_perc_warn' => 75]);

    $this->actingAs(admin())
        ->postJson(route('device.edit.mempools.update', [$device, $otherMempool]), ['mempool_perc_warn' => 90])
        ->assertNotFound();

    expect($otherMempool->fresh()->mempool_perc_warn)->toBe(75);
});

test('user cannot update warn threshold', function () {
    $device = Device::factory()->create();
    $mempool = Mempool::factory()->for($device)->create(['mempool_perc_warn' => 75]);

    $this->actingAs(user())
        ->postJson(route('device.edit.mempools.update', [$device, $mempool]), ['mempool_perc_warn' => 90])
        ->assertForbidden();

    expect($mempool->fresh()->mempool_perc_warn)->toBe(75);
});