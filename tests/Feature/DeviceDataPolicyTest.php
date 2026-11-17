<?php

use App\Models\AlertOperationSegment;
use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Ipv4Network;
use App\Models\OspfArea;
use App\Models\Port;
use App\Models\PortVlan;
use App\Models\Sla;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('admin');
    Role::findOrCreate('global-read');
    Role::findOrCreate('user');
    foreach (['device', 'diskio', 'port', 'routing'] as $prefix) {
        Permission::findOrCreate("$prefix.view");
        Permission::findOrCreate("$prefix.viewAll");
    }

    $this->accessible = Device::factory()->create();
    $this->other = Device::factory()->create();
});

/**
 * Every model with a policy in this group, with its global permission prefix
 *
 * @return array<string, array{class-string<Model>, string}>
 */
dataset('policyModels', function () {
    return [
        'AccessPoint' => [\App\Models\AccessPoint::class, 'device'],
        'Availability' => [\App\Models\Availability::class, 'device'],
        'CefSwitching' => [\App\Models\CefSwitching::class, 'device'],
        'DiskIo' => [\App\Models\DiskIo::class, 'diskio'],
        'IpsecTunnel' => [\App\Models\IpsecTunnel::class, 'device'],
        'Sla' => [\App\Models\Sla::class, 'device'],
        'Stp' => [\App\Models\Stp::class, 'device'],
        'MplsLsp' => [\App\Models\MplsLsp::class, 'routing'],
        'MplsLspPath' => [\App\Models\MplsLspPath::class, 'routing'],
        'MplsSap' => [\App\Models\MplsSap::class, 'routing'],
        'MplsSdp' => [\App\Models\MplsSdp::class, 'routing'],
        'MplsSdpBind' => [\App\Models\MplsSdpBind::class, 'routing'],
        'MplsService' => [\App\Models\MplsService::class, 'routing'],
        'MplsTunnelArHop' => [\App\Models\MplsTunnelArHop::class, 'routing'],
        'MplsTunnelCHop' => [\App\Models\MplsTunnelCHop::class, 'routing'],
        'OspfArea' => [\App\Models\OspfArea::class, 'routing'],
        'OspfInstance' => [\App\Models\OspfInstance::class, 'routing'],
        'OspfNbr' => [\App\Models\OspfNbr::class, 'routing'],
        'OspfPort' => [\App\Models\OspfPort::class, 'routing'],
        'Ospfv3Area' => [\App\Models\Ospfv3Area::class, 'routing'],
        'Ospfv3Instance' => [\App\Models\Ospfv3Instance::class, 'routing'],
        'Ospfv3Nbr' => [\App\Models\Ospfv3Nbr::class, 'routing'],
        'Ospfv3Port' => [\App\Models\Ospfv3Port::class, 'routing'],
        'Pseudowire' => [\App\Models\Pseudowire::class, 'routing'],
        'Ipv4Address' => [\App\Models\Ipv4Address::class, 'port'],
        'Ipv4Mac' => [\App\Models\Ipv4Mac::class, 'port'],
        'Ipv4Network' => [\App\Models\Ipv4Network::class, 'port'],
        'Ipv6Address' => [\App\Models\Ipv6Address::class, 'port'],
        'Ipv6Nd' => [\App\Models\Ipv6Nd::class, 'port'],
        'Ipv6Network' => [\App\Models\Ipv6Network::class, 'port'],
        'PortAdsl' => [\App\Models\PortAdsl::class, 'port'],
        'PortSecurity' => [\App\Models\PortSecurity::class, 'port'],
        'PortsFdb' => [\App\Models\PortsFdb::class, 'port'],
        'PortStack' => [\App\Models\PortStack::class, 'port'],
        'PortStatistic' => [\App\Models\PortStatistic::class, 'port'],
        'PortStp' => [\App\Models\PortStp::class, 'port'],
        'PortVdsl' => [\App\Models\PortVdsl::class, 'port'],
        'PortVlan' => [\App\Models\PortVlan::class, 'port'],
        'Transceiver' => [\App\Models\Transceiver::class, 'port'],
    ];
});

test('view any by role and permission', function (string $model, string $prefix): void {
    expect(Gate::getPolicyFor($model))->not->toBeNull();

    expect(Gate::forUser(policyUser('user'))->allows('viewAny', $model))->toBeTrue('user role');
    expect(Gate::forUser(policyUser('global-read'))->allows('viewAny', $model))->toBeTrue('global-read role');
    expect(Gate::forUser(policyUser())->allows('viewAny', $model))->toBeFalse('no role');
    expect(Gate::forUser(policyUser(null, "$prefix.view"))->allows('viewAny', $model))->toBeTrue("$prefix.view");
    expect(Gate::forUser(policyUser(null, "$prefix.viewAll"))->allows('viewAll', $model))->toBeTrue("$prefix.viewAll");
})->with('policyModels');

