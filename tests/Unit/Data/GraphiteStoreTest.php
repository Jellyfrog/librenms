<?php

/**
 * GraphiteStoreTest.php
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
use App\Models\Device;
use Carbon\Carbon;
use LibreNMS\Data\Store\Graphite;
use Socket\Raw\Socket;

beforeEach(function () {
    $this->timestamp = 1197464400;

    // fix the date
    Carbon::setTestNow(Carbon::createFromTimestampUTC($this->timestamp));
    LibrenmsConfig::set('graphite.enable', true);
});

afterEach(function () {
    // restore Carbon:now() to normal
    Carbon::setTestNow();
    LibrenmsConfig::set('graphite.enable', false);
});

test('socket connect error', function () {
    $mockFactory = \Mockery::mock(\Socket\Raw\Factory::class);

    $mockFactory->shouldReceive('createClient')
        ->andThrow(\Socket\Raw\Exception::class, 'Failed to handle connect exception')->once();

    new Graphite($mockFactory);
});

test('socket write error', function () {
    $mockSocket = \Mockery::mock(\Socket\Raw\Socket::class);
    $graphite = mockGraphite($mockSocket);

    $mockSocket->shouldReceive('write')
        ->andThrow(\Socket\Raw\Exception::class, 'Did not handle socket exception')->once();

    $graphite->write('fake', ['one' => 1], ['rrd_name' => 'name']);
});

test('simple write', function () {
    $mockSocket = \Mockery::mock(\Socket\Raw\Socket::class);
    $graphite = mockGraphite($mockSocket);

    $measurement = 'testmeasure';
    $tags = ['ifName' => 'testifname', 'type' => 'testtype'];
    $fields = ['ifIn' => 234234, 'ifOut' => 53453];
    $meta = ['device' => new Device(['hostname' => 'testhost']), 'rrd_name' => 'rrd_name'];

    $mockSocket->shouldReceive('write')
        ->with("testhost.testmeasure.rrd_name.ifIn 234234 $this->timestamp\n")->once();
    $mockSocket->shouldReceive('write')
        ->with("testhost.testmeasure.rrd_name.ifOut 53453 $this->timestamp\n")->once();
    $graphite->write($measurement, $fields, $tags, $meta);
});

function mockGraphite(Socket $mockSocket): Graphite
{
    $mockFactory = \Mockery::mock(\Socket\Raw\Factory::class);

    $mockFactory->shouldReceive('createClient')
        ->andReturn($mockSocket);

    return new Graphite($mockFactory);
}
