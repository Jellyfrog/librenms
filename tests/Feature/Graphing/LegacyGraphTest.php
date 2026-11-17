<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\GraphParameters;
use App\Facades\LibrenmsConfig;
use App\Facades\Rrd;
use App\Graphs\GraphAccess;
use App\Graphs\GraphFactory;
use App\Graphs\Legacy\LegacyGraph;
use App\Models\Device;
use App\Models\Port;
use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use LibreNMS\Exceptions\GraphException;
use LibreNMS\RRD\Backend\RrdBackendInterface;
use LibreNMS\Util\Graph;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    LibrenmsConfig::set('allow_unauth_graphs', true);
});

test('auth file resolves the port and device', function () {
    $port = Port::factory()->for(Device::factory())->create();

    $graph = (new GraphFactory)->graphFor(new GraphParameters(['type' => 'port_bits', 'id' => $port->port_id]));

    expect($graph)->toBeInstanceOf(LegacyGraph::class);
    expect($graph->authorize())->toBeTrue();
    expect($graph->getPageContext()->port?->port_id)->toEqual($port->port_id);
    expect($graph->getPageContext()->device?->device_id)->toEqual($port->device_id);
});

test('guests need anonymous graphs', function () {
    LibrenmsConfig::set('allow_unauth_graphs', false);
    $device = Device::factory()->create();

    expect(deviceGraph($device)->authorize())->toBeFalse();
    expect(app(GraphAccess::class)->trusted(fn () => deviceGraph($device)->authorize()))->toBeTrue();
});

test('users need permission to the device', function () {
    $device = Device::factory()->create();
    $this->actingAs(User::factory()->create(['enabled' => 1]));

    expect(deviceGraph($device)->authorize())->toBeFalse();
});

test('alert images render without a user', function () {
    LibrenmsConfig::set('allow_unauth_graphs', false);
    $device = Device::factory()->create();
    $backend = Mockery::mock(RrdBackendInterface::class);
    $backend->shouldReceive('graph')->once()->andReturn('image-data');
    $this->app->instance(RrdBackendInterface::class, $backend);

    expect(Graph::getImage(['type' => 'device_ib_dns_performance', 'device' => $device->device_id, 'graph_type' => 'png'])->data)->toBe('image-data');
    expect(deviceGraph($device)->authorize())->toBeFalse('trust ends with the alert image');
});

test('graph file sets title and options', function () {
    $device = Device::factory()->create();

    $graph = (new GraphFactory)->graphFor(new GraphParameters(['type' => 'device_ib_dns_performance', 'id' => $device->device_id, 'title' => 'yes']));
    expect($graph)->toBeInstanceOf(LegacyGraph::class);
    $options = $graph->getRrdCommandOptions();

    expect($options)->toContain('--vertical-label');
    expect($options)->toContain('Answer time in milliseconds');
    expect($options)->toContain('-l');
    expect($options)->toContain('--title=' . $device->display);
    expect(preg_grep('/^DEF:PerfAA=/', $options))->not->toBeEmpty();
});

test('user title replaces the graph title', function () {
    $device = Device::factory()->create();

    $graph = (new GraphFactory)->graphFor(new GraphParameters(['type' => 'device_ib_dns_performance', 'id' => $device->device_id, 'graph_title' => 'Custom']));

    expect($graph)->toBeInstanceOf(LegacyGraph::class);
    expect($graph->getRrdCommandOptions())->toContain('--title=Custom');
});

test('unexpected errors are reported without showing the details', function () {
    Exceptions::fake();

    $graph = (new GraphFactory)->graphFor(new GraphParameters(['type' => 'bill_bits']));

    try {
        $graph->authorize();
        $this->fail('Expected a graph error');
    } catch (GraphException $e) {
        expect($e->getMessage())->toBe('Graph Error');
        expect($e->getPrevious())->not->toBeNull();
        Exceptions::assertReported($e->getPrevious()::class);
    }
});

test('title is available before the rrd options are built', function () {
    $device = Device::factory()->create();

    $graph = (new GraphFactory)->graphFor(new GraphParameters(['type' => 'device_ib_dns_performance', 'id' => $device->device_id]));

    expect($graph->getTitle())->toBe($device->display);
});

test('multi bits aggregate requires two series', function () {
    Rrd::partialMock()->shouldReceive('checkRrdExists')->andReturn(true);
    $device = Device::factory()->create();
    $ports = Port::factory()->count(2)->for($device)->create();

    expect(aggregateOptions((string) $ports[0]->port_id))->toBeEmpty();
    expect(aggregateOptions($ports->pluck('port_id')->implode(',')))->not->toBeEmpty();
    expect(aggregateOptions($ports->pluck('port_id')->implode(','), noagg: true))->toBeEmpty();
});

function deviceGraph(Device $device): LegacyGraph
{
    $graph = (new GraphFactory)->graphFor(new GraphParameters(['type' => 'device_ib_dns_performance', 'device' => $device->device_id]));
    expect($graph)->toBeInstanceOf(LegacyGraph::class);

    return $graph;
}

/**
 * @return array<int|string, mixed>
 */
function aggregateOptions(string $ids, bool $noagg = false): array
{
    $graph = (new GraphFactory)->graphFor(new GraphParameters(['type' => 'multiport_bits_separate', 'id' => $ids, 'noagg' => $noagg]));
    expect($graph)->toBeInstanceOf(LegacyGraph::class);

    return preg_grep('/^CDEF:aggr/', array_map(strval(...), $graph->getRrdCommandOptions())) ?: [];
}