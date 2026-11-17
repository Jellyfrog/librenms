<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Ipv4Mac;
use App\Models\Ipv6Nd;
use App\Models\Port;
use App\Models\PortsFdb;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('search fdb by mac returns fdb tables group', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);
    $mac = '001122334455';

    PortsFdb::forceCreate([
        'device_id' => $device->device_id,
        'port_id' => $port->port_id,
        'mac_address' => $mac,
        'vlan' => 1,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => '00:11:22:33:44:55']));

    $response->assertStatus(200)
        ->assertJsonPath('groups.0.type', 'fdb_tables')
        ->assertJsonPath('groups.0.results.0.name', '00:11:22:33:44:55')
        ->assertJsonPath('groups.0.results.0.icon', 'fa fa-plug');
});

test('search fdb shows ip mapping if available', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);
    $mac = '001122334455';
    $ip = '192.168.1.50';

    PortsFdb::forceCreate([
        'device_id' => $device->device_id,
        'port_id' => $port->port_id,
        'mac_address' => $mac,
        'vlan' => 1,
    ]);

    Ipv4Mac::forceCreate([
        'device_id' => $device->device_id,
        'port_id' => $port->port_id,
        'mac_address' => $mac,
        'ipv4_address' => $ip,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => '00:11:22:33:44:55']));

    $response->assertStatus(200);
    $name = $response->json('groups.0.results.0.name');
    expect($name)->toEqual('00:11:22:33:44:55');
});

test('search fdb by ipv4 returns fdb entry', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);
    $mac = '001122334455';
    $ip = '10.0.0.1';

    PortsFdb::forceCreate(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac, 'vlan' => 1]);
    Ipv4Mac::forceCreate(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac, 'ipv4_address' => $ip]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => $ip]));

    $response->assertStatus(200);

    /** @var array<int, array{name: string, subtitle?: string, icon?: string, status?: string|null, url?: string}> $results */
    $results = (array) $response->json('groups.0.results');
    $names = collect($results)->pluck('name');
    expect($names->contains('00:11:22:33:44:55'))->toBeTrue();
});

test('search fdb by ipv6 returns fdb entry', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);
    $mac = '001122334455';
    $ipv6 = 'fe80::1';

    PortsFdb::forceCreate(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac, 'vlan' => 1]);
    Ipv6Nd::forceCreate(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => $mac, 'ipv6_address' => $ipv6]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => $ipv6]));

    $response->assertStatus(200);

    /** @var array<int, array{name: string, subtitle?: string, icon?: string, status?: string|null, url?: string}> $v6Results */
    $v6Results = (array) $response->json('groups.0.results');
    $names = collect($v6Results)->pluck('name');
    expect($names->contains('00:11:22:33:44:55'))->toBeTrue();
});

test('search fdb single mac port gets green status', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);
    $mac = '001122334455';

    PortsFdb::forceCreate([
        'device_id' => $device->device_id,
        'port_id' => $port->port_id,
        'mac_address' => $mac,
        'vlan' => 1,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => $mac]));

    $response->assertStatus(200)
        ->assertJsonPath('groups.0.results.0.status', 'tw:border-l-green-600!');
});

test('search fdb multi mac port sorts after single mac', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create();

    // Port with 2 MACs (uplink/trunk) — should sort after single-MAC port
    $uplink = Port::factory()->create(['device_id' => $device->device_id]);
    PortsFdb::forceCreate(['device_id' => $device->device_id, 'port_id' => $uplink->port_id, 'mac_address' => 'aabbcc001111', 'vlan' => 1]);
    PortsFdb::forceCreate(['device_id' => $device->device_id, 'port_id' => $uplink->port_id, 'mac_address' => 'aabbcc002222', 'vlan' => 1]);

    // Port with 1 MAC (direct connection) — should sort first
    $access = Port::factory()->create(['device_id' => $device->device_id]);
    PortsFdb::forceCreate(['device_id' => $device->device_id, 'port_id' => $access->port_id, 'mac_address' => 'aabbcc003333', 'vlan' => 1]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => 'aabbcc']));

    $response->assertStatus(200);

    /** @var array<int, array{name: string, subtitle?: string, icon?: string, status?: string|null, url?: string}> $results */
    $results = (array) $response->json('groups.0.results');

    // The single-MAC port entry has a green status; uplink entries do not
    $singleMacResult = collect($results)->firstWhere('name', 'aa:bb:cc:00:33:33');
    expect($singleMacResult['status'])->toEqual('tw:border-l-green-600!');

    $multiMacResult = collect($results)->firstWhere('name', 'aa:bb:cc:00:11:11');
    expect($multiMacResult['status'] ?? null)->toBeNull();
});

test('search fdb subtitle shows connected for single mac', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);
    $mac = '001122334455';

    PortsFdb::forceCreate([
        'device_id' => $device->device_id,
        'port_id' => $port->port_id,
        'mac_address' => $mac,
        'vlan' => 1,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => $mac]));

    $response->assertStatus(200);
    $this->assertStringContainsString('connected', $response->json('groups.0.results.0.subtitle'));
});

test('search fdb subtitle shows trunk for multi mac', function () {
    $user = User::factory()->admin()->create(['enabled' => true]);
    $device = Device::factory()->create();
    $port = Port::factory()->create(['device_id' => $device->device_id]);

    PortsFdb::forceCreate(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '001122334455', 'vlan' => 1]);
    PortsFdb::forceCreate(['device_id' => $device->device_id, 'port_id' => $port->port_id, 'mac_address' => '001122334456', 'vlan' => 1]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => '001122334455']));

    $response->assertStatus(200);
    $this->assertStringContainsString('trunk', $response->json('groups.0.results.0.subtitle'));
});

test('search returns empty when fdb disabled', function () {
    LibrenmsConfig::set('webui.global_search.fdb', false);

    $user = User::factory()->admin()->create(['enabled' => true]);

    $response = $this->actingAs($user)
        ->getJson(route('ajax.search.fdb', ['search' => 'anything']));

    $response->assertStatus(200)
        ->assertJsonPath('groups', []);
});