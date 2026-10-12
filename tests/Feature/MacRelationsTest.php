<?php

namespace LibreNMS\Tests\Feature;

use App\Models\Device;
use App\Models\Ipv4Mac;
use App\Models\Ipv6Nd;
use App\Models\MacAccounting;
use App\Models\Port;
use App\Models\PortsFdb;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

final class MacRelationsTest extends DBTestCase
{
    use DatabaseTransactions;

    private const MAC = '001122334455';

    private Device $switch;
    private Port $switchPort;
    private PortsFdb $fdb;

    // arp/nd is learned on the routers, not on the switch holding the fdb entry
    private Device $router;
    private Port $routerPort;
    private Ipv4Mac $arp;
    private Ipv6Nd $nd;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->switch, $this->switchPort] = $this->createDeviceWithPort();
        $this->fdb = PortsFdb::factory()->create([
            'device_id' => $this->switch->device_id,
            'port_id' => $this->switchPort->port_id,
            'mac_address' => self::MAC,
        ]);

        [$this->router, $this->routerPort] = $this->createDeviceWithPort();
        $this->arp = $this->createArp($this->routerPort, self::MAC, '192.0.2.10');
        $this->nd = $this->createNd($this->routerPort, self::MAC, '2001:db8::10');
    }

    /**
     * @return array{Device, Port}
     */
    private function createDeviceWithPort(): array
    {
        /** @var Device $device */
        $device = Device::factory()->create();
        /** @var Port $port */
        $port = Port::factory()->create(['device_id' => $device->device_id]);

        return [$device, $port];
    }

    private function createArp(Port $port, string $mac, string $ip): Ipv4Mac
    {
        return Ipv4Mac::factory()->create([
            'device_id' => $port->device_id,
            'port_id' => $port->port_id,
            'mac_address' => $mac,
            'ipv4_address' => $ip,
        ]);
    }

    private function createNd(Port $port, string $mac, string $ip): Ipv6Nd
    {
        return Ipv6Nd::factory()->create([
            'device_id' => $port->device_id,
            'port_id' => $port->port_id,
            'mac_address' => $mac,
            'ipv6_address' => $ip,
        ]);
    }

    public function testDeviceMacs(): void
    {
        $this->assertEquals([$this->arp->id], $this->router->macs->modelKeys());
        $this->assertEmpty($this->switch->macs);
    }

    public function testDeviceNd(): void
    {
        $this->assertEquals([$this->nd->id], $this->router->nd->modelKeys());
        $this->assertEmpty($this->switch->nd);
    }

    public function testDevicePortsFdb(): void
    {
        $this->assertEquals([$this->fdb->ports_fdb_id], $this->switch->portsFdb->modelKeys());
        $this->assertEmpty($this->router->portsFdb);
    }

    public function testPortMacs(): void
    {
        $this->assertEquals([$this->arp->id], $this->routerPort->macs->modelKeys());
        $this->assertEmpty($this->switchPort->macs);
    }

    public function testPortNd(): void
    {
        $this->assertEquals([$this->nd->id], $this->routerPort->nd->modelKeys());
        $this->assertEmpty($this->switchPort->nd);
    }

    public function testPortFdbEntries(): void
    {
        $this->assertEquals([$this->fdb->ports_fdb_id], $this->switchPort->fdbEntries->modelKeys());
        $this->assertEmpty($this->routerPort->fdbEntries);
    }

    public function testMacAccounting(): void
    {
        $mac = new MacAccounting(['mac' => self::MAC, 'ifIndex' => 1, 'vlan' => 1, 'bps_in' => 0, 'bps_out' => 0]);
        $mac->port_id = $this->routerPort->port_id;
        $this->router->macAccounting()->save($mac);

        $this->assertEquals([$mac->ma_id], $this->router->macAccounting->modelKeys());
        $this->assertEquals([$mac->ma_id], $this->routerPort->macAccounting->modelKeys());
        $this->assertTrue($this->routerPort->is($mac->port));
        $this->assertEmpty($this->switch->macAccounting);
        $this->assertEmpty($this->switchPort->macAccounting);
    }

    public function testIpv4MacPortAndDevice(): void
    {
        $this->assertTrue($this->routerPort->is($this->arp->port));
        $this->assertTrue($this->router->is($this->arp->device));
    }

    public function testIpv6NdPortAndDevice(): void
    {
        $this->assertTrue($this->routerPort->is($this->nd->port));
        $this->assertTrue($this->router->is($this->nd->device));
    }

    public function testFdbIpv4AddressesFromOtherDevice(): void
    {
        $this->assertEquals(['192.0.2.10'], $this->fdb->ipv4Addresses->pluck('ipv4_address')->all());
    }

    public function testFdbWithoutArpOrNdHasNoAddresses(): void
    {
        /** @var PortsFdb $fdb */
        $fdb = PortsFdb::factory()->create([
            'device_id' => $this->switch->device_id,
            'port_id' => $this->switchPort->port_id,
            'mac_address' => '66778899aabb',
        ]);

        $this->assertEmpty($fdb->ipv4Addresses);
        $this->assertEmpty($fdb->ipv6Addresses);

        $eagerLoaded = PortsFdb::with(['ipv4Addresses', 'ipv6Addresses'])->findOrFail($fdb->ports_fdb_id);
        $this->assertEmpty($eagerLoaded->ipv4Addresses);
        $this->assertEmpty($eagerLoaded->ipv6Addresses);
    }

    public function testFdbIpv4AddressesAreNotDuplicated(): void
    {
        // same mac/ip seen by a second router and on a second port of the first router
        [, $secondRouterPort] = $this->createDeviceWithPort();
        $this->createArp($secondRouterPort, self::MAC, '192.0.2.10');
        $this->createArp(Port::factory()->create(['device_id' => $this->router->device_id]), self::MAC, '192.0.2.10');
        // different ip for the same mac and an unrelated mac
        $this->createArp($secondRouterPort, self::MAC, '192.0.2.20');
        $this->createArp($secondRouterPort, '66778899aabb', '192.0.2.30');

        $expected = ['192.0.2.10', '192.0.2.20'];
        $this->assertEqualsCanonicalizing($expected, $this->fdb->ipv4Addresses->pluck('ipv4_address')->all());

        $eagerLoaded = PortsFdb::with('ipv4Addresses')->findOrFail($this->fdb->ports_fdb_id);
        $this->assertEqualsCanonicalizing($expected, $eagerLoaded->ipv4Addresses->pluck('ipv4_address')->all());
    }

    public function testFdbIpv6AddressesAreNotDuplicated(): void
    {
        [, $secondRouterPort] = $this->createDeviceWithPort();
        $this->createNd($secondRouterPort, self::MAC, '2001:db8::10');
        $this->createNd($secondRouterPort, self::MAC, '2001:db8::20');
        $this->createNd($secondRouterPort, '66778899aabb', '2001:db8::30');

        $expected = ['2001:db8::10', '2001:db8::20'];
        $this->assertEqualsCanonicalizing($expected, $this->fdb->ipv6Addresses->pluck('ipv6_address')->all());

        $eagerLoaded = PortsFdb::with('ipv6Addresses')->findOrFail($this->fdb->ports_fdb_id);
        $this->assertEqualsCanonicalizing($expected, $eagerLoaded->ipv6Addresses->pluck('ipv6_address')->all());
    }
}
