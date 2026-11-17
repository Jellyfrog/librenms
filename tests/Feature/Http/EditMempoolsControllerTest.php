<?php

use App\Models\Device;
use App\Models\Mempool;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\LibreNMS\Tests\TestCase::class, \Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function mempoolsAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function mempoolsUser(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

test('admin can view mempools page', function (): void {
    $device = Device::factory()->create();
    Mempool::factory()->for($device)->create([
        'mempool_descr' => 'Physical <b>Memory</b>',
        'mempool_perc' => 42,
        'mempool_perc_warn' => 85,
    ]);

    $this->actingAs(mempoolsAdmin())
        ->get(route('device.edit.mempools', $device))
        ->assertOk()
        ->assertSee('Physical &lt;b&gt;Memory&lt;/b&gt;', false)
        ->assertDontSee('Physical <b>Memory</b>', false)
        ->assertSee('42%')
        ->assertSee('value="85"', false);
});

test('null warn threshold renders empty', function (): void {
    $device = Device::factory()->create();
    Mempool::factory()->for($device)->create(['mempool_perc_warn' => null]);

    $this->actingAs(mempoolsAdmin())
        ->get(route('device.edit.mempools', $device))
        ->assertOk()
        ->assertSee('value=""', false);
});

test('user cannot view mempools page', function (): void {
    $device = Device::factory()->create();

    $this->actingAs(mempoolsUser())
        ->get(route('device.edit.mempools', $device))
        ->assertForbidden();
});

test('admin can update warn threshold', function (): void {
    $device = Device::factory()->create();
    $mempool = Mempool::factory()->for($device)->create(['mempool_perc_warn' => 75]);

    $this->actingAs(mempoolsAdmin())
        ->postJson(route('device.edit.mempools.update', [$device, $mempool]), ['mempool_perc_warn' => '90'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect($mempool->fresh()->mempool_perc_warn)->toBe(90);
});

test('empty warn threshold clears it', function (): void {
    $device = Device::factory()->create();
    $mempool = Mempool::factory()->for($device)->create(['mempool_perc_warn' => 75]);

    $this->actingAs(mempoolsAdmin())
        ->postJson(route('device.edit.mempools.update', [$device, $mempool]), ['mempool_perc_warn' => ''])
        ->assertOk();

    expect($mempool->fresh()->mempool_perc_warn)->toBeNull();
});

/**
 * @return array<string, array{mixed}>
 */
dataset('invalidThresholds', function (): array {
    return [
        'non-numeric' => ['abc'],
        'negative' => [-1],
        'over 100' => [101],
    ];
});

test('invalid warn threshold is rejected', function (mixed $value): void {
    $device = Device::factory()->create();
    $mempool = Mempool::factory()->for($device)->create(['mempool_perc_warn' => 75]);

    $this->actingAs(mempoolsAdmin())
        ->postJson(route('device.edit.mempools.update', [$device, $mempool]), ['mempool_perc_warn' => $value])
        ->assertUnprocessable();

    expect($mempool->fresh()->mempool_perc_warn)->toBe(75);
})->with('invalidThresholds');

test('mempool must belong to device', function (): void {
    $device = Device::factory()->create();
    $otherMempool = Mempool::factory()->create(['mempool_perc_warn' => 75]);

    $this->actingAs(mempoolsAdmin())
        ->postJson(route('device.edit.mempools.update', [$device, $otherMempool]), ['mempool_perc_warn' => 90])
        ->assertNotFound();

    expect($otherMempool->fresh()->mempool_perc_warn)->toBe(75);
});

test('user cannot update warn threshold', function (): void {
    $device = Device::factory()->create();
    $mempool = Mempool::factory()->for($device)->create(['mempool_perc_warn' => 75]);

    $this->actingAs(mempoolsUser())
        ->postJson(route('device.edit.mempools.update', [$device, $mempool]), ['mempool_perc_warn' => 90])
        ->assertForbidden();

    expect($mempool->fresh()->mempool_perc_warn)->toBe(75);
});