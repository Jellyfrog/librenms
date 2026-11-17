<?php

/**
 * AjaxTableAuthorizationTest.php
 *
 * Verifies that legacy ajax tables only return data for devices the user can access.
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
use App\Models\Component;
use App\Models\ComponentPref;
use App\Models\Device;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('user');
    $this->accessible = Device::factory()->create(['bgpLocalAs' => 64496]);
    $this->other = Device::factory()->create(['bgpLocalAs' => 64497]);
    LibrenmsConfig::set('astext.64496', 'AS-ACCESSIBLE');
    LibrenmsConfig::set('astext.64497', 'AS-OTHER');
});

function limitedUser(): User
{
    /** @var User $user */
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');
    $user->devicesOwned()->attach($this->accessible->device_id);

    return $user;
}

function adminUser(): User
{
    /** @var User $user */
    $user = User::factory()->admin()->create(['enabled' => 1]);

    return $user;
}

function ntpComponent(Device $device, string $peer): Component
{
    /** @var Component $component */
    $component = Component::factory()->create(['device_id' => $device->device_id, 'type' => 'ntp', 'label' => $peer]);
    foreach (['peer' => $peer, 'stratum' => '2', 'error' => ''] as $attribute => $value) {
        ComponentPref::create(['component' => $component->id, 'attribute' => $attribute, 'value' => $value]);
    }

    return $component;
}

/**
 * Run a legacy ajax table include the way html/ajax_table.php does and return the decoded output.
 *
 * @param  array<string, mixed>  $vars
 * @return array{current: int, rowCount: int, rows: list<array<string, mixed>>, total: int}
 */
function runTable(string $id, array $vars = []): array
{
    $current = 1;
    $rowCount = 50;
    $sort = '';
    $searchPhrase = '';
    $response = [];
    $vars += ['current' => $current, 'rowCount' => $rowCount, 'searchPhrase' => $searchPhrase];

    ob_start();
    try {
        include base_path("includes/html/table/$id.inc.php");
    } finally {
        $output = (string) ob_get_clean();
    }

    return json_decode($output, true, flags: JSON_THROW_ON_ERROR);
}

test('component table requires device access', function () {
    $mine = Component::factory()->create(['device_id' => $this->accessible->device_id, 'label' => 'mine']);
    Component::factory()->create(['device_id' => $this->other->device_id, 'label' => 'secret']);
    $this->actingAs(limitedUser());

    $denied = runTable('component', ['device_id' => $this->other->device_id]);
    expect($denied['rows'])->toBe([]);
    expect($denied['total'])->toBe(0);

    expect(runTable('component', ['device_id' => 'invalid'])['total'])->toBe(0);
    expect(runTable('component')['total'])->toBe(0);

    $allowed = runTable('component', ['device_id' => (string) $this->accessible->device_id]);
    expect($allowed['total'])->toBe(1);
    expect(array_column($allowed['rows'], 'id'))->toContain($mine->id);
});

test('component table allows admin', function () {
    $component = Component::factory()->create(['device_id' => $this->other->device_id]);
    $this->actingAs(adminUser());

    $result = runTable('component', ['device_id' => $this->other->device_id]);
    expect($result['total'])->toBe(1);
    expect(array_column($result['rows'], 'id'))->toContain($component->id);
});

test('ntp table only lists accessible devices', function () {
    ntpComponent($this->accessible, '192.0.2.1');
    ntpComponent($this->other, '192.0.2.2');
    $vars = ['view' => 'all', 'graph' => 'none'];

    $this->actingAs(limitedUser());
    $limited = runTable('app_ntp', $vars);
    expect(array_column($limited['rows'], 'peer'))->toBe(['192.0.2.1']);
    expect($limited['total'])->toBe(1);

    $this->actingAs(adminUser());
    $all = runTable('app_ntp', $vars);
    expect(array_column($all['rows'], 'peer'))->toEqualCanonicalizing(['192.0.2.1', '192.0.2.2']);
});

test('as selection only lists accessible devices', function () {
    $this->actingAs(limitedUser());
    $limited = runTable('as-selection');
    expect(array_column($limited['rows'], 'bgpLocalAs'))->toEqual([64496]);
    expect($limited['total'])->toEqual(1);

    $this->actingAs(adminUser());
    $all = array_column(runTable('as-selection')['rows'], 'bgpLocalAs');
    expect($all)->toContain(64496);
    expect($all)->toContain(64497);
});