<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Eventlog;
use LibreNMS\Data\Store\Rrd;
use LibreNMS\Exceptions\RrdCachedConnectionException;
use LibreNMS\Exceptions\RrdNotFoundException;
use LibreNMS\RRD\Backend\RrdBackendInterface;
use LibreNMS\RRD\RrdDefinition;

beforeEach(function () {
    $this->backend = Mockery::mock(RrdBackendInterface::class);
    $this->app->instance(RrdBackendInterface::class, $this->backend);
    $this->device = new Device(['hostname' => 'rrd-store-test']);
});

test('creates missing file', function () {
    LibrenmsConfig::set('rrd.step', 300);
    $definition = RrdDefinition::make()->addDataset('a', 'GAUGE');
    $this->backend->shouldReceive('update')->once()->andThrow(new RrdNotFoundException('No such file'));
    $this->backend->shouldReceive('create')->once()->withArgs(fn ($rrd, $def) => $rrd->relativePath() === 'rrd-store-test/test.rrd' && $def->getStep() === 60);
    $this->backend->shouldReceive('update')->once()->with(Mockery::any(), [5], null);

    (new Rrd)->write('test', ['a' => 5, 'unused' => 1], [], ['device' => $this->device, 'rrd_def' => $definition, 'rrd_step' => 60]);

    expect($definition->getStep())->toBe(300, 'rrd_step must not change the caller\'s definition');
});

test('values follow definition order', function () {
    $definition = RrdDefinition::make()->addDataset('in', 'COUNTER')->addDataset('out', 'COUNTER')->addDataset('errors', 'COUNTER');
    $this->backend->shouldReceive('update')->once()->with(Mockery::any(), [10, 20, null], null);

    (new Rrd)->write('test', ['out' => 20, 'in' => 10], [], ['device' => $this->device, 'rrd_def' => $definition]);
});

test('only consecutive store errors disable', function () {
    $eventlog = Mockery::mock(Eventlog::class);
    $eventlog->shouldReceive('_log')->once();
    $this->app->instance(Eventlog::class, $eventlog);

    $error = new RrdCachedConnectionException('Unable to connect to rrdcached');
    $results = [$error, $error, null, $error, $error, $error];
    // null is a successful update
    $this->backend->shouldReceive('update')->times(6)->andReturnUsing(function () use (&$results): void {
        if ($error = array_shift($results)) {
            throw $error;
        }
    });

    $rrd = new Rrd;
    foreach (range(1, 7) as $ignored) {
        $rrd->write('test', ['a' => 1], [], ['device' => $this->device]); // the 7th write is skipped
    }
});