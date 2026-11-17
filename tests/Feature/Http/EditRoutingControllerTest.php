<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Models\BgpPeer;
use App\Models\Device;
use App\Models\User;
use App\Models\Vrf;
use Spatie\Permission\Models\Role;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('user');
});

function routingAdmin(): User
{
    $admin = User::factory()->create(['enabled' => 1]);
    $admin->assignRole('admin');

    return $admin;
}

function routingUser(): User
{
    $user = User::factory()->create(['enabled' => 1]);
    $user->assignRole('user');

    return $user;
}

test('admin can view routing page', function (): void {
    $device = Device::factory()->create();
    Vrf::factory()->for($device)->create(['vrf_oid' => 'vrf-red', 'vrf_name' => 'vrf-red', 'context_name' => 'vrf-red', 'context_manual' => true, 'vrf_manual' => true]);
    BgpPeer::factory()->for($device)->create([
        'bgpPeerIdentifier' => '192.0.2.1',
        'bgpPeerRemoteAs' => 64512,
        'bgpPeerDescr' => 'Upstream <b>Transit</b>',
        'context_name' => 'vrf-blue',
    ]);

    $this->actingAs(routingAdmin())
        ->get(route('device.edit.routing', $device))
        ->assertOk()
        ->assertSee('vrf-red')
        ->assertSee('vrf-blue')
        ->assertSee('192.0.2.1')
        ->assertSee('64512')
        ->assertSee('Upstream &lt;b&gt;Transit&lt;/b&gt;', false)
        ->assertDontSee('Upstream <b>Transit</b>', false);
});

test('routing page shows context status', function (): void {
    $device = Device::factory()->create();
    Vrf::factory()->for($device)->create(['vrf_name' => 'red', 'context_name' => 'vrf-red', 'context_verified' => true]);
    Vrf::factory()->for($device)->create(['vrf_name' => 'blue', 'context_name' => 'vrf-blue', 'context_manual' => true, 'context_verified' => false, 'context_failures' => 1]);
    Vrf::factory()->for($device)->create(['vrf_oid' => 'vrf-pending', 'vrf_name' => 'vrf-pending', 'context_name' => 'vrf-pending', 'context_manual' => true, 'vrf_manual' => true]);
    Vrf::factory()->for($device)->create(['vrf_name' => 'green', 'context_name' => 'vrf-green', 'context_verified' => true, 'context_enabled' => false]);
    Vrf::factory()->for($device)->create(['vrf_name' => 'mgmt', 'context_name' => null]);

    $vrfs = $this->actingAs(routingAdmin())
        ->get(route('device.edit.routing', $device))
        ->assertOk()
        ->viewData('vrfs');

    expect(array_map(fn ($vrf) => [$vrf['name'], $vrf['context'], $vrf['device_context'], $vrf['context_only'], $vrf['editable'], $vrf['status']], $vrfs))->toBe([
        ['blue', 'vrf-blue', false, false, true, 'failing'],
        ['green', 'vrf-green', true, false, false, 'disabled'],
        ['mgmt', null, false, false, true, 'none'],
        ['red', 'vrf-red', true, false, false, 'answering'],
        ['vrf-pending', 'vrf-pending', false, true, false, 'waiting'],
    ]);
});

test('bgp peers panel is only shown with peers', function (): void {
    $device = Device::factory()->create();
    Vrf::factory()->for($device)->create();
    $admin = routingAdmin();

    $this->actingAs($admin)
        ->get(route('device.edit.routing', $device))
        ->assertOk()
        ->assertDontSee('BGP Peers');

    BgpPeer::factory()->for($device)->create();

    $this->actingAs($admin)
        ->get(route('device.edit.routing', $device))
        ->assertOk()
        ->assertSee('BGP Peers');
});

test('routing tab is shown for devices with vrfs', function (): void {
    $device = Device::factory()->create();
    Vrf::factory()->for($device)->create();

    $this->actingAs(routingAdmin())
        ->get(route('device.edit', $device))
        ->assertOk()
        ->assertSee(route('device.edit.routing', $device->device_id));
});

test('user cannot view routing page', function (): void {
    $device = Device::factory()->create();

    $this->actingAs(routingUser())
        ->get(route('device.edit.routing', $device))
        ->assertForbidden();
});

