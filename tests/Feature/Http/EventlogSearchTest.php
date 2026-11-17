<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\Device;
use App\Models\Eventlog;
use App\Models\Port;
use App\Models\User;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function admin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

test('eventlog table search matches port label', function () {
    $device = Device::factory()->create();
    $portA = Port::factory()->for($device)->create(['ifName' => 'GigabitEthernet0/1', 'ifDescr' => 'GigabitEthernet0/1']);
    $portB = Port::factory()->for($device)->create(['ifName' => 'GigabitEthernet0/2', 'ifDescr' => 'GigabitEthernet0/2']);

    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'interface',
        'reference' => (string) $portA->port_id,
        'message' => 'ifOperStatus: up -> down',
    ]);
    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'interface',
        'reference' => (string) $portB->port_id,
        'message' => 'ifOperStatus: down -> up',
    ]);

    $this->actingAs(admin())
        ->postJson(route('table.eventlog'), [
            'device' => $device->device_id,
            'eventtype' => 'interface',
            'searchPhrase' => 'GigabitEthernet0/1',
            'rowCount' => 25,
            'current' => 1,
        ])
        ->assertOk()
        ->assertJsonCount(1, 'rows')
        ->assertJsonPath('rows.0.message', 'ifOperStatus: up -&gt; down');
});

test('eventlog search does not match non interface events with port id reference', function () {
    $device = Device::factory()->create();
    $port = Port::factory()->for($device)->create(['ifName' => 'GigabitEthernet0/1', 'ifDescr' => 'GigabitEthernet0/1']);

    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'system',
        'reference' => (string) $port->port_id,
        'message' => 'system event with a port id reference',
    ]);

    $this->actingAs(admin())
        ->postJson(route('table.eventlog'), [
            'device' => $device->device_id,
            'searchPhrase' => 'GigabitEthernet0/1',
            'rowCount' => 25,
            'current' => 1,
        ])
        ->assertOk()
        ->assertJsonCount(0, 'rows');
});

test('eventlog search still matches message', function () {
    $device = Device::factory()->create();

    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'system',
        'message' => 'unique-marker-message',
    ]);

    $this->actingAs(admin())
        ->postJson(route('table.eventlog'), [
            'device' => $device->device_id,
            'searchPhrase' => 'unique-marker-message',
            'rowCount' => 25,
            'current' => 1,
        ])
        ->assertOk()
        ->assertJsonCount(1, 'rows')
        ->assertJsonPath('rows.0.message', 'unique-marker-message');
});