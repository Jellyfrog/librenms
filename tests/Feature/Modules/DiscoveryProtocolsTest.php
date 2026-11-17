<?php

/**
 * DiscoveryProtocolsTest.php
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2026 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Actions\Device\AutoDiscoverDevice;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Port;
use Illuminate\Support\Collection;
use LibreNMS\Discovery\Neighbors\Neighbor;
use LibreNMS\Enum\LldpPortIdSubtype;
use LibreNMS\Modules\DiscoveryProtocols;
use LibreNMS\OS;
use Mockery\MockInterface;

uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

beforeEach(function (): void {
    LibrenmsConfig::set('autodiscovery.xdp', false);
    LibrenmsConfig::set('autodiscovery.ospf', false);
    LibrenmsConfig::set('autodiscovery.ospfv3', false);
    LibrenmsConfig::set('discovery_by_ip', false);
    LibrenmsConfig::set('autodiscovery.xdp_exclude.sysname_regexp', []);
    LibrenmsConfig::set('autodiscovery.xdp_exclude.sysdesc_regexp', []);
    LibrenmsConfig::set('autodiscovery.cdp_exclude.platform_regexp', []);

    $this->device = Device::factory()->create(['hostname' => 'local.dp.test', 'ip' => null, 'os' => 'generic']);
    $this->localPort = Port::factory()->create(['device_id' => $this->device->device_id, 'ifName' => 'Gi0/1', 'ifIndex' => 1]);
});

test('links are synced', function (): void {
    $module = new DiscoveryProtocols;

    $module->discover(dpOs(lldp: [
        neighbor(['sysName' => 'switch-a', 'portId' => 'eth1', 'sysDescr' => 'Switch A']),
        neighbor(['sysName' => 'switch-b', 'portId' => 'eth2']),
    ], cdp: [
        neighbor(['protocol' => 'cdp', 'sysName' => 'router-c', 'portId' => 'Gi0/0', 'platform' => 'cisco ISR4331']),
    ]));

    $links = $this->device->links()->orderBy('remote_hostname')->get();
    expect($links->pluck('remote_hostname')->all())->toBe(['router-c', 'switch-a', 'switch-b']);
    expect($links->pluck('protocol')->all())->toBe(['cdp', 'lldp', 'lldp']);
    expect($links->pluck('remote_platform')->all())->toBe(['cisco ISR4331', null, null]);
    expect($links[1]->remote_version)->toBe('Switch A');
    expect($links->pluck('local_port_id')->unique()->values()->all())->toEqual([$this->localPort->port_id]);
    expect($links->pluck('remote_device_id')->unique()->values()->all())->toEqual([0]);
    expect($module->dataExists($this->device))->toBeTrue();

    $kept_id = $links[1]->id;
    $module->discover(dpOs(lldp: [
        neighbor(['sysName' => 'switch-a', 'portId' => 'eth1', 'sysDescr' => 'Switch A v2']),
    ]));

    $link = $this->device->links()->sole();
    expect($link->id)->toBe($kept_id, 'Existing link should be updated, not replaced');
    expect($link->remote_version)->toBe('Switch A v2');

    expect($module->cleanup($this->device))->toBe(1);
    expect($module->dataExists($this->device))->toBeFalse();
});

test('links to known devices and ports', function (): void {
    $remote = Device::factory()->create(['hostname' => 'remote.dp.test', 'sysName' => 'remote', 'ip' => null, 'os' => 'generic']);
    $remotePort = Port::factory()->create(['device_id' => $remote->device_id, 'ifName' => 'xe-0/0/1', 'ifIndex' => 501]);

    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        neighbor(['sysName' => 'remote.dp.test', 'portId' => 'xe-0/0/1']),
        // no name advertised, found by mac, named after the device
        neighbor(['chassisMac' => 'feed00aa0001', 'portId' => 'xe-0/0/1']),
    ]));
    Port::where('port_id', $remotePort->port_id)->update(['ifPhysAddress' => 'feed00aa0001']);
    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        neighbor(['sysName' => 'remote.dp.test', 'portId' => 'xe-0/0/1']),
        neighbor(['chassisMac' => 'feed00aa0001', 'portId' => 'xe-0/0/1']),
    ]));

    $links = $this->device->links()->orderBy('remote_hostname')->get();
    expect($links->pluck('remote_hostname')->all())->toBe(['remote', 'remote.dp.test']);
    expect($links->pluck('remote_device_id')->all())->toEqual([$remote->device_id, $remote->device_id]);
    expect($links->pluck('remote_port_id')->all())->toEqual([$remotePort->port_id, $remotePort->port_id]);
});

/** @return list<array{string}> */
dataset('reusedAddressNames', function () {
    return [['site1-access'], ['192.168.1.11']];
});

