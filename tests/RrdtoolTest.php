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
use LibreNMS\Data\Store\Rrd;


test('build command local', function () {
    LibrenmsConfig::set('rrdcached', '');
    LibrenmsConfig::set('rrdtool_version', '1.4');
    LibrenmsConfig::set('rrd_dir', '/opt/librenms/rrd');

    $cmd = buildCommandProxy('create', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['create', '/opt/librenms/rrd/f', 'o']);

    $cmd = buildCommandProxy('tune', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['tune', '/opt/librenms/rrd/f', 'o']);

    $cmd = buildCommandProxy('update', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['update', '/opt/librenms/rrd/f', 'o']);

    LibrenmsConfig::set('rrdtool_version', '1.6');

    $cmd = buildCommandProxy('create', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['create', '/opt/librenms/rrd/f', 'o', '-O']);

    $cmd = buildCommandProxy('tune', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['tune', '/opt/librenms/rrd/f', 'o']);

    $cmd = buildCommandProxy('update', '/opt/librenms/rrd/f', ['options']);
    expect($cmd)->toEqual(['update', '/opt/librenms/rrd/f', 'options']);
});

test('build command remote', function () {
    LibrenmsConfig::set('rrdcached', 'server:42217');
    LibrenmsConfig::set('rrdtool_version', '1.4');
    LibrenmsConfig::set('rrd_dir', '/opt/librenms/rrd');

    $cmd = buildCommandProxy('create', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['create', '/opt/librenms/rrd/f', 'o']);

    $cmd = buildCommandProxy('tune', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['tune', '/opt/librenms/rrd/f', 'o']);

    $cmd = buildCommandProxy('update', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['update', 'f', '--daemon', 'server:42217', 'o']);

    LibrenmsConfig::set('rrdtool_version', '1.6');

    $cmd = buildCommandProxy('create', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['create', 'f', '--daemon', 'server:42217', 'o', '-O']);

    $cmd = buildCommandProxy('tune', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['tune', 'f', '--daemon', 'server:42217', 'o']);

    $cmd = buildCommandProxy('update', '/opt/librenms/rrd/f', ['o']);
    expect($cmd)->toEqual(['update', 'f', '--daemon', 'server:42217', 'o']);
});

test('build command exception', function () {
    LibrenmsConfig::set('rrdcached', '');
    LibrenmsConfig::set('rrdtool_version', '1.4');

    $this->expectException(\LibreNMS\Exceptions\FileExistsException::class);

    // use this file, since it is guaranteed to exist
    buildCommandProxy('create', __FILE__, ['o']);
});

function buildCommandProxy(string $command, string $filename, array $options): array
{
    $mock = \Mockery::mock(Rrd::class)->makePartial();
    app()->instance(Rrd::class, $mock);

    // avoid constructor
    // @phpstan-ignore method.protected
    $mock->loadConfig();

    // load config every time to clear cached settings
    return $mock->buildCommand($command, $filename, $options);
}
