<?php

/**
 * SyslogLostConnectionTest.php
 *
 * Tests that syslog processing recovers from a lost database connection
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
 * @copyright  2026 Jacob Wilkins
 * @author     Jacob Wilkins <jacob@9.nz>
 */

uses(\LibreNMS\Tests\DBTestCase::class);
use App\Facades\DeviceCache;
use App\Models\Device;
use App\Models\Syslog;
use Illuminate\Support\Facades\DB;
use LibreNMS\Syslog\Processor;

beforeEach(function (): void {
    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
        $this->markTestSkipped('Killing a connection from a second session requires MySQL/MariaDB.');
    }

    $this->processor = new Processor;
    DeviceCache::flush();
});

afterEach(function (): void {
    DeviceCache::flush();

});

test('device lookup survives lost connection', function (): void {
    /** @var Device $device */
    $device = Device::factory()->create(['os' => 'generic', 'version' => '1.2.3']);

    try {
        $killed = killCurrentConnection();

        $entry = $this->processor->process(message($device->hostname));

        expect($entry->device_id)->toEqual($device->device_id, 'Expected the lookup to survive the lost connection.');
        $this->assertNotSame($killed, connectionId(), 'Expected a new connection, not the killed one.');
    } finally {
        $device->delete();
    }
});

test('message is stored after lost connection', function (): void {
    /** @var Device $device */
    $device = Device::factory()->create(['os' => 'generic']);

    try {
        $killed = killCurrentConnection();

        $this->processor->process(message($device->hostname));

        expect(Syslog::where('device_id', $device->device_id)->count())->toBe(1);
        $this->assertNotSame($killed, connectionId(), 'Expected a new connection, not the killed one.');
    } finally {
        Syslog::where('device_id', $device->device_id)->delete();
        $device->delete();
    }
});

function message(string $host): string
{
    return "$host||local7||info||info||0e||2024-01-01 00:00:00||lost connection test||TEST";
}

/**
 * Kill this connection from a second session, leaving a stale handle behind.
 * Returns the id of the connection that was killed.
 */
function killCurrentConnection(): int
{
    $id = connectionId();

    $killer = app('db.factory')->make(DB::connection()->getConfig(), 'syslog_test_killer');
    $killer->unprepared('KILL ' . $id);
    $killer->disconnect();

    return $id;
}

function connectionId(): int
{
    return (int) DB::connection()->getPdo()->query('SELECT CONNECTION_ID()')->fetchColumn();
}