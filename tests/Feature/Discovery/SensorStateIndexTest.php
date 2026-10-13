<?php

namespace LibreNMS\Tests\Feature\Discovery;

use App\Discovery\Sensor as SensorDiscovery;
use App\Models\Device;
use App\Models\Sensor;
use App\Models\StateIndex;
use App\Models\StateTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LibreNMS\Tests\TestCase;

class SensorStateIndexTest extends TestCase
{
    use RefreshDatabase;

    private const LSI_STATES = [1 => 'invalid', 2 => 'ok', 3 => 'critical'];
    private const IDRAC_STATES = [1 => 'other', 2 => 'unknown', 3 => 'ok'];

    public function test_devices_with_different_os_keep_their_own_translations_for_the_same_state_name(): void
    {
        $linux = Device::factory()->create(['os' => 'linux']);
        $drac = Device::factory()->create(['os' => 'drac']);

        $linuxSensor = $this->discoverStateSensor($linux, 'powerSupplyStatus', self::LSI_STATES);
        $dracSensor = $this->discoverStateSensor($drac, 'powerSupplyStatus', self::IDRAC_STATES);

        $this->assertSame(self::LSI_STATES, $this->translationsOf($linuxSensor));
        $this->assertSame(self::IDRAC_STATES, $this->translationsOf($dracSensor));
    }

    public function test_devices_with_the_same_os_share_one_state_index(): void
    {
        $first = $this->discoverStateSensor(Device::factory()->create(['os' => 'drac']), 'powerSupplyStatus', self::IDRAC_STATES);
        $second = $this->discoverStateSensor(Device::factory()->create(['os' => 'drac']), 'powerSupplyStatus', self::IDRAC_STATES);

        $this->assertSame($first->stateIndex->state_index_id, $second->stateIndex->state_index_id);
        $this->assertSame(1, StateIndex::where('state_name', 'powerSupplyStatus')->count());
    }

    public function test_sensor_moves_to_the_new_os_state_index_when_the_device_os_changes(): void
    {
        $device = Device::factory()->create(['os' => 'linux']);
        $this->discoverStateSensor($device, 'powerSupplyStatus', self::LSI_STATES);

        $device->os = 'drac';
        $device->save();
        $sensor = $this->discoverStateSensor($device, 'powerSupplyStatus', self::IDRAC_STATES);

        $this->assertSame('drac', $sensor->stateIndex->os);
        $this->assertSame(self::IDRAC_STATES, $this->translationsOf($sensor));
    }

    /**
     * @param  array<int, string>  $states
     */
    private function discoverStateSensor(Device $device, string $stateName, array $states): Sensor
    {
        $translations = [];
        foreach ($states as $value => $descr) {
            $translations[] = new StateTranslation(['state_descr' => $descr, 'state_value' => $value, 'state_generic_value' => 0]);
        }

        return (new SensorDiscovery($device))
            ->withStateTranslations($stateName, $translations)
            ->discover(new Sensor([
                'device_id' => $device->device_id,
                'poller_type' => 'snmp',
                'sensor_class' => 'state',
                'sensor_oid' => '.1.3.6.1.4.1.1.1',
                'sensor_index' => "$stateName.1",
                'sensor_type' => $stateName,
                'sensor_descr' => 'Power Supply 1',
                'sensor_current' => 2,
            ]))
            ->sync(sensor_class: 'state', poller_type: 'snmp')
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function translationsOf(Sensor $sensor): array
    {
        return $sensor->translations()->orderBy('state_value')->pluck('state_descr', 'state_value')->all();
    }
}
