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
uses(LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use LibreNMS\RRD\RrdDefinition;
use LibreNMS\RRD\RrdPath;

test('empty', function (): void {
    expect((string) new RrdDefinition())->toBeEmpty();
});

test('wrong type', function (): void {
    $this->expectException(LibreNMS\Exceptions\InvalidRrdTypeException::class);
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $def = new RrdDefinition();
    $def->addDataset('badtype', 'Something unexpected');
});

test('name escaping', function (): void {
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $expected = 'DS:bad_name-is_too_lon:GAUGE:600:0:100';
    $def = RrdDefinition::make()->addDataset('b a%d$_n:a^me-is_too_lon%g.', 'GAUGE', 0, 100, 600);

    expect((string) $def)->toEqual($expected);
});

test('creation', function (): void {
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $expected = 'DS:pos:COUNTER:600:0:125000000000 DS:unbound:DERIVE:600:U:U';

    $def = new RrdDefinition();
    $def->addDataset('pos', 'COUNTER', 0, 125000000000);
    $def->addDataset('unbound', 'DERIVE');

    expect((string) $def)->toEqual($expected);
});

test('creation with source', function (): void {
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $def = new RrdDefinition();
    $def->addDataset('migrated', 'COUNTER', 0, 125000000000, null, 'source_ds');
    $def->addDataset('other', 'DERIVE');
    expect($def->getArguments())->toEqual([
        'DS:migrated=source_ds:COUNTER:600:0:125000000000',
        'DS:other:DERIVE:600:U:U',
    ]);

    $def->addDataset('fromfile', 'COUNTER', source_ds: 'other', source_file: RrdPath::make('host', 'old.rrd'));
    expect($def->getArguments())->toEqual([
        '--source',
        'host/old.rrd',
        'DS:migrated=source_ds:COUNTER:600:0:125000000000',
        'DS:other:DERIVE:600:U:U',
        'DS:fromfile=other[1]:COUNTER:600:U:U',
    ]);
});

test('without sources', function (): void {
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $def = new RrdDefinition();
    $def->addDataset('a', 'COUNTER', source_ds: 'x', source_file: RrdPath::make('host', 'old.rrd'));
    $def->addDataset('b', 'COUNTER', source_ds: 'y');

    expect($def->hasSources())->toBeTrue();
    expect($def->getArguments(withSources: false))->toBe([
        'DS:a:COUNTER:600:U:U',
        'DS:b:COUNTER:600:U:U',
    ]);
});

test('multiple sources keep index order', function (): void {
    LibrenmsConfig::set('rrd.heartbeat', 600);
    $def = new RrdDefinition();
    $def->addDataset('a', 'COUNTER', source_ds: 'x', source_file: RrdPath::make('host', 'one.rrd'));
    $def->addDataset('b', 'COUNTER', source_ds: 'y', source_file: RrdPath::make('host', 'two.rrd'));

    expect($def->getArguments())->toBe([
        '--source', 'host/one.rrd',
        '--source', 'host/two.rrd',
        'DS:a=x[1]:COUNTER:600:U:U',
        'DS:b=y[2]:COUNTER:600:U:U',
    ]);
});

test('step and rras', function (): void {
    LibrenmsConfig::set('rrd.step', 300);
    LibrenmsConfig::set('rrd.heartbeat', 600);
    LibrenmsConfig::set('rrd_rra', ' RRA:AVERAGE:0.5:1:2016   RRA:MAX:0.5:6:1440 ');
    $def = RrdDefinition::make()->addDataset('a', 'GAUGE');

    expect($def->getCreateArguments())->toBe(['--step', '300', 'DS:a:GAUGE:600:U:U', 'RRA:AVERAGE:0.5:1:2016', 'RRA:MAX:0.5:6:1440']);

    $def->setStep(60)->setRras(['RRA:LAST:0.5:1:10']);
    expect($def->getCreateArguments())->toBe(['--step', '60', 'DS:a:GAUGE:600:U:U', 'RRA:LAST:0.5:1:10']);
});

test('order values', function (): void {
    $def = RrdDefinition::make()->addDataset('a', 'GAUGE')->addDataset('b', 'GAUGE')->addDataset('c', 'GAUGE');

    expect($def->orderValues(['b' => 2, 'extra' => 9, 'a' => 1]))->toBe([1, 2, null]);
    expect($def->disableNameChecking()->orderValues(['x' => 2, 'y' => 1]))->toBe([2, 1]);
});
