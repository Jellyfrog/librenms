<?php

use App\Data\TimeSeries\LegacyMetric;
use App\Data\TimeSeries\MetricIdentity;
use App\Facades\Rrd;
use App\Graphs\Rrd\RrdFileResolver;
use LibreNMS\Data\Store\MetricNames;
use LibreNMS\RRD\RrdPath;

uses(LibreNMS\Tests\TestCase::class);

beforeEach(function (): void {
    $this->rrdFiles = new RrdFileResolver;
    Rrd::partialMock();
});

function pathMatcher(string $relative): Mockery\Matcher\Closure
{
    return Mockery::on(fn ($path) => $path instanceof RrdPath && $path->relativePath() === $relative);
}

function renamed(): MetricIdentity
{
    return new MetricIdentity('thing', ['hostname' => 'localhost', 'index' => 1]);
}

function resolver(bool $canonical): RrdFileResolver
{
    $names = test()->mock(MetricNames::class);
    $names->shouldReceive('isCanonical')->andReturn($canonical);
    $names->shouldReceive('legacyOf')->andReturn(new LegacyMetric('things', ['index' => 'thing_index'], ['things', 'id{index}']));

    return new RrdFileResolver;
}

test('it names files after the measurement in the hostname directory', function (): void {
    Rrd::shouldReceive('checkRrdExists')->with(pathMatcher('localhost/netstats-ip.rrd'))->once()->andReturn(true);

    expect($this->rrdFiles->pathFor(new MetricIdentity('netstats-ip', ['hostname' => 'localhost'])))->toBe('localhost/netstats-ip.rrd');
});

test('it names files after the measurement and other tag values', function (): void {
    Rrd::shouldReceive('checkRrdExists')->with(pathMatcher('localhost/vm.cpu-qemu-100.rrd'))->once()->andReturn(true);

    $metric = new MetricIdentity('vm.cpu', ['hostname' => 'localhost', 'type' => 'qemu', 'id' => 100]);

    expect($this->rrdFiles->pathFor($metric))->toBe('localhost/vm.cpu-qemu-100.rrd');
});

test('a metric without a hostname is kept in the directory of its first tag', function (): void {
    Rrd::shouldReceive('checkRrdExists')->with(pathMatcher('proxmox-abc/vm.cpu-qemu-100.rrd'))->once()->andReturn(true);

    $metric = new MetricIdentity('vm.cpu', ['cluster' => 'proxmox-abc', 'type' => 'qemu', 'id' => 100]);

    expect($this->rrdFiles->pathFor($metric))->toBe('proxmox-abc/vm.cpu-qemu-100.rrd');
});

test('it reads the legacy file the poller writes to', function (): void {
    Rrd::shouldReceive('checkRrdExists')->with(pathMatcher('localhost/things-id1.rrd'))->once()->andReturn(true);

    expect(resolver(canonical: false)->pathFor(renamed()))->toBe('localhost/things-id1.rrd');
});

test('it reads the canonical file when metrics are canonical', function (): void {
    Rrd::shouldReceive('checkRrdExists')->with(pathMatcher('localhost/thing-1.rrd'))->once()->andReturn(true);

    expect(resolver(canonical: true)->pathFor(renamed()))->toBe('localhost/thing-1.rrd');
});

test('it finds a file that is still stored under its other name', function (): void {
    Rrd::shouldReceive('checkRrdExists')->with(pathMatcher('localhost/thing-1.rrd'))->once()->andReturn(false);
    Rrd::shouldReceive('checkRrdExists')->with(pathMatcher('localhost/things-id1.rrd'))->once()->andReturn(true);

    expect(resolver(canonical: true)->pathFor(renamed()))->toBe('localhost/things-id1.rrd');
});

test('it returns null when neither file exists', function (): void {
    Rrd::shouldReceive('checkRrdExists')->twice()->andReturn(false);

    expect(resolver(canonical: false)->pathFor(renamed()))->toBeNull();
});

test('processor files keep their name', function (): void {
    Rrd::shouldReceive('checkRrdExists')->with(pathMatcher('localhost/processor-hr-1.rrd'))->once()->andReturn(true);

    $metric = new MetricIdentity('processor', ['hostname' => 'localhost', 'type' => 'hr', 'index' => 1]);

    expect($this->rrdFiles->pathFor($metric))->toBe('localhost/processor-hr-1.rrd');
});

test('metrics that kept their file name have one path', function (): void {
    $metric = new MetricIdentity('processor', ['hostname' => 'localhost', 'type' => 'hr', 'index' => 1]);

    $paths = array_map(fn (RrdPath $path) => $path->relativePath(), app(\LibreNMS\Data\Store\Rrd::class)->metricNames($metric));

    expect($paths)->toBe(['localhost/processor-hr-1.rrd']);
});

test('it checks each file once', function (): void {
    $metric = new MetricIdentity('netstats-ip', ['hostname' => 'localhost']);
    Rrd::shouldReceive('checkRrdExists')->once()->andReturn(false);

    expect($this->rrdFiles->pathFor($metric))->toBeNull();
    expect($this->rrdFiles->pathFor($metric))->toBeNull();
});

test('it returns null without tags to locate the file', function (): void {
    Rrd::shouldReceive('checkRrdExists')->never();

    expect($this->rrdFiles->pathFor(new MetricIdentity('netstats-ip')))->toBeNull();
    expect($this->rrdFiles->pathFor(new MetricIdentity('netstats-ip', ['hostname' => ''])))->toBeNull();
});