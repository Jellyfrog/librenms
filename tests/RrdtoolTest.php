<?php

use LibreNMS\Tests\TestCase;

uses(TestCase::class);

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

use App\Facades\LibrenmsConfig;
use LibreNMS\Data\Store\Rrd;

function buildCommandProxy(string $command, string $filename, array $options): array
{
    $mock = test()->mock(Rrd::class)->makePartial(); // avoid constructor
    // @phpstan-ignore method.protected
    $mock->loadConfig(); // load config every time to clear cached settings

    return $mock->buildCommand($command, $filename, $options);
}

test('build command local', function () {
    LibrenmsConfig::set('rrdcached', '');
    LibrenmsConfig::set('rrdtool_version', '1.4');
    LibrenmsConfig::set('rrd_dir', '/opt/librenms/rrd');

    $cmd = buildCommandProxy('create', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['create', '/opt/librenms/rrd/f', 'o'], $cmd);

    $cmd = buildCommandProxy('tune', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['tune', '/opt/librenms/rrd/f', 'o'], $cmd);

    $cmd = buildCommandProxy('update', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['update', '/opt/librenms/rrd/f', 'o'], $cmd);

    LibrenmsConfig::set('rrdtool_version', '1.6');

    $cmd = buildCommandProxy('create', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['create', '/opt/librenms/rrd/f', 'o', '-O'], $cmd);

    $cmd = buildCommandProxy('tune', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['tune', '/opt/librenms/rrd/f', 'o'], $cmd);

    $cmd = buildCommandProxy('update', '/opt/librenms/rrd/f', ['options']);
    $this->assertEquals(['update', '/opt/librenms/rrd/f', 'options'], $cmd);
});

test('build command remote', function () {
    LibrenmsConfig::set('rrdcached', 'server:42217');
    LibrenmsConfig::set('rrdtool_version', '1.4');
    LibrenmsConfig::set('rrd_dir', '/opt/librenms/rrd');

    $cmd = buildCommandProxy('create', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['create', '/opt/librenms/rrd/f', 'o'], $cmd);

    $cmd = buildCommandProxy('tune', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['tune', '/opt/librenms/rrd/f', 'o'], $cmd);

    $cmd = buildCommandProxy('update', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['update', 'f', '--daemon', 'server:42217', 'o'], $cmd);

    LibrenmsConfig::set('rrdtool_version', '1.6');

    $cmd = buildCommandProxy('create', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['create', 'f', '--daemon', 'server:42217', 'o', '-O'], $cmd);

    $cmd = buildCommandProxy('tune', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['tune', 'f', '--daemon', 'server:42217', 'o'], $cmd);

    $cmd = buildCommandProxy('update', '/opt/librenms/rrd/f', ['o']);
    $this->assertEquals(['update', 'f', '--daemon', 'server:42217', 'o'], $cmd);
});

test('build command exception', function () {
    LibrenmsConfig::set('rrdcached', '');
    LibrenmsConfig::set('rrdtool_version', '1.4');

    // use this file, since it is guaranteed to exist
    buildCommandProxy('create', __FILE__, ['o']);
})->throws(\LibreNMS\Exceptions\FileExistsException::class);
