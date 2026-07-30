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

uses(\LibreNMS\Tests\TestCase::class)->group('datastores');
use App\Facades\LibrenmsConfig;
use PHPUnit\Framework\Attributes\Group;


beforeEach(function () {
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

test('default initialization', function () {
    $ds = $this->app->make('Datastore');
    $stores = $ds->getStores();
    expect($stores)->toHaveCount(1, 'Incorrect number of default stores enabled');

    expect($stores[0]::class)->toEqual(\LibreNMS\Data\Store\Rrd::class, 'The default enabled store should be Rrd');
});

test('initialization', function () {
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
        \LibreNMS\Data\Store\Graphite::class,
        \LibreNMS\Data\Store\InfluxDB::class,
        \LibreNMS\Data\Store\InfluxDBv2::class,
        \LibreNMS\Data\Store\OpenTSDB::class,
        \LibreNMS\Data\Store\Prometheus::class,
    ];

    expect($enabled)->toEqual($expected_enabled, 'Expected all non-default stores to be initialized');
});
