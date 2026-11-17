<?php

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Port;
use App\Models\PortGroup;
use App\Models\User;
use App\Models\UserPref;
use Spatie\Permission\Models\Role;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
    LibrenmsConfig::set('polling.selected_ports', false);
});

function portsAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function portsUser(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makePort(Device $device, array $attributes = []): Port
{
    return Port::factory()->for($device)->create(array_merge([
        'ifName' => 'eth' . fake()->unique()->numberBetween(0, 10000),
        'ifDescr' => 'Ethernet',
        'ifAlias' => 'Uplink',
        'ifSpeed' => 1000000000,
        'ifAdminStatus' => 'up',
        'ifOperStatus' => 'up',
        'disabled' => 0,
        'ignore' => 0,
        'deleted' => 0,
    ], $attributes));
}

/**
 * @return array{up: Port, down: Port, admin_down: Port, lower_layer_down: Port, disabled: Port, deleted: Port}
 */
function portSet(Device $device): array
{
    return [
        'up' => makePort($device, ['ifIndex' => 1]),
        'down' => makePort($device, ['ifIndex' => 2, 'ifOperStatus' => 'down']),
        'admin_down' => makePort($device, ['ifIndex' => 3, 'ifAdminStatus' => 'down', 'ifOperStatus' => 'down']),
        'lower_layer_down' => makePort($device, ['ifIndex' => 4, 'ifOperStatus' => 'lowerLayerDown']),
        'disabled' => makePort($device, ['ifIndex' => 5, 'disabled' => 1]),
        'deleted' => makePort($device, ['ifIndex' => 6, 'deleted' => 1]),
    ];
}

/**
 * @param  array<string, mixed>  $query
 * @return array<int, string>
 */
function pollingStates(Device $device, array $query = []): array
{
    $response = test()->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device) . '?' . http_build_query($query))
        ->assertOk();

    return array_column($response->json('data'), 'polling', 'ifIndex');
}

test('admin can view ports page', function (): void {
    $device = Device::factory()->create();

    $this->actingAs(portsAdmin())
        ->get(route('device.edit.ports', $device))
        ->assertOk()
        ->assertSee('Selected port polling')
        ->assertSee('devicePorts', false)
        ->assertSee('window.LibreNMS.selectableTable =', false);
});

test('user cannot view ports page', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device);

    $this->actingAs(portsUser())
        ->get(route('device.edit.ports', $device))
        ->assertForbidden();

    $this->actingAs(portsUser())
        ->getJson(route('device.edit.ports.list', $device))
        ->assertForbidden();

    $this->actingAs(portsUser())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['disabled' => true])
        ->assertForbidden();

    expect($port->fresh()->disabled)->toBe(0);
});

test('list returns structured data', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device, ['ifName' => 'Gi0/1', 'ifAlias' => '<b>uplink</b>']);
    $device->setAttrib('ifName:Gi0/1', 1);

    $response = $this->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.port_id', $port->port_id)
        ->assertJsonPath('data.0.ifAlias', '<b>uplink</b>')
        ->assertJsonPath('data.0.ifAlias_override', true)
        ->assertJsonPath('data.0.ifSpeed_override', false)
        ->assertJsonPath('data.0.polling', 'polled');

    expect($response->json())->toHaveKey('summary');
    expect($response->json('data.0.link_class'))->toBe('interface-upup');
});

test('full polling polls down ports', function (): void {
    $device = Device::factory()->create();
    portSet($device);

    expect(pollingStates($device))->toBe([
        1 => 'polled',
        2 => 'polled',
        3 => 'polled',
        4 => 'polled',
        5 => 'disabled',
        6 => 'deleted',
    ]);
});

test('selected port polling skips down ports', function (): void {
    $device = Device::factory()->create();
    portSet($device);
    $device->setAttrib('selected_ports', 'true');

    expect(pollingStates($device))->toBe([
        1 => 'polled',
        2 => 'down',
        3 => 'admin_down',
        4 => 'down',
        5 => 'disabled',
        6 => 'deleted',
    ]);

    expect(pollingStates($device, ['filter' => ['polling' => ['eq' => 'polled']]]))->toBe([1 => 'polled']);
    expect(array_keys(pollingStates($device, ['filter' => ['polling' => ['eq' => 'skipped']]])))->toBe([2, 3, 4]);
    expect(array_keys(pollingStates($device, ['filter' => ['polling' => ['eq' => 'not_polled']]])))->toBe([2, 3, 4, 5, 6]);
    expect(array_keys(pollingStates($device, ['filter' => ['polling' => ['neq' => 'polled']]])))->toBe([2, 3, 4, 5, 6]);
    expect(array_keys(pollingStates($device, ['filter' => ['polling' => ['not_in' => 'skipped']]])))->toBe([1, 5, 6]);
    expect(array_keys(pollingStates($device, ['filter' => ['polling' => ['eq' => 'skipped'], 'state' => ['eq' => 'down']]])))->toBe([2, 4]);

    $this->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device))
        ->assertJsonPath('summary', [
            'polled' => 1,
            'skipped' => 3,
            'disabled' => 1,
            'ignored' => 0,
            'deleted' => 1,
        ]);
});

