<?php

/**
 * IpmiTest.php
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
use App\Models\Device;
use App\Models\DevicePollingMethod;
use App\Models\Sensor;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Process;
use LibreNMS\Enum\PollingMethodType;
use LibreNMS\Interfaces\Data\DataStorageInterface;
use LibreNMS\Modules\Ipmi;
use LibreNMS\OS;

private const string SENSOR_OUTPUT = <<<'EOT'
FAN1             | 3600.000   | RPM        | ok    | 300.000   | 500.000   | 700.000   | 25300.000 | 25400.000 | 25500.000
CPU Temp         | 45.000     | degrees C  | ok    | na        | na        | na        | 85.000    | 90.000    | 95.000
Chassis Intru    | 0x0        | discrete   | 0x0080| na        | na        | na        | na        | na        | na
12V              | na         | Volts      | na    | na        | na        | na        | na        | na        | na
VCORE            | 1.310      | Volts      | ok    | na        | 0.680     | 0.700     | 1.450     | 1.520     | na
EOT;

private const string SDR_OUTPUT = <<<'EOT'
CPU Temp,47,degrees C,ok,
FAN1,E1h,RPM,ok,
PS Status,0x01,discrete,ok,
EOT;

beforeEach(function () {
    $this->device = Device::factory()->create(['os' => 'linux']);
    $this->ipmiMethod = DevicePollingMethod::factory()->create([
        'device_id' => $this->device->device_id,
        'method_type' => PollingMethodType::Ipmi,
        'affects_availability' => false,
        'last_check_successful' => true,
        'settings' => ['hostname' => 'bmc.example.com', 'type' => 'lanplus'],
    ]);
    DeviceCache::setPrimary($this->device->device_id);

    Process::fake(fn (PendingProcess $process) => in_array('sdr', (array) $process->command)
        ? self::SDR_OUTPUT
        : self::SENSOR_OUTPUT);
});

test('discover and poll', function () {
    $module = new Ipmi;
    $module->discover(os());

    Process::assertRan(fn (PendingProcess $process) => ($process->environment['LC_ALL'] ?? null) === 'C');

    $sensors = $this->device->sensors()->orderBy('sensor_index')->get();
    expect($sensors->pluck('sensor_descr')->all())->toBe(['CPU Temp', 'FAN1', 'VCORE']);
    expect($sensors->pluck('sensor_class')->all())->toBe(['temperature', 'fanspeed', 'voltage']);

    // discrete sensors consume an index so existing sensors keep their index
    expect($sensors->pluck('sensor_index')->all())->toEqual([0, 2, 3]);
    expect($sensors[0]->sensor_current)->toEqual(45);
    expect($sensors[0]->sensor_limit)->toEqual(90);
    expect($sensors[1]->sensor_limit_low)->toEqual(500);
    expect($sensors[2]->sensor_limit)->toEqual(1.52);
    expect($sensors[2]->sensor_limit_warn)->toEqual(1.45);
    expect($sensors[2]->sensor_limit_low)->toEqual(0.68);
    expect($sensors[2]->sensor_limit_low_warn)->toEqual(0.7);
    expect($module->dataExists($this->device))->toBeTrue();

    $datastore = Mockery::mock(DataStorageInterface::class);
    $datastore->shouldReceive('put')->once()->withArgs(fn ($device, $measurement, $tags, $fields) => $measurement == 'ipmi'
        && $tags['rrd_name'] == ['sensor', 'temperature', 'ipmi', 'CPU Temp'] && $fields == ['sensor' => 47]);
    $datastore->shouldReceive('put')->once()->withArgs(fn ($device, $measurement, $tags, $fields) => $measurement == 'ipmi'
        && $tags['rrd_name'] == ['sensor', 'fanspeed', 'ipmi', 'FAN1'] && $fields == ['sensor' => 225]);

    $module->poll(os(), $datastore);

    expect($this->device->sensors()->orderBy('sensor_index')->pluck('sensor_current')->all())->toEqual([47, 225, 1.31]);
});

test('port and timeout are left to ipmitool unless set', function () {
    (new Ipmi)->discover(os());

    Process::assertRan(fn (PendingProcess $process) => ! in_array('-p', (array) $process->command) && ! in_array('-N', (array) $process->command));

    $this->ipmiMethod->update(['settings' => ['hostname' => 'bmc.example.com', 'type' => 'lanplus', 'port' => 6230, 'timeout' => 5]]);
    (new Ipmi)->discover(os());

    Process::assertRan(function (PendingProcess $process): bool {
        $command = implode(' ', (array) $process->command);

        return str_contains($command, '-p 6230') && str_contains($command, '-N 5');
    });
});

test('rediscovery does not update unchanged sensors', function () {
    $module = new Ipmi;
    $module->discover(os());
    $sensor_ids = $this->device->sensors()->orderBy('sensor_index')->pluck('sensor_id')->all();

    $updated = [];
    Event::listen('eloquent.updated: ' . Sensor::class, function (Sensor $sensor) use (&$updated): void {
        $updated[] = $sensor->sensor_descr;
    });

    $module->discover(os());

    expect($updated)->toBe([]);
    expect($this->device->sensors()->orderBy('sensor_index')->pluck('sensor_id')->all())->toBe($sensor_ids);
});

test('discover handles empty and short output', function () {
    Process::fake(fn () => "\nCPU Temp | 45.000 | degrees C | ok\n\n");

    (new Ipmi)->discover(os());

    $sensor = $this->device->sensors()->sole();
    expect($sensor->sensor_descr)->toBe('CPU Temp');
    expect($sensor->sensor_current)->toEqual(45);
    expect($sensor->sensor_limit_warn)->toBeNull();

    Process::fake(fn () => '');
    (new Ipmi)->discover(os());

    expect($this->device->sensors()->count())->toBe(0);
});

test('does not run without ipmi configured', function () {
    $module = new Ipmi;

    expect($module->shouldDiscover(os()))->toBeTrue();
    expect($module->shouldPoll(os()))->toBeFalse();

    // no sensors discovered
    $module->discover(os());
    expect($module->shouldPoll(os()))->toBeTrue();

    // a failure in an earlier poll is checked again
    $this->ipmiMethod->update(['last_check_successful' => false]);
    expect($module->shouldPoll(os()))->toBeTrue();

    // the IPMI check fails
    Process::fake(fn () => Process::result(exitCode: 1));
    expect($module->shouldPoll(os()))->toBeFalse();

    // no IPMI polling method
    $this->ipmiMethod->delete();
    $os = os();

    expect($module->shouldDiscover($os))->toBeFalse();
    expect($module->shouldPoll($os))->toBeFalse();
});

test('poll uses the sdr from the ipmi check', function () {
    $module = new Ipmi;
    $module->discover(os());

    $os = os();
    expect($module->shouldPoll($os))->toBeTrue();
    $module->poll($os, Mockery::mock(DataStorageInterface::class)->shouldIgnoreMissing());

    Process::assertRanTimes(fn (PendingProcess $process) => in_array('sdr', (array) $process->command), 1);
    Process::assertDidntRun(fn (PendingProcess $process) => in_array('power', (array) $process->command));
    expect($this->device->sensors()->where('sensor_descr', 'CPU Temp')->value('sensor_current'))->toEqual(47);
});

function os(): OS
{
    // simulate a fresh discovery/poller run
    $device = DeviceCache::refresh($this->device->device_id)->toArray();

    return OS::make($device);
}