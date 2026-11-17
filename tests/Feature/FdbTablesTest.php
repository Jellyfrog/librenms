<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Device;
use App\Models\Port;
use App\Models\PortsFdb;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    $this->device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $this->device->device_id]);
    foreach (['02aaaaaaaa01' => 100, '02aaaaaaaa02' => 200, '02aaaaaaaa03' => null] as $mac => $vlan) {
        PortsFdb::factory()->create(['device_id' => $this->device->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac, 'vlan' => $vlan]);
    }
});

test('shows the vlan number and nothing without one', function () {
    $vlans = collect(rows())->pluck('vlan', 'mac_address');

    expect($vlans['02:aa:aa:aa:aa:01'])->toBe(100);
    expect($vlans['02:aa:aa:aa:aa:03'])->toBe('');
});

test('search by vlan', function () {
    expect(macs(['searchby' => 'vlan', 'searchPhrase' => '200']))->toBe(['02:aa:aa:aa:aa:02']);
});

test('vlan search with text matches nothing', function () {
    expect(macs(['searchby' => 'vlan', 'searchPhrase' => 'not-a-vlan']))->toBe([]);
});

test('search all fields matches vlan', function () {
    expect(macs(['searchPhrase' => '100']))->toBe(['02:aa:aa:aa:aa:01']);
});

test('text search does not match entries without avlan', function () {
    expect(macs(['searchPhrase' => 'not-a-vlan']))->toBe([]);
});

test('sort by vlan', function () {
    expect(collect(rows(['sort' => ['vlan' => 'desc']]))->pluck('vlan')->all())->toBe([200, 100, '']);
});

/**
 * @param  array<string, mixed>  $params
 * @return list<string>
 */
function macs(array $params): array
{
    return collect(rows($params))->pluck('mac_address')->all();
}

/**
 * @param  array<string, mixed>  $params
 * @return list<array<string, mixed>>
 */
function rows(array $params = []): array
{
    return $this->actingAs(User::factory()->admin()->create(['enabled' => true]))
        ->postJson(url('/ajax/table/fdb-tables'), $params + ['device_id' => $this->device->device_id])
        ->assertOk()
        ->json('rows');
}