test('summary excludes deleted ports', function (): void {
    $device = Device::factory()->create();
    makePort($device, ['ifIndex' => 1, 'disabled' => 1, 'ignore' => 1]);
    makePort($device, ['ifIndex' => 2, 'disabled' => 1, 'ignore' => 1, 'deleted' => 1]);

    $this->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device))
        ->assertJsonPath('summary', [
            'polled' => 0,
            'skipped' => 0,
            'disabled' => 1,
            'ignored' => 1,
            'deleted' => 1,
        ]);

    // the summary links use these filters, they must match the counts
    expect(array_keys(pollingStates($device, ['filter' => ['disabled' => ['eq' => '1'], 'deleted' => ['eq' => '0']]])))->toBe([1]);
    expect(array_keys(pollingStates($device, ['filter' => ['ignore' => ['eq' => '1'], 'deleted' => ['eq' => '0']]])))->toBe([1]);
});

test('selected port polling falls back to os then global', function (): void {
    $device = Device::factory()->create(['os' => 'linux']);
    makePort($device, ['ifIndex' => 1, 'ifOperStatus' => 'down']);

    LibrenmsConfig::set('polling.selected_ports', true);
    expect(pollingStates($device))->toBe([1 => 'down']);

    LibrenmsConfig::set('os.linux.polling.selected_ports', false);
    expect(pollingStates($device))->toBe([1 => 'polled']);

    $device->setAttrib('selected_ports', 'true');
    expect(pollingStates($device))->toBe([1 => 'down']);

    LibrenmsConfig::forget('os.linux.polling.selected_ports');
});

test('search and filters', function (): void {
    $device = Device::factory()->create();
    makePort($device, ['ifIndex' => 10, 'ifName' => 'Gi0/10', 'ifAlias' => 'core uplink']);
    makePort($device, ['ifIndex' => 11, 'ifName' => 'Gi0/11', 'ifAlias' => 'access', 'ifOperStatus' => 'down']);
    makePort($device, ['ifIndex' => 12, 'ifName' => 'Gi0/12', 'ifAlias' => 'access', 'ignore' => 1]);
    makePort(Device::factory()->create(), ['ifIndex' => 13, 'ifName' => 'Gi0/13', 'ifAlias' => 'core uplink']);

    expect(array_keys(pollingStates($device, ['filter' => ['search' => ['contains' => 'core']]])))->toBe([10]);
    expect(array_keys(pollingStates($device, ['filter' => ['ifIndex' => ['eq' => '11']]])))->toBe([11]);
    expect(array_keys(pollingStates($device, ['filter' => ['state' => ['eq' => 'down']]])))->toBe([11]);
    expect(array_keys(pollingStates($device, ['filter' => ['ignore' => ['eq' => '1']]])))->toBe([12]);
    expect(array_keys(pollingStates($device, ['sort' => 'ifIndex', 'order' => 'desc'])))->toBe([12, 11, 10]);

    // a filter can not escape the device
    expect(pollingStates($device, ['filter' => ['device_id' => ['neq' => $device->device_id]]]))->toBe([]);

    $this->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device) . '?' . http_build_query(['filter' => ['search' => ['bogus' => 'x']]]))
        ->assertUnprocessable();
});

test('pagination', function (): void {
    $device = Device::factory()->create();
    foreach (range(1, 5) as $ifIndex) {
        makePort($device, ['ifIndex' => $ifIndex]);
    }

    $this->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device) . '?per_page=2&page=3')
        ->assertOk()
        ->assertJsonPath('total', 5)
        ->assertJsonPath('current_page', 3)
        ->assertJsonPath('last_page', 3)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.ifIndex', 5);

    // a page past the end shows the last page
    $this->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device) . '?per_page=2&page=9')
        ->assertOk()
        ->assertJsonPath('current_page', 3)
        ->assertJsonPath('data.0.ifIndex', 5);
});

