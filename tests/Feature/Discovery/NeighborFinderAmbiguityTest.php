<?php

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Models\Device;
use App\Models\Ipv4Address;
use App\Models\Ipv6Address;
use App\Models\Port;
use LibreNMS\Discovery\Neighbors\Neighbor;
use LibreNMS\Discovery\Neighbors\NeighborFinder;
use LibreNMS\Util\IP;
use PHPUnit\Framework\Attributes\DataProvider;


uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);

/** @return list<array{string}> */
dataset('addresses', function () {
    return [['198.51.100.209'], ['2001:db8::209']];
});

/** @return array<string, array{string, string, string}> */
dataset('addressNames', function () {
    $cases = [];
    foreach (['portMac', 'chassisMac'] as $identifier) {
        $cases["IPv4 $identifier"] = ['192.168.1.11', '192.168.1.11', $identifier];
        $cases["IPv6 $identifier"] = ['2001:db8::11', '2001:0db8:0000:0000:0000:0000:0000:0011', $identifier];
    }

    return $cases;
});

test('ambiguous address name does not override mac', function (string $ip, string $name, string $identifier) {
    $remote = owner($ip, device: Device::factory()->create(['hostname' => '172.19.7.11', 'ip' => '172.19.7.11', 'sysName' => 'site1-access']));
    owner($ip, device: Device::factory()->create(['hostname' => $ip, 'ip' => $ip, 'sysName' => $name]));
    $finder = new NeighborFinder;

    $neighbor = new Neighbor('lldp', null, ...[
        'sysName' => $name,
        'managementIp' => $ip,
        $identifier => str_replace(':', '', (string) $remote->ifPhysAddress),
    ]);
    $actual = $finder->findDevice($neighbor);
    expect($actual?->device_id)->toBe($remote->device_id, "Expected NAT device B (172.19.7.11), got {$actual?->hostname}");
    expect($finder->findDevice(new Neighbor('lldp', null, sysName: $name, managementIp: $ip)))->toBeNull();
    expect($finder->findDevice($neighbor)?->device_id)->toBe($remote->device_id);
})->with('addressNames');

test('ambiguous address name is not independent name evidence', function (string $ip) {
    $remote = owner($ip);
    $direct = Device::factory()->create(['hostname' => $ip, 'ip' => $ip, 'sysName' => $ip]);
    owner($ip, device: $direct);
    $finder = new NeighborFinder;

    foreach (['lldp' => null, 'cdp' => $direct->device_id, 'fdp' => $direct->device_id, 'lldp-again' => null] as $protocol => $expected) {
        $neighbor = new Neighbor($protocol === 'lldp-again' ? 'lldp' : $protocol, null, sysName: $ip, managementIp: $ip);
        expect($finder->findDevice($neighbor)?->device_id)->toBe($expected);
    }

    $withMac = new Neighbor('lldp', null, sysName: $ip, managementIp: $ip, portMac: str_replace(':', '', (string) $remote->ifPhysAddress));
    expect($finder->findDevice($withMac)?->device_id)->toBe($remote->device_id);
})->with('addresses');

test('protocol compatibility and cache isolation', function (string $ip) {
    $direct = Device::factory()->create(['hostname' => $ip, 'ip' => $ip]);
    owner($ip);
    $finder = new NeighborFinder;

    // CDP/FDP supply no MAC identifiers; preserve the unique direct management match.
    foreach (['lldp' => null, 'cdp' => $direct->device_id, 'fdp' => $direct->device_id, 'lldp-again' => null] as $protocol => $expected) {
        $neighbor = new Neighbor($protocol === 'lldp-again' ? 'lldp' : $protocol, null, sysName: 'serial-id.invalid', managementIp: $ip);
        expect($finder->findDevice($neighbor)?->device_id)->toBe($expected);
    }

    expect($finder->findDevice(new Neighbor('cdp', null, managementIp: $ip, portMac: 'feedffffffff')))->toBeNull();
})->with('addresses');

test('deleted ports cannot create ambiguity or supply mac evidence', function (string $ip) {
    $direct = Device::factory()->create(['hostname' => $ip, 'ip' => $ip]);
    $stale = owner($ip, deleted: true);
    $finder = new NeighborFinder;
    expect($finder->findDevice(new Neighbor('lldp', null, managementIp: $ip))?->device_id)->toBe($direct->device_id);
    expect($finder->findDevice(new Neighbor('lldp', null, chassisMac: str_replace(':', '', (string) $stale->ifPhysAddress))))->toBeNull();

    $direct->delete();
    expect((new NeighborFinder)->findDevice(new Neighbor('lldp', null, managementIp: $ip)))->toBeNull();
})->with('addresses');

test('all candidates are considered and global matches are excluded', function (string $ip) {
    Device::factory()->create(['hostname' => $ip, 'ip' => $ip]);
    owner($ip);
    $third = owner($ip);
    $mac = str_replace(':', '', (string) $third->ifPhysAddress);
    $outside = Device::factory()->create(['sysName' => 'outside-serial']);
    Port::factory()->create(['device_id' => $outside->device_id, 'ifPhysAddress' => $mac]);
    $finder = new NeighborFinder;

    // The third IP owner remains eligible even when its MAC is reused elsewhere.
    expect($finder->findDevice(new Neighbor('lldp', null, managementIp: $ip, portMac: $mac))?->device_id)->toBe($third->device_id);
    expect($finder->findDevice(new Neighbor('lldp', null, sysName: 'outside-serial', managementIp: $ip)))->toBeNull();

    $third->device->update(['sysName' => 'candidate-serial']);
    $outside->update(['sysName' => 'candidate-serial']);
    expect($finder->findDevice(new Neighbor('lldp', null, sysName: 'candidate-serial', managementIp: $ip))?->device_id)->toBe($third->device_id);
})->with('addresses');

test('disabled and ignored devices still own their addresses', function (string $ip) {
    foreach (['disabled', 'ignore'] as $flag) {
        $port = owner($ip);
        $port->device->update([$flag => true]);
    }

    expect((new NeighborFinder)->findDevice(new Neighbor('lldp', null, managementIp: $ip)))->toBeNull();
})->with('addresses');

function owner(string $ip, bool $deleted = false, ?Device $device = null): Port
{
    $port = Port::factory()->create(['device_id' => $device ?? Device::factory(), 'deleted' => $deleted]);
    $port->ifPhysAddress = sprintf('feed%08x', $port->port_id);
    $port->save();
    $address = IP::parse($ip);
    if ($address->getFamily() === 'ipv4') {
        Ipv4Address::factory()->create(['port_id' => $port->port_id, 'ipv4_address' => $address->uncompressed()]);
    } else {
        Ipv6Address::factory()->create(['port_id' => $port->port_id, 'ipv6_address' => $address->uncompressed(), 'ipv6_compressed' => $address->compressed()]);
    }

    return $port;
}