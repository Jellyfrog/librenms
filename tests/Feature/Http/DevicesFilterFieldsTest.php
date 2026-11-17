<?php

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('filters follow permissions', function (): void {
    LibrenmsConfig::set('distributed_poller', true);
    Role::findOrCreate('admin');

    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');
    expect(array_values(array_intersect(deviceFilterKeys($admin), ['location_id', 'groups.id', 'poller_group'])))->toBe(['location_id', 'groups.id', 'poller_group']);

    // no permissions, only filters on the devices themselves
    $user = User::factory()->create(['enabled' => 1]);
    $keys = deviceFilterKeys($user);
    expect($keys)->not->toContain('groups.id');
    expect($keys)->not->toContain('poller_group');
    expect($keys)->toContain('type');
    expect($keys)->toContain('disabled');
});

/**
 * @return string[]
 */
function deviceFilterKeys(User $user): array
{
    $response = test()->actingAs($user)->get(route('devices'));
    $response->assertOk();

    return array_column($response->viewData('filterFields'), 'key');
}
