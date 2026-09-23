<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\User;
use LibreNMS\Tests\InMemoryDbTestCase;

class DeviceDependencyMapTest extends InMemoryDbTestCase
{
    public function testShowParentPathIgnoresParentsFilteredFromDeviceList(): void
    {
        $disabledParent = Device::factory()->create(['status' => 1, 'disabled' => 1]);
        $child = Device::factory()->create(['status' => 1, 'disabled' => 0]);
        $child->parents()->attach($disabledParent);

        $devices = $this->getDependencyMapDevices($child);

        $this->assertArrayHasKey($child->device_id, $devices);
        $this->assertHighlighted($devices[$child->device_id]);
        $this->assertArrayNotHasKey($disabledParent->device_id, $devices);
    }

    public function testShowParentPathHighlightsParentsInDeviceList(): void
    {
        $disabledParent = Device::factory()->create(['status' => 1, 'disabled' => 1]);
        $disabledGrandparent = Device::factory()->create(['status' => 1, 'disabled' => 1]);
        $grandparent = Device::factory()->create(['status' => 1, 'disabled' => 0]);
        $parent = Device::factory()->create(['status' => 1, 'disabled' => 0]);
        $child = Device::factory()->create(['status' => 1, 'disabled' => 0]);
        $unrelated = Device::factory()->create(['status' => 1, 'disabled' => 0]);
        $parent->parents()->attach([$grandparent->device_id, $disabledGrandparent->device_id]);
        $child->parents()->attach([$disabledParent->device_id, $parent->device_id]);

        $devices = $this->getDependencyMapDevices($child);

        $this->assertHighlighted($devices[$child->device_id]);
        $this->assertHighlighted($devices[$parent->device_id]);
        $this->assertHighlighted($devices[$grandparent->device_id]);
        $this->assertNotHighlighted($devices[$unrelated->device_id]);
        $this->assertArrayNotHasKey($disabledParent->device_id, $devices);
        $this->assertArrayNotHasKey($disabledGrandparent->device_id, $devices);
    }

    /**
     * Request the devices the same way the device dependency map does with "Show parent device path" enabled
     *
     * @return array<int, array<string, mixed>>
     */
    private function getDependencyMapDevices(Device $highlight): array
    {
        $response = $this->actingAs($this->admin())->postJson(route('maps.getdevices'), [
            'disabled' => 0,
            'disabled_alerts' => null,
            'link_type' => 'depends',
            'group' => null,
            'highlight_node' => $highlight->device_id,
            'showpath' => 1,
            'hide_isolated' => 0,
        ]);

        $response->assertOk();

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function assertHighlighted(array $device): void
    {
        $borderWidth = LibrenmsConfig::get('network_map_legend.highlight.borderWidth');
        $this->assertNotNull($borderWidth);
        $this->assertSame($borderWidth, $device['style']['borderWidth'], "Device {$device['id']} is not highlighted");
        $this->assertSame(LibrenmsConfig::get('network_map_legend.highlight.border'), $device['style']['color']['border']);
    }

    /**
     * @param  array<string, mixed>  $device
     */
    private function assertNotHighlighted(array $device): void
    {
        $this->assertNull($device['style']['borderWidth'], "Device {$device['id']} should not be highlighted");
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['enabled' => 1]);
        $admin->assignRole('admin');

        return $admin;
    }
}
