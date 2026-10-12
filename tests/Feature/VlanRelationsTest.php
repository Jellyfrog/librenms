<?php

namespace LibreNMS\Tests\Feature;

use App\Models\Device;
use App\Models\Port;
use App\Models\PortsFdb;
use App\Models\PortVlan;
use App\Models\Vlan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LibreNMS\Tests\DBTestCase;

final class VlanRelationsTest extends DBTestCase
{
    use DatabaseTransactions;

    private Device $device;
    private Port $port;
    private Vlan $vlan;
    private PortVlan $portVlan;
    private PortsFdb $fdb;

    // same vlan number on another device, must never show up in the relations of the first device
    private Device $otherDevice;
    private Port $otherPort;
    private Vlan $otherVlan;
    private PortVlan $otherPortVlan;
    private PortsFdb $otherFdb;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->device, $this->port, $this->vlan, $this->portVlan, $this->fdb] = $this->createDeviceWithVlan(100);
        [$this->otherDevice, $this->otherPort, $this->otherVlan, $this->otherPortVlan, $this->otherFdb] = $this->createDeviceWithVlan(100);
    }

    /**
     * @return array{Device, Port, Vlan, PortVlan, PortsFdb}
     */
    private function createDeviceWithVlan(int $vlanNumber): array
    {
        /** @var Device $device */
        $device = Device::factory()->create();
        /** @var Port $port */
        $port = Port::factory()->create(['device_id' => $device->device_id]);
        /** @var Vlan $vlan */
        $vlan = Vlan::factory()->create(['device_id' => $device->device_id, 'vlan_vlan' => $vlanNumber]);
        /** @var PortVlan $portVlan */
        $portVlan = PortVlan::factory()->create([
            'device_id' => $device->device_id,
            'port_id' => $port->port_id,
            'vlan' => $vlanNumber,
        ]);
        /** @var PortsFdb $fdb */
        $fdb = PortsFdb::factory()->create([
            'device_id' => $device->device_id,
            'port_id' => $port->port_id,
            'vlan_id' => $vlan->vlan_id,
        ]);

        return [$device, $port, $vlan, $portVlan, $fdb];
    }

    public function testDeviceVlans(): void
    {
        $this->assertEquals([$this->vlan->vlan_id], $this->device->vlans->modelKeys());
        $this->assertEquals([$this->otherVlan->vlan_id], $this->otherDevice->vlans->modelKeys());
    }

    public function testDevicePortsVlan(): void
    {
        $this->assertEquals([$this->portVlan->port_vlan_id], $this->device->portsVlan->modelKeys());
    }

    public function testDevicePortsFdb(): void
    {
        $this->assertEquals([$this->fdb->ports_fdb_id], $this->device->portsFdb->modelKeys());
    }

    public function testPortVlans(): void
    {
        $this->assertEquals([$this->portVlan->port_vlan_id], $this->port->vlans->modelKeys());
        $this->assertEquals([$this->otherPortVlan->port_vlan_id], $this->otherPort->vlans->modelKeys());
    }

    public function testPortFdbEntries(): void
    {
        $this->assertEquals([$this->fdb->ports_fdb_id], $this->port->fdbEntries->modelKeys());
    }

    public function testVlanDevice(): void
    {
        $this->assertTrue($this->device->is($this->vlan->device()->first()));
    }

    public function testVlanPorts(): void
    {
        $this->assertEquals([$this->portVlan->port_vlan_id], $this->vlan->ports->modelKeys());
        $this->assertEquals([$this->otherPortVlan->port_vlan_id], $this->otherVlan->ports->modelKeys());
    }

    public function testVlanPortsOnlyMatchesVlanNumber(): void
    {
        PortVlan::factory()->create([
            'device_id' => $this->device->device_id,
            'port_id' => $this->port->port_id,
            'vlan' => 200,
        ]);

        $this->assertEquals([$this->portVlan->port_vlan_id], $this->vlan->ports->modelKeys());
    }

    public function testVlanPortsExistenceQuery(): void
    {
        $this->otherPortVlan->delete();

        $vlanIds = [$this->vlan->vlan_id, $this->otherVlan->vlan_id];

        $this->assertEquals([$this->vlan->vlan_id], Vlan::whereKey($vlanIds)->has('ports')->pluck('vlan_id')->all());
        $this->assertEquals(1, Vlan::withCount('ports')->find($this->vlan->vlan_id)->ports_count);
        $this->assertEquals(0, Vlan::withCount('ports')->find($this->otherVlan->vlan_id)->ports_count);
    }

    public function testVlanPortsExistenceQueryFromLoadedModels(): void
    {
        $this->otherPortVlan->delete();

        // aggregates on loaded models build the relation from the first model, it must not be scoped to its device
        $vlanIds = [$this->vlan->vlan_id, $this->otherVlan->vlan_id];
        $vlans = Vlan::whereKey($vlanIds)->orderBy('vlan_id')->get()->loadCount('ports');

        $this->assertEquals([1, 0], $vlans->pluck('ports_count')->all());
        $this->assertEquals([$this->vlan->vlan_id], $this->otherVlan->newQuery()->whereKey($vlanIds)->has('ports')->pluck('vlan_id')->all());
    }

    public function testVlanPortsWithoutDevice(): void
    {
        $this->assertCount(0, (new Vlan(['vlan_vlan' => 100]))->ports);
    }

    public function testVlanFdbEntries(): void
    {
        $this->assertEquals([$this->fdb->ports_fdb_id], $this->vlan->fdbEntries->modelKeys());
        $this->assertEquals([$this->otherFdb->ports_fdb_id], $this->otherVlan->fdbEntries->modelKeys());
    }

    public function testPortVlanPort(): void
    {
        $this->assertTrue($this->port->is($this->portVlan->port));
    }

    public function testPortVlanDevice(): void
    {
        $this->assertTrue($this->device->is($this->portVlan->device));
    }

    public function testPortVlanDeviceVlan(): void
    {
        $this->assertTrue($this->vlan->is($this->portVlan->deviceVlan));
        $this->assertTrue($this->otherVlan->is($this->otherPortVlan->deviceVlan));
    }

    public function testPortVlanDeviceVlanExistenceQuery(): void
    {
        $this->otherVlan->delete();

        $portVlanIds = [$this->portVlan->port_vlan_id, $this->otherPortVlan->port_vlan_id];

        $this->assertEquals([$this->portVlan->port_vlan_id], PortVlan::whereKey($portVlanIds)->has('deviceVlan')->pluck('port_vlan_id')->all());
    }

    public function testPortVlanDeviceVlanExistenceQueryFromLoadedModels(): void
    {
        $this->otherVlan->delete();

        // aggregates on loaded models build the relation from the first model, it must not be scoped to its device
        $portVlans = PortVlan::whereKey([$this->portVlan->port_vlan_id, $this->otherPortVlan->port_vlan_id])->orderBy('port_vlan_id')->get()->loadCount('deviceVlan');

        $this->assertEquals([1, 0], $portVlans->pluck('device_vlan_count')->all());
    }

    public function testPortsFdbVlan(): void
    {
        $this->assertTrue($this->vlan->is($this->fdb->vlan));
    }

    public function testPortsFdbPort(): void
    {
        $this->assertTrue($this->port->is($this->fdb->port));
    }

    public function testPortsFdbDevice(): void
    {
        $this->assertTrue($this->device->is($this->fdb->device));
    }

    public function testDeletingPortDeletesPortVlans(): void
    {
        $this->port->delete();

        $this->assertModelMissing($this->portVlan);
        $this->assertModelExists($this->otherPortVlan);
    }
}
