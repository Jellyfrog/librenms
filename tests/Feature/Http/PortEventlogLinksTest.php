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

test('port page menu links to port filtered eventlog', function () {
    $device = Device::factory()->create();
    $port = Port::factory()->for($device)->create(['ifDescr' => 'GigabitEthernet0/1', 'ifOperStatus' => 'up']);

    $this->actingAs(admin())
        ->get(route('device', [
            'device' => $device,
            'tab' => 'port',
            'vars' => 'port=' . $port->port_id . '/view=notes/graph=bits/subview=graphs',
        ]))
        ->assertOk()
        ->assertSee(route('device.eventlog', ['device' => $device->device_id, 'port' => $port->port_id]), false);
});

test('port list shows eventlog link only for ports with events', function () {
    $device = Device::factory()->create();
    $portWithEvents = Port::factory()->for($device)->create(['ifName' => 'GigabitEthernet0/1', 'ifDescr' => 'GigabitEthernet0/1', 'ifOperStatus' => 'up']);
    $portWithoutEvents = Port::factory()->for($device)->create(['ifName' => 'GigabitEthernet0/2', 'ifDescr' => 'GigabitEthernet0/2', 'ifOperStatus' => 'up']);

    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'interface',
        'reference' => (string) $portWithEvents->port_id,
        'message' => 'ifOperStatus: up -> down',
    ]);

    $this->actingAs(admin())
        ->get(route('device', ['device' => $device, 'tab' => 'ports']))
        ->assertOk()
        ->assertSee(route('device.eventlog', ['device' => $device->device_id, 'port' => $portWithEvents->port_id]))
        ->assertDontSee(route('device.eventlog', ['device' => $device->device_id, 'port' => $portWithoutEvents->port_id]));
});

test('old port events view falls back to graphs', function () {
    $device = Device::factory()->create();
    $port = Port::factory()->for($device)->create(['ifDescr' => 'GigabitEthernet0/1', 'ifOperStatus' => 'up']);

    $this->actingAs(admin())
        ->get(route('device', [
            'device' => $device,
            'tab' => 'port',
            'vars' => 'port=' . $port->port_id . '/view=events',
        ]))
        ->assertOk()
        ->assertSee(route('device.eventlog', ['device' => $device->device_id, 'port' => $port->port_id]), false);
});

test('eventlog table filters by port', function () {
    $device = Device::factory()->create();
    $port = Port::factory()->for($device)->create(['ifDescr' => 'GigabitEthernet0/1']);

    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'interface',
        'reference' => (string) $port->port_id,
        'message' => 'ifOperStatus: up -> down',
    ]);
    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'interface',
        'reference' => '999999',
        'message' => 'ifOperStatus: down -> up',
    ]);

    $this->actingAs(admin())
        ->postJson(route('table.eventlog'), [
            'device' => $device->device_id,
            'eventtype' => 'interface',
            'port' => $port->port_id,
            'rowCount' => 25,
            'current' => 1,
        ])
        ->assertOk()
        ->assertJsonCount(1, 'rows')
        ->assertJsonPath('rows.0.message', 'ifOperStatus: up -&gt; down');
});

test('eventlog table port filter ignores other types with same reference', function () {
    $device = Device::factory()->create();
    $port = Port::factory()->for($device)->create(['ifDescr' => 'GigabitEthernet0/1']);

    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'interface',
        'reference' => (string) $port->port_id,
        'message' => 'port event',
    ]);
    Eventlog::factory()->create([
        'device_id' => $device->device_id,
        'type' => 'temperature',
        'reference' => (string) $port->port_id,
        'message' => 'sensor event',
    ]);

    $this->actingAs(admin())
        ->postJson(route('table.eventlog'), [
            'device' => $device->device_id,
            'port' => $port->port_id,
            'rowCount' => 25,
            'current' => 1,
        ])
        ->assertOk()
        ->assertJsonCount(1, 'rows')
        ->assertJsonPath('rows.0.message', 'port event');
});

test('eventlog page shows port label and port pass through', function () {
    $device = Device::factory()->create();
    $port = Port::factory()->for($device)->create(['ifName' => 'GigabitEthernet0/1', 'ifDescr' => 'GigabitEthernet0/1', 'ifOperStatus' => 'up']);

    $response = $this->actingAs(admin())
        ->get(route('device.eventlog', ['device' => $device->device_id, 'port' => $port->port_id]));
    $response->assertOk()
        ->assertSee('GigabitEthernet0', false)
        ->assertSee('name="port"', false)
        ->assertSee('value="' . $port->port_id . '"', false);

    $html = $response->getContent();
    $this->assertNotFalse(mb_strpos($html, 'Filter</button>'));
    $this->assertNotFalse(mb_strpos($html, 'Port&nbsp;&nbsp;'));
    expect(mb_strpos($html, 'Filter</button>') < mb_strpos($html, 'Port&nbsp;&nbsp;'))->toBeTrue();
});

test('eventlog page escapes html in port label', function () {
    $device = Device::factory()->create();
    $port = Port::factory()->for($device)->create(['ifName' => 'evil <b>bold</b>', 'ifDescr' => 'evil <b>bold</b>', 'ifOperStatus' => 'up']);

    $html = $this->actingAs(admin())
        ->get(route('device.eventlog', ['device' => $device->device_id, 'port' => $port->port_id]))
        ->assertOk()
        ->getContent();

    $this->assertStringNotContainsString('<b>bold</b>', $html);
    $this->assertStringNotContainsString('\u003Cb\u003E', $html);
    $this->assertStringContainsString('\u0026lt;b\u0026gt;bold', $html);
});