<?php

namespace LibreNMS\Tests\Feature\Http;

use App\Models\Device;
use App\Models\Sensor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;
use Spatie\Permission\Models\Role;

class EditHealthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['enabled' => 1]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function testResetValuesResetsAllCustomSensors(): void
    {
        $device = Device::factory()->create();
        $custom1 = Sensor::factory()->for($device)->create(['sensor_custom' => 'Yes']);
        $custom2 = Sensor::factory()->for($device)->create(['sensor_custom' => 'Yes']);
        $notCustom = Sensor::factory()->for($device)->create(['sensor_custom' => 'No']);
        $otherDeviceSensor = Sensor::factory()->for(Device::factory())->create(['sensor_custom' => 'Yes']);

        $this->actingAs($this->admin())
            ->post(route('device.edit.health.sensor.reset', $device), [
                'sensor_id' => [$custom1->sensor_id, $custom2->sensor_id, $notCustom->sensor_id, $otherDeviceSensor->sensor_id],
            ])
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'message' => 'Sensor values reset']);

        $this->assertSame('Reset', $custom1->fresh()->sensor_custom);
        $this->assertSame('Reset', $custom2->fresh()->sensor_custom);
        $this->assertSame('No', $notCustom->fresh()->sensor_custom);
        $this->assertSame('Yes', $otherDeviceSensor->fresh()->sensor_custom);
    }

    public function testResetValuesWithoutCustomSensors(): void
    {
        $device = Device::factory()->create();
        $notCustom = Sensor::factory()->for($device)->create(['sensor_custom' => 'No']);
        $otherDeviceSensor = Sensor::factory()->for(Device::factory())->create(['sensor_custom' => 'Yes']);

        $this->actingAs($this->admin())
            ->post(route('device.edit.health.sensor.reset', $device), [
                'sensor_id' => [$notCustom->sensor_id, $otherDeviceSensor->sensor_id],
            ])
            ->assertOk()
            ->assertExactJson(['status' => 'ok', 'message' => 'No sensors to reset']);

        $this->assertSame('No', $notCustom->fresh()->sensor_custom);
        $this->assertSame('Yes', $otherDeviceSensor->fresh()->sensor_custom);
    }
}
