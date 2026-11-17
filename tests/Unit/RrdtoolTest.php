<?php

/**
 * RrdtoolTest.php
 *
 * Tests functionality of our rrdtool wrapper
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
 * @copyright  2016 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

uses(\LibreNMS\Tests\TestCase::class);
use App\Facades\LibrenmsConfig;
use LibreNMS\RRD\Backend\Rrdtool;
use LibreNMS\RRD\RrdDefinition;
use LibreNMS\RRD\RrdPath;
use LibreNMS\RRD\RrdProcess;

beforeEach(function (): void {
    $this->commands = [];

    LibrenmsConfig::set('rrdcached', '');
    LibrenmsConfig::set('rrd_dir', '/opt/librenms/rrd');
    LibrenmsConfig::set('rrd.heartbeat', 600);

    $process = Mockery::mock(RrdProcess::class);
    $process->shouldReceive('run')->andReturnUsing(function (string $command) {
        $this->commands[] = $command;

        return '';
    });
    $process->shouldReceive('stop');
    $this->process = $process;
});

test('create adds no overwrite', function (): void {
    rrdtoolBackend()->create(RrdPath::make('host', 'f.rrd'), rrdtoolDefinition()->setRras(['RRA:AVERAGE:0.5:1:10']));

    expect($this->commands)->toBe(['create host/f.rrd --step 300 DS:a:GAUGE:600:U:U RRA:AVERAGE:0.5:1:10 -O']);
});

test('create without no overwrite support', function (): void {
    $backend = new Rrdtool(null, $this->process, '1.4.2');
    $backend->create(RrdPath::make('host', 'f.rrd'), rrdtoolDefinition());

    expect($this->commands)->toBe(['create host/f.rrd --step 300 DS:a:GAUGE:600:U:U']);
});

test('update and tune', function (): void {
    $rrd = RrdPath::make('host', 'f.rrd');

    rrdtoolBackend()->update($rrd, [1, null, 'x', 2.5]);
    rrdtoolBackend()->update($rrd, [3], 1700000000);
    rrdtoolBackend()->tune($rrd, ['a' => ['max' => 100], 'b' => ['min' => 0, 'max' => null]]);

    expect($this->commands)->toBe([
        'update host/f.rrd N:1:U:U:2.5',
        'update host/f.rrd 1700000000:3',
        'tune host/f.rrd --maximum a:100 --minimum b:0 --maximum b:U',
    ]);
});

function rrdtoolBackend(): Rrdtool
{
    return new Rrdtool(null, test()->process);
}

function rrdtoolDefinition(): RrdDefinition
{
    return RrdDefinition::make()->addDataset('a', 'GAUGE')->setStep(300)->setRras([]);
}