<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function discoveryStatusAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

test('undiscovered device reports not discovered', function (): void {
    $device = Device::factory()->create(['last_discovered' => null]);

    $this->actingAs(discoveryStatusAdmin())
        ->getJson(route('device.discovery-status', $device))
        ->assertOk()
        ->assertExactJson(['discovered' => false]);
});

test('discovered device reports discovered', function (): void {
    $device = Device::factory()->create(['last_discovered' => now()]);

    $this->actingAs(discoveryStatusAdmin())
        ->getJson(route('device.discovery-status', $device))
        ->assertOk()
        ->assertExactJson(['discovered' => true]);
});

test('user without access gets forbidden', function (): void {
    $device = Device::factory()->create();

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    $this->actingAs($user)
        ->getJson(route('device.discovery-status', $device))
        ->assertForbidden();
});

test('device page shows awaiting discovery notice', function (): void {
    $device = Device::factory()->create(['last_discovered' => null]);

    $this->actingAs(discoveryStatusAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'notes']))
        ->assertOk()
        ->assertSee(__('device.awaiting_discovery'))
        ->assertSee(route('device.discovery-status', $device->device_id), false)
        ->assertSee('window.location.reload()', false)
        ->assertDontSee(__('device.discovery_complete'));
});

test('device edit page shows click to refresh notice', function (): void {
    $device = Device::factory()->create(['last_discovered' => null]);

    $this->actingAs(discoveryStatusAdmin())
        ->get(route('device.edit.misc', $device))
        ->assertOk()
        ->assertSee(__('device.awaiting_discovery_edit'))
        ->assertSee(__('device.discovery_complete'))
        ->assertDontSee(__('device.awaiting_discovery'))
        ->assertSee(route('device.discovery-status', $device->device_id), false);
});

test('device page hides notice once discovered', function (): void {
    $device = Device::factory()->create(['last_discovered' => now()]);

    $this->actingAs(discoveryStatusAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'notes']))
        ->assertOk()
        ->assertDontSee(__('device.awaiting_discovery'));
});

test('device edit page hides notice once discovered', function (): void {
    $device = Device::factory()->create(['last_discovered' => now()]);

    $this->actingAs(discoveryStatusAdmin())
        ->get(route('device.edit.misc', $device))
        ->assertOk()
        ->assertSee('x-cloak', false)
        ->assertSee('x-on:device-discovery-pending.window', false);
});

test('rediscover reports discovery pending', function (): void {
    $device = Device::factory()->create(['last_discovered' => now()]);

    $this->actingAs(discoveryStatusAdmin())
        ->postJson(route('device.rediscover', $device))
        ->assertOk()
        ->assertJson(['status' => 'ok', 'discovery_pending' => true]);

    expect($device->fresh()->last_discovered)->toBeNull();
});

test('rediscover disabled device is not pending', function (): void {
    $device = Device::factory()->create(['last_discovered' => now(), 'disabled' => 1]);

    $this->actingAs(discoveryStatusAdmin())
        ->postJson(route('device.rediscover', $device))
        ->assertOk()
        ->assertJson(['status' => 'ok', 'discovery_pending' => false]);
});

test('disabled device page hides notice', function (): void {
    $device = Device::factory()->create(['last_discovered' => null, 'disabled' => 1]);

    $this->actingAs(discoveryStatusAdmin())
        ->get(route('device', ['device' => $device, 'tab' => 'notes']))
        ->assertOk()
        ->assertDontSee(__('device.awaiting_discovery'));
});