test('update toggles', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device, ['ifName' => 'eth0']);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['disabled' => true, 'ignore' => true])
        ->assertOk()
        ->assertJsonPath('port.disabled', true)
        ->assertJsonPath('port.ignore', true)
        ->assertJsonPath('port.polling', 'disabled')
        ->assertJsonPath('summary.disabled', 1);

    $port->refresh();
    expect($port->disabled)->toBe(1);
    expect($port->ignore)->toBe(1);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['rrd_tune' => true])
        ->assertOk()
        ->assertJsonPath('port.rrd_tune', true)
        ->assertJsonPath('port.rrd_tune_override', true);
    expect($device->getAttrib('ifName_tune:eth0'))->toBe('true');

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['rrd_tune' => false])
        ->assertOk()
        ->assertJsonPath('port.rrd_tune', false)
        ->assertJsonPath('port.rrd_tune_override', false);

    // matches the device setting, so no override is stored
    expect($device->fresh()->getAttrib('ifName_tune:eth0'))->toBeNull();
});

test('update alias', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device, ['ifName' => 'eth0']);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['ifAlias' => 'To core'])
        ->assertOk()
        ->assertJsonPath('port.ifAlias', 'To core')
        ->assertJsonPath('port.ifAlias_override', true);

    expect($port->fresh()->ifAlias)->toBe('To core');
    expect($device->fresh()->getAttrib('ifName:eth0'))->not->toBeNull();

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['ifAlias' => ''])
        ->assertOk()
        ->assertJsonPath('port.ifAlias', '')
        ->assertJsonPath('port.ifAlias_override', false);

    expect($port->fresh()->ifAlias)->toBe('repoll');
    expect($device->fresh()->getAttrib('ifName:eth0'))->toBeNull();
});

test('update speed', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device, ['ifName' => 'eth0']);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['ifSpeed' => 10000000])
        ->assertOk()
        ->assertJsonPath('port.ifSpeed', 10000000)
        ->assertJsonPath('port.ifSpeed_override', true);

    expect($port->fresh()->ifSpeed)->toBe(10000000);
    expect($device->fresh()->getAttrib('ifSpeed:eth0'))->toEqual(10000000);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['ifSpeed' => null])
        ->assertOk()
        ->assertJsonPath('port.ifSpeed', 10000000)
        ->assertJsonPath('port.ifSpeed_override', false);

    // only the override is removed, polling updates the speed
    expect($device->fresh()->getAttrib('ifSpeed:eth0'))->toBeNull();
    expect($port->fresh()->ifSpeed)->toBe(10000000);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['ifSpeed' => 'fast'])
        ->assertUnprocessable();
});

test('reset custom speed', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device, ['ifName' => 'eth0', 'ifSpeed' => 1000000000]);
    $device->setAttrib('ifSpeed:eth0', 1000000000);

    // the device speed is unknown while a custom speed is set
    $this->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device))
        ->assertJsonPath('data.0.ifSpeed', 1000000000)
        ->assertJsonPath('data.0.ifSpeed_device', null);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['ifSpeed' => null])
        ->assertOk()
        ->assertJsonPath('port.ifSpeed', 1000000000)
        ->assertJsonPath('port.ifSpeed_override', false);

    expect($device->fresh()->getAttrib('ifSpeed:eth0'))->toBeNull();
});

test('update circuit speed', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device, ['ifName' => 'eth0']);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['port_descr_speed' => ['out' => 100000000, 'in' => 20000000]])
        ->assertOk()
        ->assertJsonPath('port.circuit_speed', [100000000, 20000000])
        ->assertJsonPath('port.circuit_speed_override', true);

    expect($port->fresh()->port_descr_speed)->toBe('100M/20M');
    expect($device->fresh()->getAttrib('port_descr_speed:eth0'))->toBe('100M/20M');
    expect($port->fresh()->getSpeeds())->toBe([100000000, 20000000]);

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['port_descr_speed' => ['out' => 1544000, 'in' => 1544000]])
        ->assertOk()
        ->assertJsonPath('port.circuit_speed', [1544000, 1544000]);
    expect($port->fresh()->port_descr_speed)->toBe('1.544M');

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['port_descr_speed' => null])
        ->assertOk()
        ->assertJsonPath('port.circuit_speed', null)
        ->assertJsonPath('port.circuit_speed_override', false);

    // polling sets it from the port description again
    expect($port->fresh()->port_descr_speed)->toBeNull();
    expect($device->fresh()->getAttrib('port_descr_speed:eth0'))->toBeNull();

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), ['port_descr_speed' => ['out' => 0, 'in' => 100]])
        ->assertUnprocessable();
});