test('update vrf enables and disables contexts', function (): void {
    $device = Device::factory()->create();
    $red = Vrf::factory()->for($device)->create(['context_name' => 'vrf-red', 'context_enabled' => true, 'context_verified' => true]);

    $this->actingAs(routingAdmin())
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $red]), ['context_enabled' => false])
        ->assertOk()
        ->assertJson(['enabled' => false, 'status' => 'disabled']);
    expect($red->fresh()->context_enabled)->toBeFalse();

    $this->actingAs(routingAdmin())
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $red]), ['context_enabled' => true])
        ->assertOk()
        ->assertJson(['enabled' => true, 'status' => 'answering']);
    expect($red->fresh()->context_enabled)->toBeTrue();
});

test('vrf must belong to device', function (): void {
    $device = Device::factory()->create();
    $other = Vrf::factory()->for(Device::factory())->create(['context_name' => 'vrf-other', 'context_enabled' => true, 'context_manual' => true, 'vrf_manual' => true]);

    $this->actingAs(routingAdmin())
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $other]), ['context_enabled' => false])
        ->assertNotFound();
    $this->actingAs(routingAdmin())
        ->deleteJson(route('device.edit.routing.vrf.destroy', [$device, $other]))
        ->assertNotFound();

    expect($other->fresh()->context_enabled)->toBeTrue();
});

test('update vrf sets and clears contexts', function (): void {
    $device = Device::factory()->create();
    $blue = Vrf::factory()->for($device)->create(['vrf_name' => 'blue', 'context_name' => null]);
    Vrf::factory()->for($device)->create(['vrf_name' => 'green', 'context_name' => 'ctx-green']);
    $admin = routingAdmin();

    $this->actingAs($admin)
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $blue]), ['context_name' => ' ctx-blue '])
        ->assertOk()
        ->assertJson(['context' => 'ctx-blue', 'device_context' => false, 'editable' => true, 'status' => 'waiting']);
    $blue->refresh();
    expect([$blue->context_name, $blue->context_manual, $blue->vrf_manual])->toBe(['ctx-blue', true, false]);

    $this->actingAs($admin)
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $blue]), ['context_name' => 'ctx-green'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['context_name' => 'Context ctx-green is used by VRF green.']);
    expect($blue->fresh()->context_name)->toBe('ctx-blue');

    $this->actingAs($admin)
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $blue]), ['context_name' => 'CTX-GREEN'])
        ->assertOk()
        ->assertJson(['context' => 'CTX-GREEN']);

    $this->actingAs($admin)
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $blue]), ['context_name' => ''])
        ->assertOk()
        ->assertJson(['context' => null, 'editable' => true, 'status' => 'none']);
    expect($blue->fresh()->context_name)->toBeNull();
});

test('update vrf only changes contexts users set', function (): void {
    $device = Device::factory()->create();
    $mapped = Vrf::factory()->for($device)->create(['vrf_name' => 'green', 'context_name' => 'ctx-green']);
    $contextOnly = Vrf::factory()->for($device)->create(['vrf_oid' => 'red', 'vrf_name' => 'red', 'context_name' => 'red', 'context_manual' => true, 'vrf_manual' => true]);
    $default = Vrf::factory()->for($device)->create(['vrf_name' => 'default', 'context_name' => null]);
    $admin = routingAdmin();

    foreach ([$mapped, $contextOnly, $default] as $vrf) {
        foreach (['other', null] as $context) {
            $this->actingAs($admin)
                ->patchJson(route('device.edit.routing.vrf.update', [$device, $vrf]), ['context_name' => $context])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('context_name');
        }
    }

    expect($mapped->fresh()->context_name)->toBe('ctx-green');
    expect($contextOnly->fresh()->context_name)->toBe('red');
    expect($default->fresh()->context_name)->toBeNull();
});

test('store context adds vrfs the device does not list', function (): void {
    $device = Device::factory()->create();
    Vrf::factory()->for($device)->create(['vrf_name' => 'Blue', 'context_name' => null]);
    Vrf::factory()->for($device)->create(['vrf_name' => 'green', 'context_name' => 'ctx-green']);
    $admin = routingAdmin();

    $this->actingAs($admin)
        ->postJson(route('device.edit.routing.vrf.store', $device), ['context' => ' vrf-new '])
        ->assertOk()
        ->assertJsonCount(3, 'vrfs')
        ->assertJson(['message' => 'VRF vrf-new added']);
    $new = $device->vrfs()->firstWhere('vrf_name', 'vrf-new');
    expect([$new->vrf_oid, $new->context_name, $new->context_manual, $new->vrf_manual])->toBe(['context:vrf-new', 'vrf-new', true, true]);

    foreach ([
        'blue' => 'VRF Blue exists, set its context in the table.',
        'ctx-green' => 'Context ctx-green is used by VRF green.',
        'vrf-new' => 'Context vrf-new is used by VRF vrf-new.',
        'default' => 'The default VRF is polled without a context.',
    ] as $context => $error) {
        $this->actingAs($admin)
            ->postJson(route('device.edit.routing.vrf.store', $device), ['context' => $context])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['context' => $error]);
    }

    $this->actingAs($admin)
        ->postJson(route('device.edit.routing.vrf.store', $device), ['context' => ' '])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('context');

    expect($device->vrfs()->count())->toBe(3);
});

