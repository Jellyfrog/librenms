<?php

/**
 * ApiWriteAuthorizationTest.php
 *
 * Verifies that write operations on devices, components and bills are scoped
 * to the resources the user can access, not just the global permission.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 LibreNMS
 */

use App\Models\Bill;
use App\Models\Component;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(LibreNMS\Tests\DBTestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('user');
    $this->accessible = Device::factory()->create();
    $this->other = Device::factory()->create();
});

/**
 * @param  string[]  $permissions
 */
function apiWriteLimitedUser(array $permissions): User
{
    /** @var User $user */
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }
    $user->givePermissionTo($permissions);
    $user->devicesOwned()->attach(test()->accessible->device_id);

    return $user;
}

/**
 * @return array{X-Auth-Token: string}
 */
function apiWriteHeaders(User $user): array
{
    return ['X-Auth-Token' => $user->createToken('test')->plainTextToken];
}

test('device policy update and delete are scoped to accessible devices', function (): void {
    $user = apiWriteLimitedUser(['device.update', 'device.delete']);

    expect(Gate::forUser($user)->allows('update', Device::class))->toBeTrue();
    expect(Gate::forUser($user)->allows('update', $this->accessible))->toBeTrue();
    expect(Gate::forUser($user)->allows('update', $this->other))->toBeFalse();
    expect(Gate::forUser($user)->allows('delete', $this->accessible))->toBeTrue();
    expect(Gate::forUser($user)->allows('delete', $this->other))->toBeFalse();
});

test('api device writes deny inaccessible device', function (): void {
    $user = apiWriteLimitedUser(['device.update', 'device.delete']);
    $headers = apiWriteHeaders($user);
    $other = $this->other->hostname;

    $this->json('PATCH', "/api/v0/devices/$other", ['field' => 'purpose', 'data' => 'changed'], $headers)->assertStatus(403);
    $this->json('PATCH', "/api/v0/devices/$other/rename/renamed.example.com", [], $headers)->assertStatus(403);
    $this->json('POST', "/api/v0/devices/$other/maintenance", ['duration' => '1:00'], $headers)->assertStatus(403);
    $this->json('POST', "/api/v0/devices/{$this->other->device_id}/parents", ['parent_ids' => (string) $this->accessible->device_id], $headers)->assertStatus(403);
    $this->json('DELETE', "/api/v0/devices/{$this->other->device_id}/parents", [], $headers)->assertStatus(403);
    $this->json('DELETE', "/api/v0/devices/$other", [], $headers)->assertStatus(403);

    $this->other->refresh();
    $this->assertNotEquals('changed', $this->other->purpose);
    expect($this->other->hostname)->toBe($other);
    expect($this->other->parents()->doesntExist())->toBeTrue();

    $this->json('PATCH', "/api/v0/devices/{$this->accessible->hostname}", ['field' => 'purpose', 'data' => 'changed'], $headers)->assertStatus(200);
    expect($this->accessible->refresh()->purpose)->toBe('changed');
});

test('component writes are scoped to accessible devices', function (): void {
    $user = apiWriteLimitedUser(['component.update', 'component.delete']);
    $headers = apiWriteHeaders($user);
    $own = Component::factory()->create(['device_id' => $this->accessible->device_id, 'label' => 'original']);
    $foreign = Component::factory()->create(['device_id' => $this->other->device_id, 'label' => 'original']);

    // foreign component addressed through an accessible device is ignored
    $this->json('PUT', "/api/v0/devices/{$this->accessible->hostname}/components", [$foreign->id => ['label' => 'tampered']], $headers)
        ->assertStatus(200);
    expect($foreign->refresh()->label)->toBe('original');

    // foreign component addressed through its own device is denied
    $this->json('PUT', "/api/v0/devices/{$this->other->hostname}/components", [$foreign->id => ['label' => 'tampered']], $headers)
        ->assertStatus(403);
    expect($foreign->refresh()->label)->toBe('original');

    // device cannot be changed
    $this->json('PUT', "/api/v0/devices/{$this->accessible->hostname}/components", [$own->id => ['label' => 'changed', 'device_id' => $this->other->device_id]], $headers)
        ->assertStatus(200);
    $own->refresh();
    expect($own->label)->toBe('changed');
    expect($own->device_id)->toBe($this->accessible->device_id);

    $this->json('DELETE', "/api/v0/devices/{$this->accessible->hostname}/components/$foreign->id", [], $headers)->assertStatus(404);
    $this->json('DELETE', "/api/v0/devices/{$this->other->hostname}/components/$foreign->id", [], $headers)->assertStatus(403);
    expect(Component::whereKey($foreign->id)->exists())->toBeTrue();

    $this->json('DELETE', "/api/v0/devices/{$this->accessible->hostname}/components/$own->id", [], $headers)->assertStatus(200);
    expect(Component::whereKey($own->id)->exists())->toBeFalse();
});

test('bill writes are scoped to accessible bills', function (): void {
    $user = apiWriteLimitedUser(['bill.create', 'bill.update', 'bill.delete']);
    $headers = apiWriteHeaders($user);
    $own = Bill::factory()->create(['bill_notes' => 'original']);
    $foreign = Bill::factory()->create(['bill_notes' => 'original']);
    $user->bills()->attach($own->bill_id);

    $this->json('POST', '/api/v0/bills', ['bill_id' => $foreign->bill_id, 'bill_notes' => 'tampered'], $headers)->assertStatus(403);
    expect($foreign->refresh()->bill_notes)->toBe('original');

    $this->json('DELETE', "/api/v0/bills/$foreign->bill_id", [], $headers)->assertStatus(403);
    expect(Bill::whereKey($foreign->bill_id)->exists())->toBeTrue();

    $this->json('POST', '/api/v0/bills', ['bill_id' => $own->bill_id, 'bill_notes' => 'changed'], $headers)->assertStatus(200);
    expect($own->refresh()->bill_notes)->toBe('changed');

    $this->json('DELETE', "/api/v0/bills/$own->bill_id", [], $headers)->assertStatus(200);
    expect(Bill::whereKey($own->bill_id)->exists())->toBeFalse();
});