test('invalid updates are rejected', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device);
    $otherPort = makePort(Device::factory()->create());

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $port]), [])
        ->assertUnprocessable();

    $this->actingAs(portsAdmin())
        ->patchJson(route('device.edit.ports.update', [$device, $otherPort]), ['disabled' => true])
        ->assertNotFound();

    expect($otherPort->fresh()->disabled)->toBe(0);
});

test('bulk action on all matching ports', function (): void {
    $device = Device::factory()->create();
    $up = makePort($device, ['ifIndex' => 1]);
    $down = makePort($device, ['ifIndex' => 2, 'ifOperStatus' => 'down']);
    $otherDevicePort = makePort(Device::factory()->create(), ['ifIndex' => 3, 'ifOperStatus' => 'down']);

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), ['action' => 'ignore', 'all' => true, 'group_id' => null, 'filter' => ['state' => ['eq' => 'down']]])
        ->assertOk()
        ->assertJsonPath('updated', 1)
        ->assertJsonPath('summary.ignored', 1);

    expect($up->fresh()->ignore)->toBe(0);
    expect($down->fresh()->ignore)->toBe(1);
    expect($otherDevicePort->fresh()->ignore)->toBe(0);

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), ['action' => 'disable', 'all' => true])
        ->assertOk()
        ->assertJsonPath('updated', 2);

    expect($up->fresh()->disabled)->toBe(1);
    expect($down->fresh()->disabled)->toBe(1);
    expect($otherDevicePort->fresh()->disabled)->toBe(0);

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), ['action' => 'explode', 'all' => true])
        ->assertUnprocessable();

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), ['action' => 'enable'])
        ->assertUnprocessable();

    $this->actingAs(portsUser())
        ->postJson(route('device.edit.ports.bulk', $device), ['action' => 'enable', 'all' => true])
        ->assertForbidden();
});

test('bulk action on selected ports', function (): void {
    $device = Device::factory()->create();
    $first = makePort($device, ['ifIndex' => 1]);
    $second = makePort($device, ['ifIndex' => 2]);
    $third = makePort($device, ['ifIndex' => 3]);
    $otherDevicePort = makePort(Device::factory()->create(), ['ifIndex' => 4]);

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), [
            'action' => 'disable',
            'ids' => [$first->port_id, $third->port_id, $otherDevicePort->port_id],
        ])
        ->assertOk()
        ->assertJsonPath('updated', 2);

    expect($first->fresh()->disabled)->toBe(1);
    expect($second->fresh()->disabled)->toBe(0);
    expect($third->fresh()->disabled)->toBe(1);
    expect($otherDevicePort->fresh()->disabled)->toBe(0);

    // neither every port nor a selection
    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), ['action' => 'disable', 'all' => false])
        ->assertUnprocessable();
});

test('bulk port groups', function (): void {
    $device = Device::factory()->create();
    $first = makePort($device, ['ifIndex' => 1]);
    $second = makePort($device, ['ifIndex' => 2]);
    $otherDevicePort = makePort(Device::factory()->create(), ['ifIndex' => 3]);
    $group = PortGroup::factory()->create();
    $group->ports()->attach($first);

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), [
            'action' => 'add_group',
            'group_id' => $group->id,
            'ids' => [$first->port_id, $second->port_id, $otherDevicePort->port_id],
        ])
        ->assertOk()
        ->assertJsonPath('updated', 1);

    expect($group->ports()->pluck('ports.port_id')->all())->toEqualCanonicalizing([$first->port_id, $second->port_id]);

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), ['action' => 'remove_group', 'group_id' => $group->id, 'all' => true])
        ->assertOk()
        ->assertJsonPath('updated', 2);

    expect($group->ports()->count())->toBe(0);

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.bulk', $device), ['action' => 'add_group', 'all' => true])
        ->assertUnprocessable();
});

test('create port group with json', function (): void {
    $this->actingAs(portsAdmin())
        ->postJson(route('port-groups.store'), ['name' => 'Uplinks'])
        ->assertCreated()
        ->assertJsonPath('text', 'Uplinks');

    expect(PortGroup::where('name', 'Uplinks')->exists())->toBeTrue();

    $this->actingAs(portsAdmin())
        ->postJson(route('port-groups.store'), ['name' => 'Uplinks'])
        ->assertUnprocessable();
});

