<?php

/*
 * Draws images with rrdtool from real rrd files
 */

use App\Data\Graphing\DataSeries;
use App\Data\Graphing\GraphParameters;
use App\Data\Graphing\SeriesStyle;
use App\Facades\LibrenmsConfig;
use App\Facades\Rrd;
use App\Graphs\Device\DeviceGraph;
use App\Graphs\GraphFactory;
use App\Models\Device;
use App\Models\Processor;
use Illuminate\Support\Facades\File;
use LibreNMS\Exceptions\NoGraphData;
use LibreNMS\Interfaces\Data\Graphing\GraphInterface;
use LibreNMS\RRD\Backend\RrdBackendInterface;
use LibreNMS\RRD\RrdDefinition;
use LibreNMS\RRD\RrdPath;

uses(LibreNMS\Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

const RRD_GRAPH_PNG_HEADER = "\x89PNG";

beforeEach(function (): void {
    if (! is_executable((string) LibrenmsConfig::get('rrdtool', '/usr/bin/rrdtool'))) {
        $this->markTestSkipped('rrdtool is not installed');
    }

    $this->rrdDir = sys_get_temp_dir() . '/librenms-graph-' . uniqid();
    File::ensureDirectoryExists($this->rrdDir);
    LibrenmsConfig::set('rrd_dir', $this->rrdDir);
    LibrenmsConfig::set('rrdcached', '');
    LibrenmsConfig::set('rrd.heartbeat', 600);
    LibrenmsConfig::set('rrd_rra', 'RRA:AVERAGE:0.5:1:100 RRA:MIN:0.5:1:100 RRA:MAX:0.5:1:100');
    LibrenmsConfig::set('webui.graph_type', 'png');
    LibrenmsConfig::set('allow_unauth_graphs', true);
    app()->forgetInstance(RrdBackendInterface::class);
});

afterEach(function (): void {
    if (isset($this->rrdDir)) {
        File::deleteDirectory($this->rrdDir);
    }
});

test('it draws a graph class', function (): void {
    $device = Device::factory()->create();
    createRrd($device, 'netstats-ip.rrd', ['ipForwDatagrams', 'ipInDelivers', 'ipInReceives', 'ipOutRequests', 'ipInDiscards', 'ipOutDiscards', 'ipOutNoRoutes']);

    $image = renderGraph('device_netstat_ip', $device)->render();

    expect($image->data)->toStartWith(RRD_GRAPH_PNG_HEADER);
});

test('it draws a legacy graph', function (): void {
    $device = Device::factory()->create();
    createRrd($device, 'ib_dns_performance.rrd', ['PerfAA', 'PerfnonAA']);

    $image = renderGraph('device_ib_dns_performance', $device)->render();

    expect($image->data)->toStartWith(RRD_GRAPH_PNG_HEADER);
});

test('it draws stacked series with the previous period', function (): void {
    $device = Device::factory()->create(['os' => 'linux']);
    LibrenmsConfig::set('os.linux.processor_stacked', true);
    foreach ([0, 1] as $index) {
        Processor::factory()->create(['device_id' => $device->device_id, 'processor_type' => 'hr', 'processor_index' => $index]);
        createRrd($device, "processor-hr-$index.rrd", ['usage']);
    }

    $graph = (new GraphFactory)->graphFor(new GraphParameters(['type' => 'device_processor', 'device' => $device->device_id, 'graph_type' => 'png', 'previous' => 'yes']));

    expect($graph->render()->data)->toStartWith(RRD_GRAPH_PNG_HEADER);
});

test('it draws lines with a scaled series', function (): void {
    $device = Device::factory()->create();
    createRrd($device, 'traffic.rrd', ['bytes']);

    $graph = new class(new GraphParameters(['type' => 'device_traffic', 'device' => $device->device_id, 'graph_type' => 'png'])) extends DeviceGraph
    {
        public function getSeries(): array
        {
            return ['bits' => new DataSeries($this->metric('traffic'), 'bytes', 'Traffic', 'bps', 8, new SeriesStyle(fill: true, invert: true))];
        }
    };

    expect($graph->render()->data)->toStartWith(RRD_GRAPH_PNG_HEADER);
});

test('successful legacy draws do not check for rrd files', function (): void {
    $device = Device::factory()->create();
    createRrd($device, 'ib_dns_performance.rrd', ['PerfAA', 'PerfnonAA']);
    Rrd::partialMock()->shouldReceive('checkRrdExists')->never();

    $image = renderGraph('device_ib_dns_performance', $device)->render();

    expect($image->data)->toStartWith(RRD_GRAPH_PNG_HEADER);
});

test('series graphs without rrd files are reported as no data', function (): void {
    $device = Device::factory()->create();

    $this->expectException(NoGraphData::class);

    renderGraph('device_netstat_ip', $device)->render();
});

test('missing rrd files are reported as no data', function (): void {
    $device = Device::factory()->create();

    $this->expectException(NoGraphData::class);

    renderGraph('device_ib_dns_performance', $device)->render();
});

function renderGraph(string $type, Device $device): GraphInterface
{
    return (new GraphFactory)->graphFor(new GraphParameters(['type' => $type, 'device' => $device->device_id, 'graph_type' => 'png']));
}

/**
 * @param  string[]  $datasets
 */
function createRrd(Device $device, string $filename, array $datasets): void
{
    $definition = RrdDefinition::make();
    foreach ($datasets as $dataset) {
        $definition->addDataset($dataset, 'GAUGE', 0);
    }

    File::ensureDirectoryExists(test()->rrdDir . "/$device->hostname");
    app(RrdBackendInterface::class)->create(RrdPath::make($device->hostname, $filename), $definition);
}