test('lldp link uses mac when management ip is reused across sites', function (string $sysName): void {
    $remote = Device::factory()->create(['hostname' => '172.19.7.11', 'ip' => '172.19.7.11', 'sysName' => 'site1-access', 'os' => 'generic']);
    $otherSite = Device::factory()->create(['hostname' => '192.168.1.11', 'ip' => '192.168.1.11']);
    $remotePort = Port::factory()->create([
        'device_id' => $remote->device_id,
        'ifName' => 'eth0',
        'ifPhysAddress' => 'feed00020962',
    ]);
    $otherPort = Port::factory()->create(['device_id' => $otherSite->device_id]);
    foreach ([$remotePort, $otherPort] as $port) {
        Ipv4Address::factory()->create(['port_id' => $port->port_id, 'ipv4_address' => '192.168.1.11']);
    }

    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        neighbor([
            'sysName' => $sysName,
            'managementIp' => '192.168.1.11',
            'portId' => 'feed00020962',
            'portIdSubtype' => LldpPortIdSubtype::MacAddress,
            'portMac' => 'feed00020962',
        ]),
    ]));

    $link = $this->device->links()->sole();
    expect($link->remote_device_id)->toBe($remote->device_id);
    expect($link->remote_port_id)->toBe($remotePort->port_id);
})->with('reusedAddressNames');

test('neighbors without local port or name are skipped', function (): void {
    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        new Neighbor('lldp', null, sysName: 'no-local-port', portId: 'eth0'),
        neighbor(['portId' => 'eth0']), // no name
        neighbor(['sysDescr' => 'HPE ProLiant DL360 Gen10']), // only a description
    ]));

    $link = $this->device->links()->sole();
    expect($link->remote_hostname)->toBe('HPE ProLiant DL360 Gen10');
    expect($link->remote_port)->toBe('');
});

test('remote port label', function (): void {
    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        neighbor(['sysName' => 'by-name', 'portId' => 'eth0', 'portDescr' => 'uplink']),
        neighbor(['sysName' => 'by-mac', 'portId' => 'aca31ec34be6', 'portIdSubtype' => LldpPortIdSubtype::MacAddress, 'portMac' => 'aca31ec34be6']),
        neighbor(['sysName' => 'by-descr', 'portDescr' => 'PCI-E Slot 3, Port 1']),
        neighbor(['sysName' => 'vmware', 'portId' => 'vmnic5', 'portMac' => '000af7ecd161']),
    ]));

    expect($this->device->links()->orderBy('remote_hostname')->pluck('remote_port', 'remote_hostname')->all())->toBe(['by-descr' => 'PCI-E Slot 3, Port 1', 'by-mac' => 'ac:a3:1e:c3:4b:e6', 'by-name' => 'eth0', 'vmware' => 'vmnic5']);
});

test('autodiscovery', function (): void {
    LibrenmsConfig::set('autodiscovery.xdp', true);
    LibrenmsConfig::set('discovery_by_ip', true);
    $added = Device::factory()->create(['hostname' => 'added.dp.test', 'ip' => null, 'os' => 'generic']);

    mockAutoDiscover(function (MockInterface $mock) use ($added): void {
        // each target is only attempted once, even if seen on several ports
        $mock->shouldReceive('execute')->once()->with('unresolvable', Mockery::any(), 'LLDP', Mockery::type(Port::class))->andReturnNull();
        $mock->shouldReceive('execute')->once()->with('192.0.2.10', Mockery::any(), 'LLDP', Mockery::type(Port::class))->andReturn($added);
        $mock->shouldReceive('execute')->once()->with('new-cdp', Mockery::any(), 'CDP', Mockery::type(Port::class))->andReturnNull();
    });

    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        neighbor(['sysName' => 'unresolvable', 'managementIp' => '192.0.2.10', 'portId' => 'eth0']),
        neighbor(['sysName' => 'unresolvable', 'managementIp' => '192.0.2.10', 'portId' => 'eth1']),
    ], cdp: [
        neighbor(['protocol' => 'cdp', 'sysName' => 'new-cdp', 'portId' => 'Gi0/0']),
    ]));

    expect($this->device->links()->orderBy('protocol', 'desc')->orderBy('remote_port')->pluck('remote_device_id')->all())->toEqual([$added->device_id, $added->device_id, 0]);
});

test('ambiguous known ip is not autodiscovered but new names still are', function (): void {
    LibrenmsConfig::set('autodiscovery.xdp', true);
    LibrenmsConfig::set('discovery_by_ip', true);
    foreach (range(1, 2) as $index) {
        $remote = Device::factory()->create(['hostname' => $index === 1 ? '192.0.2.209' : "known-$index.dp.test", 'sysName' => '192.0.2.209']);
        $port = Port::factory()->create(['device_id' => $remote->device_id]);
        Ipv4Address::factory()->create(['port_id' => $port->port_id, 'ipv4_address' => '192.0.2.209']);
    }
    mockAutoDiscover(function (MockInterface $mock): void {
        $mock->shouldReceive('execute')->once()->with('new.dp.test', Mockery::any(), 'LLDP', Mockery::any())->andReturnNull();
    });

    $module = new DiscoveryProtocols;
    $module->discover(dpOs(lldp: [
        neighbor(['managementIp' => '192.0.2.209', 'sysDescr' => 'Unknown switch']),
        neighbor(['managementIp' => '192.0.2.209', 'sysName' => '192.0.2.209', 'portId' => 'eth2']),
        neighbor(['managementIp' => '192.0.2.209', 'sysName' => 'new.dp.test', 'portId' => 'eth0']),
        neighbor(['managementIp' => '192.0.2.209', 'sysName' => 'new.dp.test', 'portId' => 'eth1']),
    ]));
    expect($this->device->links()->count())->toBe(4);
    expect($this->device->links()->pluck('remote_device_id')->unique()->values()->all())->toEqual([0]);
});

