<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Storage;
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

function user(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

test('admin can view storage page', function () {
    $device = Device::factory()->create();
    Storage::factory()->for($device)->create([
        'storage_descr' => '/data <b>disk</b>',
        'storage_size' => 1073741824,
        'storage_perc' => 42,
        'storage_perc_warn' => 75,
    ]);

    $this->actingAs(admin())
        ->get(route('device.edit.storage', $device))
        ->assertOk()
        ->assertSee('/data &lt;b&gt;disk&lt;/b&gt;', false)
        ->assertDontSee('/data <b>disk</b>', false)
        ->assertSee('1 GiB')
        ->assertSee('42%')
        ->assertSee('value="75"', false);
});

test('null warn threshold renders empty', function () {
    $device = Device::factory()->create();
    $storage = Storage::factory()->for($device)->create();
    Storage::whereKey($storage->storage_id)->update(['storage_perc_warn' => null]);

    $this->actingAs(admin())
        ->get(route('device.edit.storage', $device))
        ->assertOk()
        ->assertSee('value=""', false);
});

test('user cannot view storage page', function () {
    $device = Device::factory()->create();

    $this->actingAs(user())
        ->get(route('device.edit.storage', $device))
        ->assertForbidden();
});

test('admin can update warn threshold', function () {
    $device = Device::factory()->create();
    $storage = Storage::factory()->for($device)->create(['storage_perc_warn' => 60]);

    $this->actingAs(admin())
        ->postJson(route('device.edit.storage.update', [$device, $storage]), ['storage_perc_warn' => '90'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect($storage->fresh()->storage_perc_warn)->toBe(90);
});

test('empty warn threshold clears value', function () {
    $device = Device::factory()->create();
    $storage = Storage::factory()->for($device)->create(['storage_perc_warn' => 60]);

    $this->actingAs(admin())
        ->postJson(route('device.edit.storage.update', [$device, $storage]), ['storage_perc_warn' => ''])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect($storage->fresh()->storage_perc_warn)->toBeNull();
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
    $storage = Storage::factory()->for($device)->create(['storage_perc_warn' => 60]);

    $this->actingAs(admin())
        ->postJson(route('device.edit.storage.update', [$device, $storage]), ['storage_perc_warn' => $value])
        ->assertUnprocessable();

    expect($storage->fresh()->storage_perc_warn)->toBe(60);
});

test('storage must belong to device', function () {
    $device = Device::factory()->create();
    $otherStorage = Storage::factory()->create(['storage_perc_warn' => 60]);

    $this->actingAs(admin())
        ->postJson(route('device.edit.storage.update', [$device, $otherStorage]), ['storage_perc_warn' => 90])
        ->assertNotFound();

    expect($otherStorage->fresh()->storage_perc_warn)->toBe(60);
});

test('user cannot update warn threshold', function () {
    $device = Device::factory()->create();
    $storage = Storage::factory()->for($device)->create(['storage_perc_warn' => 60]);

    $this->actingAs(user())
        ->postJson(route('device.edit.storage.update', [$device, $storage]), ['storage_perc_warn' => 90])
        ->assertForbidden();

    expect($storage->fresh()->storage_perc_warn)->toBe(60);
});