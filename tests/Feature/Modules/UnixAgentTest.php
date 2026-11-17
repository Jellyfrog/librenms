<?php

/**
 * UnixAgentTest.php
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

uses(\LibreNMS\Tests\InMemoryDbTestCase::class);
use App\Facades\DeviceCache;
use \LibreNMS\Polling\Method\Methods\PollingMethod;
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\DevicePollingMethod;
use Illuminate\Support\Facades\Log;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Modules\UnixAgent;
use LibreNMS\OS;
use LibreNMS\Polling\Method\Config\PollingMethodConfig;
use LibreNMS\Polling\Method\Config\UnixAgentConfig;
use LibreNMS\Polling\Method\Definitions\UnixAgentDefinition;
use LibreNMS\Polling\Method\Methods\UnixAgentPollingMethod;
use LibreNMS\Polling\Method\ProbeResult;
use LibreNMS\Polling\PerDeviceMethodResults;

test('check uses the polling method port', function () {
    $server = stream_socket_server('tcp://127.0.0.1:0');
    $this->assertNotFalse($server);
    $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);

    LibrenmsConfig::set('unix-agent.port', 1);
    // nothing listens here
    LibrenmsConfig::set('unix-agent.read-timeout', 1);

    $device = new Device(['hostname' => '127.0.0.1']);
    $device->setRelation('pollingMethods', collect([new DevicePollingMethod([
        'method_type' => PollingMethodType::UnixAgent,
        'enabled' => true,
        'settings' => ['port' => $port, 'timeout' => 2],
    ])]));

    $log = Log::spy();

    $result = new PerDeviceMethodResults($device)->result(PollingMethodType::UnixAgent);
    expect($result?->isSuccess())->toBeTrue();
    expect($result->stat('output'))->toBe('');

    // the listener never sends data
    // connected to the polling method port and timed out reading, instead of failing to connect to the global port
    $log->shouldHaveReceived('error')->with("Connection to UNIX agent timed out during fetch on port $port")->once();

    fclose($server);
});

test('parse keys memcached and drbd by instance', function () {
    $data = UnixAgent::parse(implode("\n", [
        '<<<apache>>>',
        'Total Accesses: 10',
        '<<<app-memcached>>>',
        '{"11211":{"uptime":5}}',
        '<<<drbd>>>',
        'drbd0:cs=Connected',
        'version: 8.4',
        '<<<munin-cpu>>>',
        'user 5',
    ]));

    expect($data['app']['apache'])->toBe('Total Accesses: 10');
    expect($data['app']['memcached'] ?? null)->toBe(['11211' => ['uptime' => 5]]);
    expect($data['app']['drbd'])->toBe(['drbd0' => 'cs=Connected']);
    expect($data['munin'])->toBe(['cpu' => 'user 5']);
});

test('poll uses the output from the check', function () {
    $device = Device::factory()->create(['os' => 'linux']);
    DevicePollingMethod::factory()->create([
        'device_id' => $device->device_id,
        'method_type' => PollingMethodType::UnixAgent,
        'affects_availability' => false,
    ]);
    DeviceCache::setPrimary($device->device_id);
    $deviceArray = DeviceCache::refresh($device->device_id)->toArray();
    $os = OS::make($deviceArray);

    $agent = new class extends PollingMethod
    {
        function probe(Device $device, PollingMethodConfig $config): ProbeResult
        {
            $this->probes++;

            return ProbeResult::success(['output' => "<<<uptime>>>\n12345.67 999\n", 'time' => 15]);
        }

        function definition(): UnixAgentDefinition
        {
            return new UnixAgentDefinition;
        }

        function defaultAffectsAvailability(): bool
        {
            return false;
        }

        function defaults(?Device $device = null): array
        {
            return [];
        }

        function config(Device $device, ?DevicePollingMethod $deviceMethod = null): UnixAgentConfig
        {
            return new UnixAgentConfig(port: 6556, timeout: 1);
        }
    };
    $this->app->instance(UnixAgentPollingMethod::class, $agent);

    $datastore = Mockery::mock(DataStorageInterface::class);
    $datastore->shouldReceive('put')->once()->withArgs(fn ($device, $measurement, $tags, $fields) => $measurement == 'agent' && $fields == ['time' => 15]);

    $module = new UnixAgent;
    expect($module->shouldPoll($os))->toBeTrue();
    $module->poll($os, $datastore);

    expect($agent->probes)->toBe(1);
    // the module did not connect again
});
