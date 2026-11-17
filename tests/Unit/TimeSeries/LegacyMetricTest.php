<?php

uses(\LibreNMS\Tests\TestCase::class);
use App\Data\TimeSeries\LegacyMetric;
use App\Data\TimeSeries\MetricIdentity;

test('only the tags that were stored are kept', function () {
    $legacy = new LegacyMetric('things', ['index' => 'thing_index']);
    $metric = new MetricIdentity('thing', ['hostname' => 'localhost', 'index' => 0, 'type' => 'new']);

    expect($legacy->tagsOf($metric))->toBe(['thing_index' => 0]);
});

test('the rrd file was named after the measurement by default', function () {
    expect(new LegacyMetric('things')->rrdNameOf(new MetricIdentity('thing', ['hostname' => 'localhost'])))->toBeNull();
});

test('tag values fill the rrd name', function () {
    $legacy = new LegacyMetric('things', rrdName: ['thing', 'id{index}']);

    expect($legacy->rrdNameOf(new MetricIdentity('thing', ['hostname' => 'localhost', 'index' => 7])))->toBe(['thing', 'id7']);
});
