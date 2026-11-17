<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\Graphing\GraphParameters;
use App\Graphs\Bill\HistoricbitsGraph;
use App\Graphs\Device\NetstatIpGraph;
use App\Graphs\GraphFactory;
use App\Graphs\Legacy\LegacyGraph;
use LibreNMS\Exceptions\InvalidGraph;

test('it resolves graph classes', function () {
    $factory = new GraphFactory;

    expect($factory->graphFor(new GraphParameters(['type' => 'device_netstat_ip'])))->toBeInstanceOf(NetstatIpGraph::class);
    expect($factory->graphFor(new GraphParameters(['type' => 'bill_historicbits'])))->toBeInstanceOf(HistoricbitsGraph::class);
});

test('it falls back to legacy graphs', function () {
    expect((new GraphFactory)->graphFor(new GraphParameters(['type' => 'device_poller_perf'])))->toBeInstanceOf(LegacyGraph::class);
});

test('it rejects unknown graphs', function () {
    $this->expectException(InvalidGraph::class);

    (new GraphFactory)->graphFor(new GraphParameters(['type' => 'device_doesnotexist']));
});

test('it does not resolve abstract graph classes', function () {
    $this->expectException(InvalidGraph::class);

    (new GraphFactory)->graphFor(new GraphParameters(['type' => 'bill_bill']));
});

test('subtypes may contain spaces', function () {
    expect((new GraphFactory)->graphFor(new GraphParameters(['type' => 'customoid_Test OID'])))->toBeInstanceOf(LegacyGraph::class);
});

test('only plain subtypes resolve to classes', function () {
    $factory = new GraphFactory;

    foreach (['device_netstat ip', 'device_Netstat_Ip', 'device_netstat-ip', 'device_netstat.ip'] as $type) {
        try {
            $this->assertNotInstanceOf(NetstatIpGraph::class, $factory->graphFor(new GraphParameters(['type' => $type])), $type);
        } catch (InvalidGraph) {
            $this->addToAssertionCount(1); // not a graph at all is fine too
        }
    }
});

test('auth files and paths are not graphs', function () {
    foreach (['device_auth', 'device_..', 'device_../port/bits', 'customoid_..'] as $type) {
        try {
            (new GraphFactory)->graphFor(new GraphParameters(['type' => $type]));
            $this->fail("$type should not be a graph");
        } catch (InvalidGraph) {
            $this->addToAssertionCount(1);
        }
    }
});

test('it lists subtypes from classes and legacy files', function () {
    $factory = new GraphFactory;

    $device = $factory->subtypes('device');
    expect($device)->toContain('processor');
    expect($device)->toContain('netstat_ip');
    expect($device)->toContain('poller_perf');
    expect($device)->not->toContain('auth');

    $bill = $factory->subtypes('bill');
    expect($bill)->toContain('historicbits');
    expect($bill)->toContain('bits');
    expect($bill)->not->toContain('bill');
});

test('framework classes are not graph types', function () {
    $factory = new GraphFactory;

    foreach (['legacy_legacy', 'Legacy_legacy', 'LEGACY_legacy', 'rrd_graph', 'rrd_renderer'] as $type) {
        try {
            $factory->graphFor(new GraphParameters(['type' => $type]));
            $this->fail("$type should not be a graph");
        } catch (InvalidGraph) {
            $this->addToAssertionCount(1);
        }
    }

    expect($factory->subtypes('legacy'))->toBe([]);
    expect($factory->subtypes('rrd'))->toBe([]);
});
