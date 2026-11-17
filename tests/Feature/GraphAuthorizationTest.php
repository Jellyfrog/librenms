<?php

/**
 * GraphAuthorizationTest.php
 *
 * Verifies legacy graph auth includes only authorize devices the user may access.
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

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Location;
use App\Models\Port;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('user');

    $this->location = Location::factory()->create();
    $this->accessible = Device::factory()->create(['location_id' => $this->location->id]);
    $this->other = Device::factory()->create(['location_id' => $this->location->id]);

    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->devicesOwned()->attach($this->accessible->device_id);
    $this->actingAs($user);
});

/**
 * Run a graph type's auth include in an isolated scope.
 *
 * @param  array<string, mixed>  $vars
 * @return array{auth: bool, devices: array<int, array<string, mixed>>}
 */
function runAuth(string $type, array $vars, bool $auth = false): array
{
    $devices = [];
    require __DIR__ . "/../../includes/html/graphs/$type/auth.inc.php";

    return ['auth' => $auth, 'devices' => $devices];
}

test('location graph only includes permitted devices', function (): void {
    $result = runAuth('location', ['id' => $this->location->id]);

    expect($result['auth'])->toBeTrue();
    expect(array_column($result['devices'], 'device_id'))->toEqual([$this->accessible->device_id]);
});

test('location graph guest auth includes all devices', function (): void {
    $result = runAuth('location', ['id' => $this->location->id], true);

    expect($result['auth'])->toBeTrue();
    expect(array_column($result['devices'], 'device_id'))->toEqualCanonicalizing([$this->accessible->device_id, $this->other->device_id]);
});

test('location graph denied without permitted devices', function (): void {
    $otherLocation = Location::factory()->create();
    Device::factory()->create(['location_id' => $otherLocation->id]);

    $result = runAuth('location', ['id' => $otherLocation->id]);

    expect($result['auth'])->toBeFalse();
    expect($result['devices'])->toBeEmpty();
});

test('smokeping graph requires permission on graphed device', function (): void {
    expect(runAuth('smokeping', ['device' => $this->accessible->device_id, 'src' => $this->accessible->device_id])['auth'])->toBeTrue();
    expect(runAuth('smokeping', ['device' => $this->other->device_id, 'src' => $this->accessible->device_id])['auth'])->toBeFalse();
});

test('smokeping graph requires permission on peer device', function (): void {
    expect(runAuth('smokeping', ['device' => $this->accessible->device_id, 'src' => $this->other->device_id])['auth'])->toBeFalse();
    expect(runAuth('smokeping', ['device' => $this->accessible->device_id, 'dest' => $this->other->device_id])['auth'])->toBeFalse();
    expect(runAuth('smokeping', ['device' => $this->accessible->device_id, 'dest' => $this->accessible->device_id])['auth'])->toBeTrue();
});

test('customer graph only includes permitted ports', function (): void {
    $accessiblePort = Port::factory()->for($this->accessible)->create(['port_descr_type' => 'cust', 'port_descr_descr' => 'Acme']);
    Port::factory()->for($this->other)->create(['port_descr_type' => 'cust', 'port_descr_descr' => 'Acme']);

    foreach ([['customer' => 'Acme'], ['id' => 'Acme']] as $vars) {
        $result = runCustomerAuth($vars);

        expect($result['auth'])->toBeTrue();
        expect($result['ports'])->toEqual([$accessiblePort->port_id]);
    }
});

test('customer graph guest auth includes all ports', function (): void {
    $accessiblePort = Port::factory()->for($this->accessible)->create(['port_descr_type' => 'cust', 'port_descr_descr' => 'Acme']);
    $otherPort = Port::factory()->for($this->other)->create(['port_descr_type' => 'cust', 'port_descr_descr' => 'Acme']);

    $result = runCustomerAuth(['customer' => 'Acme'], true);

    expect($result['auth'])->toBeTrue();
    expect($result['ports'])->toEqualCanonicalizing([$accessiblePort->port_id, $otherPort->port_id]);
});

test('customer graph denied without permitted ports', function (): void {
    Port::factory()->for($this->other)->create(['port_descr_type' => 'cust', 'port_descr_descr' => 'Acme']);
    Port::factory()->for($this->accessible)->create(['port_descr_type' => 'core', 'port_descr_descr' => 'Acme']);

    expect(runCustomerAuth(['customer' => 'Acme'])['auth'])->toBeFalse();
    expect(runCustomerAuth(['customer' => 'Nobody'])['auth'])->toBeFalse();
});

/**
 * @param  array<string, mixed>  $vars
 * @return array{auth: bool, ports: list<int>}
 */
function runCustomerAuth(array $vars, bool $auth = false): array
{
    require __DIR__ . '/../../includes/html/graphs/customer/auth.inc.php';

    /** @var Collection<Port> $customer_ports */
    return ['auth' => $auth, 'ports' => $customer_ports->pluck('port_id')->all()];
}