test('saved filter is used on page load', function (): void {
    $device = Device::factory()->create();
    $admin = portsAdmin();
    UserPref::setPref($admin, 'filters.device.edit-ports', ['polling' => ['eq' => 'skipped']]);

    $this->actingAs($admin)
        ->get(route('device.edit.ports', $device))
        ->assertOk()
        ->assertViewHas('filter', ['polling' => ['eq' => 'skipped']]);
});

test('selected ports setting', function (): void {
    $device = Device::factory()->create();

    $this->actingAs(portsAdmin())
        ->putJson(route('device.edit.ports.settings', $device), ['selected_ports' => 'true'])
        ->assertOk()
        ->assertJsonPath('settings.selected_ports.device', true);
    expect($device->fresh()->getAttrib('selected_ports'))->toBe('true');

    $this->actingAs(portsAdmin())
        ->putJson(route('device.edit.ports.settings', $device), ['selected_ports' => 'false'])
        ->assertOk()
        ->assertJsonPath('settings.selected_ports.device', false);
    expect($device->fresh()->getAttrib('selected_ports'))->toBe('false');

    $this->actingAs(portsAdmin())
        ->putJson(route('device.edit.ports.settings', $device), ['selected_ports' => 'clear'])
        ->assertOk()
        ->assertJsonPath('settings.selected_ports.device', null);
    expect($device->fresh()->getAttrib('selected_ports'))->toBeNull();

    $this->actingAs(portsAdmin())
        ->putJson(route('device.edit.ports.settings', $device), ['selected_ports' => 'maybe'])
        ->assertUnprocessable();

    $this->actingAs(portsUser())
        ->putJson(route('device.edit.ports.settings', $device), ['selected_ports' => 'true'])
        ->assertForbidden();
});

test('reset port state', function (): void {
    $device = Device::factory()->create();
    $port = makePort($device, [
        'ifSpeed_prev' => 100,
        'ifOperStatus_prev' => 'down',
        'ifAdminStatus_prev' => 'down',
    ]);

    $this->actingAs(portsAdmin())
        ->postJson(route('device.edit.ports.reset-state', $device))
        ->assertOk();

    $port->refresh();
    expect($port->ifSpeed_prev)->toBeNull();
    expect($port->ifOperStatus_prev)->toBeNull();
    expect($port->ifAdminStatus_prev)->toBeNull();
});

test('rrd tune setting', function (): void {
    LibrenmsConfig::set('rrdtool_tune', false);
    $device = Device::factory()->create();
    makePort($device, ['ifIndex' => 1, 'ifName' => 'eth0']);
    makePort($device, ['ifIndex' => 2, 'ifName' => 'eth1']);
    $device->setAttrib('ifName_tune:eth1', 'false');

    $this->actingAs(portsAdmin())
        ->putJson(route('device.edit.ports.settings', $device), ['rrd_tune' => 'true'])
        ->assertOk()
        ->assertJsonPath('settings.rrd_tune.device', true)
        ->assertJsonPath('settings.rrd_tune.global', false);
    expect($device->fresh()->getAttrib('override_rrdtool_tune'))->toBe('true');

    // ports follow the device setting unless overridden
    $this->actingAs(portsAdmin())
        ->getJson(route('device.edit.ports.list', $device))
        ->assertJsonPath('data.0.rrd_tune', true)
        ->assertJsonPath('data.0.rrd_tune_override', false)
        ->assertJsonPath('data.1.rrd_tune', false)
        ->assertJsonPath('data.1.rrd_tune_override', true);

    $this->actingAs(portsAdmin())
        ->putJson(route('device.edit.ports.settings', $device), ['rrd_tune' => 'clear'])
        ->assertOk()
        ->assertJsonPath('settings.rrd_tune.device', null);
    expect($device->fresh()->getAttrib('override_rrdtool_tune'))->toBeNull();

    $this->actingAs(portsAdmin())
        ->putJson(route('device.edit.ports.settings', $device), [])
        ->assertUnprocessable();
});

test('misc page no longer has port settings', function (): void {
    $device = Device::factory()->create();

    $this->actingAs(portsAdmin())
        ->get(route('device.edit.misc', $device))
        ->assertOk()
        ->assertDontSee('selected_ports')
        ->assertDontSee('override_rrdtool_tune');
});