test('device scoped view requires device access', function (): void {
    $user = policyUser('user');
    $user->devicesOwned()->attach($this->accessible->device_id);

    expect(Gate::forUser($user)->allows('view', onDevice(Sla::class, $this->accessible)))->toBeTrue();
    expect(Gate::forUser($user)->allows('view', onDevice(Sla::class, $this->other)))->toBeFalse();
    expect(Gate::forUser($user)->allows('view', onDevice(OspfArea::class, $this->accessible)))->toBeTrue();
    expect(Gate::forUser($user)->allows('view', onDevice(OspfArea::class, $this->other)))->toBeFalse();
});

test('view all sees every device', function (): void {
    $user = policyUser(null, 'device.viewAll');

    expect(Gate::forUser($user)->allows('view', onDevice(Sla::class, $this->other)))->toBeTrue();
});

test('port scoped view by device or port access', function (): void {
    $accessiblePort = Port::factory()->create(['device_id' => $this->accessible->device_id]);
    $otherPort = Port::factory()->create(['device_id' => $this->other->device_id]);
    $ownedPort = Port::factory()->create(['device_id' => $this->other->device_id]);

    $user = policyUser('user');
    $user->devicesOwned()->attach($this->accessible->device_id);
    $user->portsOwned()->attach($ownedPort->port_id);
    $gate = Gate::forUser($user);

    // no device_id column, device comes from the port
    expect($gate->allows('view', Ipv4Address::factory()->make(['port_id' => $accessiblePort->port_id])))->toBeTrue();
    expect($gate->allows('view', Ipv4Address::factory()->make(['port_id' => $ownedPort->port_id])))->toBeTrue();
    expect($gate->allows('view', Ipv4Address::factory()->make(['port_id' => $otherPort->port_id])))->toBeFalse();

    $vlan = fn (Port $port) => (new PortVlan)->forceFill(['device_id' => $port->device_id, 'port_id' => $port->port_id]);
    expect($gate->allows('view', $vlan($accessiblePort)))->toBeTrue();
    expect($gate->allows('view', $vlan($ownedPort)))->toBeTrue();
    expect($gate->allows('view', $vlan($otherPort)))->toBeFalse();
});

test('network view requires access to aconnected port', function (): void {
    $accessiblePort = Port::factory()->create(['device_id' => $this->accessible->device_id]);
    $otherPort = Port::factory()->create(['device_id' => $this->other->device_id]);
    $visible = Ipv4Network::factory()->create();
    $hidden = Ipv4Network::factory()->create();
    Ipv4Address::factory()->create(['port_id' => $accessiblePort->port_id, 'ipv4_network_id' => $visible->ipv4_network_id]);
    Ipv4Address::factory()->create(['port_id' => $otherPort->port_id, 'ipv4_network_id' => $hidden->ipv4_network_id]);

    $user = policyUser('user');
    $user->devicesOwned()->attach($this->accessible->device_id);

    expect(Gate::forUser($user)->allows('view', $visible))->toBeTrue();
    expect(Gate::forUser($user)->allows('view', $hidden))->toBeFalse();
});

test('alert operation segment matches alert operation', function (): void {
    Permission::findOrCreate('alert-rule.view');
    $user = policyUser('user');

    expect(Gate::forUser($user)->allows('viewAny', AlertOperationSegment::class))->toBe(Gate::forUser($user)->allows('viewAny', \App\Models\AlertOperation::class));
    expect(Gate::forUser($user)->allows('viewAny', AlertOperationSegment::class))->toBeTrue();
    expect(Gate::forUser($user)->allows('view', new AlertOperationSegment))->toBeFalse();
    expect(Gate::forUser(policyUser(null, 'alert-rule.view'))->allows('view', new AlertOperationSegment))->toBeTrue();
});

function policyUser(?string $role = null, ?string $permission = null): User
{
    $user = User::factory()->create(['enabled' => 1]);
    if ($role) {
        $user->assignRole($role);
    }
    if ($permission) {
        $user->givePermissionTo($permission);
    }

    return $user;
}

/**
 * @template T of Model
 *
 * @param  class-string<T>  $model
 * @return T
 */
function onDevice(string $model, Device $device): Model
{
    return (new $model)->forceFill(['device_id' => $device->device_id]);
}