<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Port;
use App\Models\PortGroup;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function portGroupAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function portGroupUser(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function portGroupPort(Device $device, array $attributes = []): Port
{
    return Port::factory()->for($device)->create(array_merge([
        'ifName' => 'eth' . fake()->unique()->numberBetween(0, 10000),
        'ifDescr' => 'Ethernet',
        'ifAlias' => 'Uplink',
        'ifAdminStatus' => 'up',
        'ifOperStatus' => 'up',
        'deleted' => 0,
    ], $attributes));
}

/**
 * @param  array<string, mixed>  $query
 * @return int[]
 */
function listedPortIds(PortGroup $group, array $query, ?User $user = null): array
{
    $response = test()->actingAs($user ?? portGroupAdmin())
        ->getJson(route('port-groups.ports.list', $group) . '?' . http_build_query($query))
        ->assertOk();

    $ids = array_column($response->json('data'), 'port_id');
    sort($ids);

    return $ids;
}

test('admin can view page', function (): void {
    $group = PortGroup::factory()->create(['name' => 'Uplinks']);

    $response = $this->actingAs(portGroupAdmin())
        ->get(route('port-groups.ports', $group))
        ->assertOk()
        ->assertSee('Uplinks')
        ->assertSee('x-data="portGroupPorts(JSON.parse(', false)
        ->assertSee('x-on:filter:apply.window="if ($event.detail.name === \'port-group.ports\')', false)
        ->assertSee('href="' . route('port-groups.index') . '"', false)
        ->assertDontSee(route('port-groups.edit', $group), false)
        ->assertDontSee(route('portgroup.graph', $group->id), false);

    // every table component includes the shared script, it must only be output once
    expect(substr_count($response->getContent(), 'window.LibreNMS.selectableTable ='))->toBe(1);

    $this->actingAs(portGroupAdmin())
        ->get(route('port-groups.index'))
        ->assertOk()
        ->assertSee(route('port-groups.ports', $group->id), false)
        ->assertSee(route('portgroup.graph', $group->id), false);
});

test('user cannot manage ports', function (): void {
    $group = PortGroup::factory()->create();
    $port = portGroupPort(Device::factory()->create());

    $this->actingAs(portGroupUser())
        ->get(route('port-groups.ports', $group))
        ->assertForbidden();

    $this->actingAs(portGroupUser())
        ->getJson(route('port-groups.ports.list', $group))
        ->assertForbidden();

    $this->actingAs(portGroupUser())
        ->postJson(route('port-groups.ports.update', $group), ['action' => 'add', 'ids' => [$port->port_id]])
        ->assertForbidden();

    expect($group->ports()->count())->toBe(0);
});

test('list by membership', function (): void {
    $group = PortGroup::factory()->create();
    $device = Device::factory()->create();
    $member = portGroupPort($device);
    $other = portGroupPort($device);
    $group->ports()->attach($member);

    expect(listedPortIds($group, ['membership' => 'members']))->toBe([$member->port_id]);
    $this->actingAs(portGroupAdmin())
        ->getJson(route('port-groups.ports.list', $group) . '?membership=members')
        ->assertJsonPath('data.0.member', true)
        ->assertJsonMissingPath('links')
        ->assertJsonMissingPath('next_page_url');
    expect(listedPortIds($group, ['membership' => 'available']))->not->toContain($member->port_id);
    expect(listedPortIds($group, ['membership' => 'available']))->toContain($other->port_id);

    $response = $this->actingAs(portGroupAdmin())
        ->getJson(route('port-groups.ports.list', $group) . '?' . http_build_query([
            'membership' => 'all',
            'filter' => ['device_id' => ['eq' => $device->device_id]],
            'sort' => 'ifName',
        ]))
        ->assertOk()
        ->assertJsonPath('total', 2)
        ->assertJsonPath('members', 1);

    $membership = array_column($response->json('data'), 'member', 'port_id');
    expect($membership[$member->port_id])->toBeTrue();
    expect($membership[$other->port_id])->toBeFalse();
    expect($response->json('data.0.device'))->toBe($device->display);
});

test('sort by device display name', function (): void {
    $group = PortGroup::factory()->create();
    $first = portGroupPort(Device::factory()->create(['hostname' => 'zz.example.com', 'display_template' => 'aa-switch']));
    $second = portGroupPort(Device::factory()->create(['hostname' => 'aa.example.com', 'display_template' => 'zz-switch']));
    $group->ports()->attach([$first->port_id, $second->port_id]);

    $response = $this->actingAs(portGroupAdmin())
        ->getJson(route('port-groups.ports.list', $group) . '?membership=members&sort=device')
        ->assertOk();

    expect(array_column($response->json('data'), 'port_id'))->toBe([$first->port_id, $second->port_id]);
});

test('list port with missing device', function (): void {
    $group = PortGroup::factory()->create();
    $device = Device::factory()->create();
    $port = portGroupPort($device);
    $group->ports()->attach($port);
    $device->deleteQuietly();

    $this->actingAs(portGroupAdmin())
        ->getJson(route('port-groups.ports.list', $group) . '?membership=members')
        ->assertOk()
        ->assertJsonPath('data.0.port_id', $port->port_id)
        ->assertJsonPath('data.0.device', null)
        ->assertJsonPath('data.0.device_url', null);
});

test('list filters', function (): void {
    $group = PortGroup::factory()->create();
    $device = Device::factory()->create();
    $uplink = portGroupPort($device, ['ifAlias' => 'core uplink']);
    portGroupPort($device, ['ifAlias' => 'customer']);
    $down = portGroupPort($device, ['ifAlias' => 'spare', 'ifOperStatus' => 'down']);

    $base = ['membership' => 'available', 'filter' => ['device_id' => ['eq' => $device->device_id]]];
    expect(listedPortIds($group, array_merge_recursive($base, ['filter' => ['search' => ['contains' => 'uplink']]])))->toBe([$uplink->port_id]);
    expect(listedPortIds($group, array_merge_recursive($base, ['filter' => ['state' => ['eq' => 'down']]])))->toBe([$down->port_id]);

    $this->actingAs(portGroupAdmin())
        ->getJson(route('port-groups.ports.list', $group) . '?membership=bogus')
        ->assertUnprocessable();
});

test('add and remove selected ports', function (): void {
    $group = PortGroup::factory()->create();
    $device = Device::factory()->create();
    $ports = collect([portGroupPort($device), portGroupPort($device), portGroupPort($device)]);

    $this->actingAs(portGroupAdmin())
        ->postJson(route('port-groups.ports.update', $group), ['action' => 'add', 'ids' => $ports->pluck('port_id')->all()])
        ->assertOk()
        ->assertJsonPath('updated', 3)
        ->assertJsonPath('members', 3)
        ->assertJsonPath('message', '3 ports added');

    // adding again does not duplicate
    $this->actingAs(portGroupAdmin())
        ->postJson(route('port-groups.ports.update', $group), ['action' => 'add', 'ids' => [$ports[0]->port_id]])
        ->assertOk()
        ->assertJsonPath('updated', 0)
        ->assertJsonPath('members', 3);

    $this->actingAs(portGroupAdmin())
        ->postJson(route('port-groups.ports.update', $group), ['action' => 'remove', 'ids' => [$ports[0]->port_id, $ports[1]->port_id]])
        ->assertOk()
        ->assertJsonPath('updated', 2)
        ->assertJsonPath('members', 1)
        ->assertJsonPath('message', '2 ports removed');

    expect($group->ports()->pluck('ports.port_id')->all())->toBe([$ports[2]->port_id]);
});

test('add and remove all matching ports', function (): void {
    $group = PortGroup::factory()->create();
    $device = Device::factory()->create();
    $up = [portGroupPort($device), portGroupPort($device)];
    $down = portGroupPort($device, ['ifOperStatus' => 'down']);
    $otherDevice = portGroupPort(Device::factory()->create());

    $this->actingAs(portGroupAdmin())
        ->postJson(route('port-groups.ports.update', $group), [
            'action' => 'add',
            'all' => true,
            'membership' => 'available',
            'filter' => ['device_id' => ['eq' => $device->device_id]],
        ])
        ->assertOk()
        ->assertJsonPath('updated', 3);

    $members = $group->ports()->pluck('ports.port_id')->sort()->values()->all();
    expect($members)->toBe(collect([$up[0], $up[1], $down])->pluck('port_id')->sort()->values()->all());
    expect($members)->not->toContain($otherDevice->port_id);

    $group->ports()->attach($otherDevice);

    // remove only matching members
    $this->actingAs(portGroupAdmin())
        ->postJson(route('port-groups.ports.update', $group), [
            'action' => 'remove',
            'all' => true,
            'membership' => 'members',
            'filter' => ['state' => ['eq' => 'up'], 'device_id' => ['eq' => $device->device_id]],
        ])
        ->assertOk()
        ->assertJsonPath('updated', 2)
        ->assertJsonPath('members', 2);

    expect($group->ports()->pluck('ports.port_id')->all())->toEqualCanonicalizing([$down->port_id, $otherDevice->port_id]);
});

test('invalid updates are rejected', function (): void {
    $group = PortGroup::factory()->create();

    $this->actingAs(portGroupAdmin())
        ->postJson(route('port-groups.ports.update', $group), ['action' => 'add'])
        ->assertUnprocessable();

    $this->actingAs(portGroupAdmin())
        ->postJson(route('port-groups.ports.update', $group), ['action' => 'delete', 'ids' => [1]])
        ->assertUnprocessable();

    $this->actingAs(portGroupAdmin())
        ->postJson(route('port-groups.ports.update', $group), ['action' => 'add', 'all' => false])
        ->assertUnprocessable();
});

test('limited user only manages accessible ports', function (): void {
    Permission::findOrCreate('port-group.update');
    $user = portGroupUser();
    $user->givePermissionTo('port-group.update');
    $accessible = Device::factory()->create();
    $user->devicesOwned()->attach($accessible->device_id);

    $group = PortGroup::factory()->create();
    $mine = portGroupPort($accessible);
    $hidden = portGroupPort(Device::factory()->create());

    expect(listedPortIds($group, ['membership' => 'available'], $user))->not->toContain($hidden->port_id);

    $this->actingAs($user)
        ->postJson(route('port-groups.ports.update', $group), ['action' => 'add', 'ids' => [$mine->port_id, $hidden->port_id]])
        ->assertOk()
        ->assertJsonPath('updated', 1);

    expect($group->ports()->pluck('ports.port_id')->all())->toBe([$mine->port_id]);

    // the member count only includes ports the user can access, matching the members list
    $group->ports()->attach($hidden);
    $this->actingAs($user)
        ->getJson(route('port-groups.ports.list', $group) . '?membership=members')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('members', 1);
});