test('autodiscovery without local port or remote port', function (): void {
    LibrenmsConfig::set('autodiscovery.xdp', true);

    mockAutoDiscover(function (MockInterface $mock): void {
        $mock->shouldReceive('execute')->once()->with('no-local-port', Mockery::any(), 'LLDP', null)->andReturnNull();
        $mock->shouldReceive('execute')->once()->with('cdp-phone', Mockery::any(), 'CDP', Mockery::type(Port::class))->andReturnNull();
    });

    (new DiscoveryProtocols)->discover(dpOs(lldp: [new Neighbor('lldp', null, sysName: 'no-local-port', portId: 'eth0')], cdp: [neighbor(['protocol' => 'cdp', 'sysName' => 'cdp-phone'])]));

    // only the neighbor with a local port gets a link
    $link = $this->device->links()->sole();
    expect($link->remote_hostname)->toBe('cdp-phone');
    expect($link->remote_port)->toBe('');
});

test('autodiscovery targets are normalized', function (): void {
    LibrenmsConfig::set('autodiscovery.xdp', true);
    LibrenmsConfig::set('discovery_by_ip', true);

    mockAutoDiscover(function (MockInterface $mock): void {
        $mock->shouldReceive('execute')->once()->with('New-Switch', Mockery::any(), 'LLDP', Mockery::any())->andReturnNull();
        $mock->shouldReceive('execute')->once()->with('2001:db8::10', Mockery::any(), 'LLDP', Mockery::any())->andReturnNull();
    });

    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        neighbor(['sysName' => 'New-Switch', 'managementIp' => '2001:db8::10', 'portId' => 'eth0']),
        neighbor(['sysName' => 'new-switch.', 'managementIp' => '2001:0db8:0000:0000:0000:0000:0000:0010', 'portId' => 'eth1']),
    ]));

    expect($this->device->links()->count())->toBe(2);
});

test('autodiscovery exclusions', function (): void {
    LibrenmsConfig::set('autodiscovery.xdp', true);
    LibrenmsConfig::set('autodiscovery.xdp_exclude.sysname_regexp', ['/^phone-/']);
    LibrenmsConfig::set('autodiscovery.xdp_exclude.sysdesc_regexp', ['/linux/']);
    LibrenmsConfig::set('autodiscovery.cdp_exclude.platform_regexp', ['/^cisco ip phone/']);

    mockAutoDiscover(function (MockInterface $mock): void {
        $mock->shouldReceive('execute')->once()->with('allowed', Mockery::any(), 'LLDP', Mockery::any())->andReturnNull();
    });

    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        neighbor(['sysName' => 'PHONE-1234', 'portId' => 'eth0']),
        neighbor(['sysName' => 'server1', 'sysDescr' => 'Debian GNU/Linux', 'portId' => 'eth0']),
        neighbor(['sysName' => 'SEP0011', 'platform' => 'Cisco IP Phone 8841', 'portId' => 'eth0']),
        neighbor(['sysName' => 'allowed', 'portId' => 'eth0']),
    ]));

    expect($this->device->links()->count())->toBe(4);
});

test('no autodiscovery when disabled', function (): void {
    mockAutoDiscover(fn (MockInterface $mock) => $mock->shouldNotReceive('execute'));

    (new DiscoveryProtocols)->discover(dpOs(lldp: [
        neighbor(['sysName' => 'unknown', 'managementIp' => '192.0.2.10', 'portId' => 'eth0']),
    ]));

    expect($this->device->links()->count())->toBe(1);
});

/**
 * @param  array<string, mixed>  $args
 */
function neighbor(array $args): Neighbor
{
    return new Neighbor(...['protocol' => 'lldp', 'localPortId' => test()->localPort->port_id, ...$args]);
}

/**
 * @param  Neighbor[]  $lldp
 * @param  Neighbor[]  $cdp
 */
function dpOs(array $lldp = [], array $cdp = []): OS
{
    $os = Mockery::mock(OS::class);
    $os->shouldReceive('getDevice')->andReturn(test()->device);
    $os->shouldReceive('discoverNeighbors')->andReturn(new Collection([...$cdp, ...$lldp]));

    /** @var OS $os */
    return $os;
}

function mockAutoDiscover(callable $expectations): void
{
    app()->instance(AutoDiscoverDevice::class, Mockery::mock(AutoDiscoverDevice::class, $expectations));
}