<?php

use App\Discovery\Sensor as SensorDiscovery;
use App\Models\Device;
use App\Models\Mempool;
use App\Models\Sensor;
use App\Models\Storage;
use App\Models\WirelessSensor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LibreNMS\DB\SyncsModels;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseTransactions::class);

const SYNCS_DISCOVERED_LIMITS = [
    'sensor_limit' => 70,
    'sensor_limit_warn' => 60,
    'sensor_limit_low_warn' => 3,
    'sensor_limit_low' => 2,
];

/**
 * @return array<string, array{class-string<Mempool|Storage>, string, string, string}>
 */
dataset('warnThresholdModels', [
    'storage' => [Storage::class, 'storage', 'storage_descr', 'storage_perc_warn'],
    'mempool' => [Mempool::class, 'mempools', 'mempool_descr', 'mempool_perc_warn'],
]);

test('sync keeps warn threshold on existing models', function (string $modelClass, string $relationship, string $descrField, string $warnField): void {
    $device = Device::factory()->create();
    $existing = $modelClass::factory()->for($device)->createQuietly([$warnField => null]);

    syncOne($device, $relationship, discovered($existing, [$descrField => 'renamed', $warnField => 80]));

    $existing->refresh();
    expect($existing->$descrField)->toBe('renamed');
    expect($existing->$warnField)->toBeNull();
})->with('warnThresholdModels');

test('sync sets warn threshold on new models', function (string $modelClass, string $relationship, string $descrField, string $warnField): void {
    $device = Device::factory()->create();

    syncOne($device, $relationship, $modelClass::factory()->make([$warnField => 42]));

    expect($device->$relationship()->first()->$warnField)->toBe(42);
})->with('warnThresholdModels');

test('fill new keeps warn threshold', function (string $modelClass, string $relationship, string $descrField, string $warnField): void {
    $device = Device::factory()->create();
    $existing = $modelClass::factory()->for($device)->createQuietly([$warnField => 60]);

    $filled = fillNewOne($existing, discovered($existing, [$descrField => 'renamed', $warnField => 80]));

    expect($filled->$descrField)->toBe('renamed');
    expect($filled->$warnField)->toBe(60);
})->with('warnThresholdModels');

test('sensor discovery keeps custom limits', function (): void {
    $device = Device::factory()->create();
    $existing = existingSensor(Sensor::class, $device, ['sensor_custom' => 'Yes', 'sensor_limit' => 50]);

    discoverSensor($existing, ['sensor_descr' => 'renamed'] + SYNCS_DISCOVERED_LIMITS);

    $existing->refresh();
    expect($existing->sensor_descr)->toBe('renamed');
    expect($existing->sensor_custom)->toBe('Yes');
    expect($existing->sensor_limit)->toEqual(50);
    expect($existing->sensor_limit_warn)->toBeNull();
});

test('sensor discovery updates limits', function (): void {
    $device = Device::factory()->create();
    $existing = existingSensor(Sensor::class, $device, ['sensor_limit' => 50, 'sensor_limit_warn' => 40]);

    discoverSensor($existing, ['sensor_limit' => 70, 'sensor_limit_warn' => null]);

    $existing->refresh();
    expect($existing->sensor_limit)->toEqual(70);
    expect($existing->sensor_limit_warn)->toEqual(40, 'Limits not provided by discovery are kept');
});

test('sensor discovery applies limits after reset', function (): void {
    $device = Device::factory()->create();
    $existing = existingSensor(Sensor::class, $device, ['sensor_custom' => 'Reset', 'sensor_limit' => 50]);

    discoverSensor($existing, SYNCS_DISCOVERED_LIMITS);

    $existing->refresh();
    expect($existing->sensor_custom)->toBe('No');
    expect($existing->sensor_limit)->toEqual(70);
});

test('sync keeps custom wireless sensor limits', function (): void {
    $device = Device::factory()->create();
    $existing = existingSensor(WirelessSensor::class, $device, ['sensor_custom' => 'Yes', 'sensor_limit' => 50]);

    syncOne($device, 'wirelessSensors', discovered($existing, ['sensor_descr' => 'renamed'] + SYNCS_DISCOVERED_LIMITS));

    $existing->refresh();
    expect($existing->sensor_descr)->toBe('renamed');
    expect($existing->sensor_custom)->toBe('Yes');
    expect($existing->sensor_limit)->toEqual(50);
    expect($existing->sensor_limit_warn)->toBeNull();
});

// Removing custom limits clears them, so discovery can set them again
test('sync only fills empty wireless sensor limits', function (): void {
    $device = Device::factory()->create();
    $existing = existingSensor(WirelessSensor::class, $device, ['sensor_limit' => 50]);

    syncOne($device, 'wirelessSensors', discovered($existing, SYNCS_DISCOVERED_LIMITS));

    $existing->refresh();
    expect($existing->sensor_limit)->toEqual(50);
    expect($existing->sensor_limit_warn)->toEqual(60);
    expect($existing->sensor_limit_low_warn)->toEqual(3);
    expect($existing->sensor_limit_low)->toEqual(2);
});

/**
 * @template T of Sensor|WirelessSensor
 *
 * @param  class-string<T>  $modelClass
 * @param  array<string, mixed>  $attributes
 * @return T
 */
function existingSensor(string $modelClass, Device $device, array $attributes): Sensor|WirelessSensor
{
    $sensor = $modelClass::factory()->for($device)->createQuietly($attributes + [
        'sensor_class' => $modelClass === Sensor::class ? 'temperature' : 'clients',
        'sensor_custom' => 'No',
        'sensor_limit' => null,
        'sensor_limit_warn' => null,
        'sensor_limit_low_warn' => null,
        'sensor_limit_low' => null,
    ]);

    // load database defaults that are part of the composite key
    return $sensor->refresh();
}

/**
 * Build the model discovery would return for an existing model
 *
 * @param  array<string, mixed>  $attributes
 */
function discovered(Model $existing, array $attributes): Model
{
    // discovery does not set the custom flag
    return $existing->replicate(['sensor_custom'])->forceFill($attributes);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function discoverSensor(Sensor $existing, array $attributes): void
{
    $discovered = $existing->replicate(['sensor_custom'])->forceFill($attributes);

    new SensorDiscovery($existing->device)
        ->discover($discovered)
        ->sync(sensor_class: $discovered->sensor_class, poller_type: $discovered->poller_type);
}

function syncOne(Device $device, string $relationship, Model $discovered): void
{
    modelSyncer()->syncModels($device, $relationship, new Collection([$discovered]));
}

function fillNewOne(Model $existing, Model $discovered): Model
{
    return modelSyncer()->fillNew(new Collection([$existing]), new Collection([$discovered]))->first();
}

function modelSyncer(): object
{
    return new class
    {
        use SyncsModels {
            syncModels as public;
            fillNew as public;
        }
    };
}
