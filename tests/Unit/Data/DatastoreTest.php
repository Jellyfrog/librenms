<?php

/**
 * DatastoreTest.php
 *
 * -Description-
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
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */
uses(LibreNMS\Tests\TestCase::class)->group('datastores');
use App\Data\TimeSeries\MetricIdentity;
use App\Facades\LibrenmsConfig;
use App\Providers\DatastoreServiceProvider;
use LibreNMS\Data\Store\Datastore;
use LibreNMS\Interfaces\Data\Datastore as DatastoreContract;

beforeEach(function (): void {
    LibrenmsConfig::forget([
        'graphite',
        'influxdb',
        'influxdbv2',
        'kafka',
        'opentsdb',
        'prometheus',
        'rrd',
    ]);
});

test('default initialization', function (): void {
    $ds = $this->app->make('Datastore');
    $stores = $ds->getStores();
    expect($stores)->toHaveCount(1, 'Incorrect number of default stores enabled');

    expect($stores[0]::class)->toEqual(LibreNMS\Data\Store\Rrd::class, 'The default enabled store should be Rrd');
});

test('initialization', function (): void {
    LibrenmsConfig::set('rrd.enable', false);
    LibrenmsConfig::set('graphite.enable', true);
    LibrenmsConfig::set('influxdb.enable', true);
    LibrenmsConfig::set('influxdbv2.enable', true);
    LibrenmsConfig::set('opentsdb.enable', true);
    LibrenmsConfig::set('prometheus.enable', true);
    LibrenmsConfig::set('kafka.enable', false);

    $ds = $this->app->make('Datastore');
    $stores = $ds->getStores();
    expect($stores)->toHaveCount(5, 'Incorrect number of default stores enabled');

    $enabled = array_map(get_class(...), $stores);

    $expected_enabled = [
        LibreNMS\Data\Store\Graphite::class,
        LibreNMS\Data\Store\InfluxDB::class,
        LibreNMS\Data\Store\InfluxDBv2::class,
        LibreNMS\Data\Store\OpenTSDB::class,
        LibreNMS\Data\Store\Prometheus::class,
    ];

    expect($enabled)->toEqual($expected_enabled, 'Expected all non-default stores to be initialized');
});

test('reset', function (): void {
    $rrd = WeakReference::create($this->app->make('Datastore')->getStores()[0]);
    expect(App\Facades\Rrd::getFacadeRoot())->toBe($rrd->get(), 'Facade should resolve the same Rrd instance');

    DatastoreServiceProvider::reset($this->app);

    expect($rrd->get())->toBeNull('Rrd should be released (and terminated) by reset');

    LibrenmsConfig::set('rrd.enable', false);
    LibrenmsConfig::set('graphite.enable', true);

    $stores = $this->app->make('Datastore')->getStores();
    expect(array_map(get_class(...), $stores))->toEqual([LibreNMS\Data\Store\Graphite::class], 'Datastore should be rebuilt from the current config');
});

test('cleaned up metrics are written under the names they had', function (): void {
    $metric = new MetricIdentity('processor', ['hostname' => 'localhost', 'type' => 'hr', 'index' => 1]);
    $store = Mockery::mock(DatastoreContract::class);
    $store->shouldReceive('write')->once()
        ->with('processors', ['usage' => 5], ['processor_type' => 'hr', 'processor_index' => 1], ['rrd_def' => 'def', 'rrd_name' => ['processor', 'hr', '1']]);

    new Datastore([$store])->write($metric->measurement, ['usage' => 5], $metric->tags, ['metric' => $metric, 'rrd_def' => 'def']);
});

test('canonical metrics are written as identified', function (): void {
    LibrenmsConfig::set('datastore.canonical_metrics', true);
    $metric = new MetricIdentity('processor', ['hostname' => 'localhost', 'type' => 'hr', 'index' => 1]);
    $store = Mockery::mock(DatastoreContract::class);
    $store->shouldReceive('write')->once()->with('processor', ['usage' => 5], $metric->tags, ['metric' => $metric]);

    new Datastore([$store])->write($metric->measurement, ['usage' => 5], $metric->tags, ['metric' => $metric]);
});

test('metrics that were always clean are written as identified', function (): void {
    $metric = new MetricIdentity('vm.cpu', ['cluster' => 'proxmox-abc', 'id' => 100]);
    $store = Mockery::mock(DatastoreContract::class);
    $store->shouldReceive('write')->once()->with('vm.cpu', ['usage' => 5], $metric->tags, ['metric' => $metric]);

    new Datastore([$store])->write($metric->measurement, ['usage' => 5], $metric->tags, ['metric' => $metric]);
});
