<?php

/**
 * ApiResourceWriteAuthorizationTest.php
 *
 * Verifies that legacy API writes to alerts, services, bgp peers and bill ports
 * are scoped to the devices and ports the user can access.
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

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Alert;
use App\Models\AlertFault;
use App\Models\AlertRule;
use App\Models\BgpPeer;
use App\Models\BillPort;
use App\Models\Device;
use App\Models\Port;
use App\Models\Service;
use App\Models\User;
use LibreNMS\Enum\AlertState;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    LibrenmsConfig::set('show_services', true);
    Role::findOrCreate('user');
    $this->accessible = Device::factory()->create();
    $this->other = Device::factory()->create();
});

/**
 * @param  string[]  $permissions
 * @return array{X-Auth-Token: string}
 */
function limitedUserHeaders(array $permissions): array
{
    $this->app['auth']->forgetGuards();

    // don't reuse the user resolved by a previous request
    /** @var User $user */
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission);
    }
    $user->givePermissionTo($permissions);
    $user->devicesOwned()->attach($this->accessible->device_id);

    return ['X-Auth-Token' => $user->createToken('test')->plainTextToken];
}

function createFault(Device $device): AlertFault
{
    $rule = AlertRule::factory()->create();
    Alert::factory()->create(['device_id' => $device->device_id, 'rule_id' => $rule->id, 'state' => AlertState::ACTIVE, 'open' => 1]);

    return AlertFault::create([
        'rule_id' => $rule->id,
        'device_id' => $device->device_id,
        'entity_key' => 'device',
        'state' => AlertState::ACTIVE,
        'open' => 1,
        'alerted' => 1,
        'details' => ['rule' => [], 'contacts' => []],
    ]);
}

test('alert writes are scoped to accessible devices', function () {
    $headers = limitedUserHeaders(['alert.update']);
    $own = createFault($this->accessible);
    $foreign = createFault($this->other);

    $this->json('PUT', "/api/v0/alerts/$foreign->id", ['note' => 'x', 'until_clear' => false], $headers)->assertStatus(403);
    $this->json('PUT', "/api/v0/alerts/unmute/$foreign->id", ['note' => 'x'], $headers)->assertStatus(403);
    expect($foreign->refresh()->state)->toBe(AlertState::ACTIVE);

    $this->json('PUT', "/api/v0/alerts/$own->id", ['note' => 'x', 'until_clear' => false], $headers)->assertStatus(200);
    expect($own->refresh()->state)->toBe(AlertState::ACKNOWLEDGED);
    $this->json('PUT', "/api/v0/alerts/unmute/$own->id", ['note' => 'x'], $headers)->assertStatus(200);
    expect($own->refresh()->state)->toBe(AlertState::ACTIVE);

    $this->json('PUT', "/api/v0/alerts/$own->id", ['note' => 'x'], limitedUserHeaders([]))->assertStatus(403);
});

test('service writes are scoped to accessible devices', function () {
    $headers = limitedUserHeaders(['service.create', 'service.update', 'service.delete']);
    $own = Service::factory()->create(['device_id' => $this->accessible->device_id, 'service_desc' => 'original']);
    $foreign = Service::factory()->create(['device_id' => $this->other->device_id, 'service_desc' => 'original']);

    $this->json('PATCH', "/api/v0/services/$foreign->service_id", ['service_desc' => 'tampered'], $headers)->assertStatus(403);
    $this->json('DELETE', "/api/v0/services/$foreign->service_id", [], $headers)->assertStatus(403);
    expect($foreign->refresh()->service_desc)->toBe('original');

    $this->json('PATCH', "/api/v0/services/$own->service_id", ['device_id' => $this->other->device_id], $headers)->assertStatus(403);
    expect($own->refresh()->device_id)->toBe($this->accessible->device_id);

    $this->json('POST', "/api/v0/services/{$this->other->hostname}", ['type' => 'icmp'], $headers)->assertStatus(403);
    expect(Service::where('device_id', $this->other->device_id)->count())->toBe(1);

    $this->json('PATCH', "/api/v0/services/$own->service_id", ['service_desc' => 'changed'], $headers)->assertStatus(201);
    expect($own->refresh()->service_desc)->toBe('changed');
    $this->json('DELETE', "/api/v0/services/$own->service_id", [], $headers)->assertStatus(201);
    expect(Service::whereKey($own->service_id)->exists())->toBeFalse();

    $readOnly = limitedUserHeaders([]);
    $this->json('PATCH', "/api/v0/services/$foreign->service_id", ['service_desc' => 'tampered'], $readOnly)->assertStatus(403);
    $this->json('DELETE', "/api/v0/services/$foreign->service_id", [], $readOnly)->assertStatus(403);
});

test('bgp description edit is scoped to accessible devices', function () {
    $headers = limitedUserHeaders(['routing.update']);
    $own = BgpPeer::factory()->create(['device_id' => $this->accessible->device_id, 'bgpPeerDescr' => 'original']);
    $foreign = BgpPeer::factory()->create(['device_id' => $this->other->device_id, 'bgpPeerDescr' => 'original']);

    $this->json('POST', "/api/v0/bgp/$foreign->bgpPeer_id", ['bgp_descr' => 'tampered'], $headers)->assertStatus(403);
    expect($foreign->refresh()->bgpPeerDescr)->toBe('original');

    $this->json('POST', "/api/v0/bgp/$own->bgpPeer_id", ['bgp_descr' => 'changed'], $headers)->assertStatus(200);
    expect($own->refresh()->bgpPeerDescr)->toBe('changed');
});

test('bill create only attaches accessible ports', function () {
    $headers = limitedUserHeaders(['bill.create']);
    $ownPort = Port::factory()->for($this->accessible)->create();
    $foreignPort = Port::factory()->for($this->other)->create();
    $bill = [
        'bill_name' => 'test',
        'bill_type' => 'cdr',
        'bill_cdr' => 1000,
        'bill_day' => 1,
        'bill_custid' => '',
        'bill_ref' => '',
        'bill_notes' => '',
        'dir_95th' => 'in',
    ];

    $this->json('POST', '/api/v0/bills', $bill + ['ports' => [$ownPort->port_id, $foreignPort->port_id]], $headers)->assertStatus(403);
    expect(BillPort::where('port_id', $foreignPort->port_id)->exists())->toBeFalse();

    $this->json('POST', '/api/v0/bills', $bill + ['ports' => [$ownPort->port_id]], $headers)->assertStatus(200);
    expect(BillPort::where('port_id', $ownPort->port_id)->exists())->toBeTrue();
});