test('destroy vrf removes vrfs only known through their context', function (): void {
    $device = Device::factory()->create();
    $created = Vrf::factory()->for($device)->create(['vrf_oid' => 'vrf-red', 'vrf_name' => 'vrf-red', 'context_name' => 'vrf-red', 'context_manual' => true, 'vrf_manual' => true]);
    $attached = Vrf::factory()->for($device)->create(['vrf_name' => 'blue', 'context_name' => 'ctx-blue', 'context_manual' => true, 'context_verified' => true]);
    $mapped = Vrf::factory()->for($device)->create(['vrf_name' => 'green', 'context_name' => 'ctx-green']);
    $admin = routingAdmin();

    $this->actingAs($admin)
        ->deleteJson(route('device.edit.routing.vrf.destroy', [$device, $created]))
        ->assertOk()
        ->assertJsonCount(2, 'vrfs');
    expect($created->fresh())->toBeNull('VRFs only known through their context are deleted');

    foreach ([$attached, $mapped] as $listed) {
        $this->actingAs($admin)
            ->deleteJson(route('device.edit.routing.vrf.destroy', [$device, $listed]))
            ->assertUnprocessable();
    }

    expect($attached->fresh()->context_name)->toBe('ctx-blue', 'VRFs the device lists are not removed');
    expect($mapped->fresh()->context_name)->toBe('ctx-green');
});

test('user cannot update contexts', function (): void {
    $device = Device::factory()->create();
    $vrf = Vrf::factory()->for($device)->create(['context_name' => 'vrf-red', 'context_manual' => true, 'vrf_manual' => true]);
    $user = routingUser();

    $this->actingAs($user)
        ->postJson(route('device.edit.routing.vrf.store', $device), ['context' => 'vrf-blue'])
        ->assertForbidden();
    $this->actingAs($user)
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $vrf]), ['context_enabled' => false])
        ->assertForbidden();
    $this->actingAs($user)
        ->patchJson(route('device.edit.routing.vrf.update', [$device, $vrf]), ['context_name' => 'vrf-blue'])
        ->assertForbidden();
    $this->actingAs($user)
        ->deleteJson(route('device.edit.routing.vrf.destroy', [$device, $vrf]))
        ->assertForbidden();

    expect($device->vrfs()->count())->toBe(1);
    expect($vrf->fresh()->context_enabled)->toBeTrue();
});

test('admin can update peer description', function (): void {
    $device = Device::factory()->create();
    $peer = BgpPeer::factory()->for($device)->create(['bgpPeerDescr' => 'old']);

    $this->actingAs(routingAdmin())
        ->postJson(route('device.edit.routing.peer.update', [$device, $peer]), ['descr' => 'new description'])
        ->assertOk()
        ->assertJson(['status' => 'ok']);

    expect($peer->fresh()->bgpPeerDescr)->toBe('new description');
});

test('clearing peer description stores empty string', function (): void {
    $device = Device::factory()->create();
    $peer = BgpPeer::factory()->for($device)->create(['bgpPeerDescr' => 'old']);

    $this->actingAs(routingAdmin())
        ->postJson(route('device.edit.routing.peer.update', [$device, $peer]), ['descr' => ''])
        ->assertOk();

    expect($peer->fresh()->bgpPeerDescr)->toBe('');
});

test('peer must belong to device', function (): void {
    $device = Device::factory()->create();
    $otherPeer = BgpPeer::factory()->for(Device::factory())->create(['bgpPeerDescr' => 'old']);

    $this->actingAs(routingAdmin())
        ->postJson(route('device.edit.routing.peer.update', [$device, $otherPeer]), ['descr' => 'hijack'])
        ->assertNotFound();

    expect($otherPeer->fresh()->bgpPeerDescr)->toBe('old');
});

test('user cannot update peer description', function (): void {
    $device = Device::factory()->create();
    $peer = BgpPeer::factory()->for($device)->create(['bgpPeerDescr' => 'old']);

    $this->actingAs(routingUser())
        ->postJson(route('device.edit.routing.peer.update', [$device, $peer]), ['descr' => 'new'])
        ->assertForbidden();

    expect($peer->fresh()->bgpPeerDescr)->toBe('old');
});