<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\GraphParameters;
use App\Facades\LibrenmsConfig;
use App\Graphs\Device\NetstatIpGraph;
use App\Graphs\GraphAccess;
use App\Graphs\Rrd\RrdFileResolver;
use App\Models\Device;
use App\Models\User;
use LibreNMS\RRD\Backend\RrdBackendInterface;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    LibrenmsConfig::shouldReceive('get')->byDefault()->andReturnUsing(fn ($key, $default = null) => $default);
    LibrenmsConfig::shouldReceive('get')->with('mono_font')->byDefault()->andReturn('DejaVuSansMono');
    LibrenmsConfig::shouldReceive('get')->with('display_sysname')->byDefault()->andReturn('hostname');
    LibrenmsConfig::shouldReceive('has')->byDefault()->andReturn(true);
});

test('it returns correct series', function () {
    $device = Device::factory()->create();

    $graph = new NetstatIpGraph(new GraphParameters(['type' => 'device_netstat_ip', 'device' => $device->device_id]));

    $series = $graph->getSeries();

    expect($series)->toHaveCount(7);
    expect($series)->toHaveKey('ipInDelivers');
    expect($series['ipInDelivers']->field)->toEqual('ipInDelivers');
    expect($series['ipInDelivers']->metric->measurement)->toEqual('netstats-ip');
});

test('it inverts out series', function () {
    $device = Device::factory()->create();
    $rrdFiles = Mockery::mock(RrdFileResolver::class);
    $rrdFiles->shouldReceive('pathFor')->andReturn('/rrd/netstats-ip.rrd');
    $this->app->instance(RrdFileResolver::class, $rrdFiles);
    LibrenmsConfig::shouldReceive('get')->with(Mockery::pattern('/^graph_colours\./'))->andReturn('FF0000');

    $graph = new NetstatIpGraph(new GraphParameters(['type' => 'device_netstat_ip', 'device' => $device->device_id]));
    $command = app(GraphAccess::class)->trusted(fn () => $graph->getDebugCommand());

    $this->assertStringContainsString(" 'CDEF:ds3drawn=ds3,-1,*' ", $command);
    // ipOutRequests
    $this->assertStringNotContainsString('CDEF:ds0drawn=', $command);
    // ipForwDatagrams
    $this->assertStringContainsString(" '-l' ", $command);
});

test('it draws with the rrd backend in the session timezone', function () {
    $device = Device::factory()->create();
    $rrdFiles = Mockery::mock(RrdFileResolver::class);
    $rrdFiles->shouldReceive('pathFor')->andReturn('localhost/netstats-ip.rrd');
    $this->app->instance(RrdFileResolver::class, $rrdFiles);
    LibrenmsConfig::shouldReceive('get')->with(Mockery::pattern('/^graph_colours\./'))->andReturn('FF0000');
    session(['preferences.timezone' => 'Pacific/Auckland']);

    $backend = Mockery::mock(RrdBackendInterface::class);
    $backend->shouldReceive('graph')
        ->once()
        ->with(Mockery::on(fn (array $options) => in_array('DEF:ds0=localhost/netstats-ip.rrd:ipForwDatagrams:AVERAGE', $options, true)), 'Pacific/Auckland')
        ->andReturn('image-data');
    $this->app->instance(RrdBackendInterface::class, $backend);

    $graph = new NetstatIpGraph(new GraphParameters(['type' => 'device_netstat_ip', 'device' => $device->device_id]));

    expect(app(GraphAccess::class)->trusted(fn () => $graph->render())->data)->toBe('image-data');
});

test('it authorizes based on device', function () {
    $device = Device::factory()->create();

    $graph = new NetstatIpGraph(new GraphParameters(['type' => 'device_netstat_ip', 'device' => $device->device_id]));

    $this->actingAs(User::factory()->create());

    \Illuminate\Support\Facades\Gate::shouldReceive('allows')
        ->with('view', Mockery::on(fn ($d) => $d instanceof Device && $d->device_id === $device->device_id))
        ->once()
        ->andReturn(true);

    expect($graph->authorize())->toBeTrue();
});

test('it allows trusted guests', function () {
    $device = Device::factory()->create();

    $graph = new NetstatIpGraph(new GraphParameters(['type' => 'device_netstat_ip', 'device' => $device->device_id]));

    \Illuminate\Support\Facades\Gate::shouldReceive('allows')->never();

    expect(app(GraphAccess::class)->trusted(fn () => $graph->authorize()))->toBeTrue();
});