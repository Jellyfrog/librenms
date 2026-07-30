<?php

/**
 * RrdDefinitonTest.php
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
 * @copyright  2017 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

use App\Facades\LibrenmsConfig;
use LibreNMS\RRD\RrdDefinition;

uses(\LibreNMS\Tests\TestCase::class);

test('empty', function () {
    expect((string) new RrdDefinition())->toBeEmpty();
});

test('wrong type', function () {
    $this->expectException(\LibreNMS\Exceptions\InvalidRrdTypeException::class);
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $def = new RrdDefinition();
    $def->addDataset('badtype', 'Something unexpected');
});

test('name escaping', function () {
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $expected = 'DS:bad_name-is_too_lon:GAUGE:600:0:100';
    $def = RrdDefinition::make()->addDataset('b a%d$_n:a^me-is_too_lon%g.', 'GAUGE', 0, 100, 600);

    expect((string) $def)->toEqual($expected);
});

test('creation', function () {
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $expected = 'DS:pos:COUNTER:600:0:125000000000 DS:unbound:DERIVE:600:U:U';

    $def = new RrdDefinition();
    $def->addDataset('pos', 'COUNTER', 0, 125000000000);
    $def->addDataset('unbound', 'DERIVE');

    expect((string) $def)->toEqual($expected);
});

test('creation with source', function () {
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $def = new RrdDefinition();
    $def->addDataset('migrated', 'COUNTER', 0, 125000000000, null, 'source_ds');
    $def->addDataset('other', 'DERIVE');
    expect($def->getArguments())->toEqual([
        'DS:migrated=source_ds:COUNTER:600:0:125000000000',
        'DS:other:DERIVE:600:U:U',
    ]);

    // use __FILE__ to satisify the file exists check
    $def->addDataset('fromfile', 'COUNTER', source_ds: 'other', source_file: __FILE__);
    expect($def->getArguments())->toEqual([
        '--source',
        __FILE__,
        'DS:migrated=source_ds:COUNTER:600:0:125000000000',
        'DS:other:DERIVE:600:U:U',
        'DS:fromfile=other[1]:COUNTER:600:U:U',
    ]);
});
