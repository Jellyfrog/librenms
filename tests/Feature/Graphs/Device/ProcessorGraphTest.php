<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\GraphParameters;
use App\Facades\LibrenmsConfig;
use App\Graphs\Device\ProcessorGraph;
use App\Graphs\GraphAccess;
use App\Models\Device;
use App\Models\Processor;
use App\Models\User;
use LibreNMS\Exceptions\NoGraphData;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    LibrenmsConfig::shouldReceive('get')->byDefault()->andReturnUsing(fn ($key, $default = null) => $default);
    LibrenmsConfig::shouldReceive('get')->with('mono_font')->byDefault()->andReturn('DejaVuSansMono');
    LibrenmsConfig::shouldReceive('get')->with('display_sysname')->byDefault()->andReturn('hostname');
    LibrenmsConfig::shouldReceive('getOsSetting')->byDefault()->andReturn(false);
});

test('it returns correct series', function () {
    $device = Device::factory()->create();
    $processor = Processor::factory()->create([
        'device_id' => $device->device_id,
        'processor_type' => 'hr',
        'processor_index' => 0,
        'processor_descr' => 'CPU 0',
    ]);

    $graph = new ProcessorGraph(new GraphParameters(['type' => 'device_processor', 'device' => $device->device_id]));

    $series = $graph->getSeries();

    expect($series)->toHaveCount(1);
    expect($series)->toHaveKey("proc_{$processor->processor_id}");
    expect($series["proc_{$processor->processor_id}"]->field)->toEqual('usage');
    expect($series["proc_{$processor->processor_id}"]->metric->measurement)->toEqual('processor');
    expect($series["proc_{$processor->processor_id}"]->metric->tags['hostname'])->toEqual($device->hostname);
});

test('it returns a series per processor', function () {
    $device = Device::factory()->create();
    Processor::factory()->count(2)->sequence(['processor_index' => 0], ['processor_index' => 1])->create([
        'device_id' => $device->device_id,
        'processor_type' => 'hr',
    ]);

    $graph = new ProcessorGraph(new GraphParameters(['type' => 'device_processor', 'device' => $device->device_id]));
    $metrics = array_map(fn ($series) => $series->metric, array_values($graph->getSeries()));

    expect($metrics)->toHaveCount(2);
    expect($metrics[0]->tags)->toEqual(['hostname' => $device->hostname, 'type' => 'hr', 'index' => 0]);
    expect($metrics[1]->tags)->toEqual(['hostname' => $device->hostname, 'type' => 'hr', 'index' => 1]);
});

test('it reports missing processors', function () {
    $device = Device::factory()->create();
    $graph = new ProcessorGraph(new GraphParameters(['type' => 'device_processor', 'device' => $device->device_id]));

    $this->expectException(NoGraphData::class);
    $this->expectExceptionMessage('No Data');

    app(GraphAccess::class)->trusted(fn () => $graph->getDebugCommand());
});

test('it authorizes based on device', function () {
    $device = Device::factory()->create();

    $graph = new ProcessorGraph(new GraphParameters(['type' => 'device_processor', 'device' => $device->device_id]));

    $this->actingAs(User::factory()->create());

    \Illuminate\Support\Facades\Gate::shouldReceive('allows')
        ->with('view', Mockery::on(fn ($d) => $d instanceof Device && $d->device_id === $device->device_id))
        ->once()
        ->andReturn(true);

    expect($graph->authorize())->toBeTrue();
});

test('it allows trusted guests', function () {
    $device = Device::factory()->create();

    $graph = new ProcessorGraph(new GraphParameters(['type' => 'device_processor', 'device' => $device->device_id]));

    \Illuminate\Support\Facades\Gate::shouldReceive('allows')->never();

    expect(app(GraphAccess::class)->trusted(fn () => $graph->authorize()))->toBeTrue();
});

test('it refuses guests without anonymous graphs', function () {
    $device = Device::factory()->create();

    $graph = new ProcessorGraph(new GraphParameters(['type' => 'device_processor', 'device' => $device->device_id]));

    \Illuminate\Support\Facades\Gate::shouldReceive('allows')->never();

    expect($graph->authorize())->toBeFalse();
});

test('stacked processors are drawn as their share of the total', function () {
    $device = Device::factory()->create();
    Processor::factory()->count(2)->sequence(['processor_index' => 0], ['processor_index' => 1])->create(['device_id' => $device->device_id, 'processor_type' => 'hr']);
    LibrenmsConfig::shouldReceive('getOsSetting')->andReturn(true);

    $graph = new ProcessorGraph(new GraphParameters(['type' => 'device_processor', 'device' => $device->device_id]));

    foreach ($graph->getSeries() as $series) {
        expect($series->style->stacked)->toBeTrue();
        expect($series->style->stackAverage)->toBeTrue();
        expect($series->scale)->toBe(1.0);
    }
});