<?php

/*
 * MaintenanceCleanupPortsTest.php
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2026 LibreNMS
 * @author     LibreNMS Contributors
 */

namespace LibreNMS\Tests\Feature\Commands;

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Port;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupPortsTest extends InMemoryDbTestCase
{
    private string $rrd_dir;

    protected function setUp(): void
    {
        parent::setUp();

        // deleting a port purges its rrd files, keep that inside a temporary directory
        $this->rrd_dir = sys_get_temp_dir() . '/librenms-test-rrd-' . uniqid();
        mkdir($this->rrd_dir);
        LibrenmsConfig::set('rrd_dir', $this->rrd_dir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->rrd_dir)) {
            rmdir($this->rrd_dir);
        }

        parent::tearDown();
    }

    public function testDeletedPortsArePurged(): void
    {
        LibrenmsConfig::set('ports_purge', true);
        $device = Device::factory()->create();
        /** @var \Illuminate\Database\Eloquent\Collection<int, Port> $kept */
        $kept = Port::factory()->count(3)->create(['device_id' => $device->device_id, 'deleted' => 0]);
        Port::factory()->count(2)->create(['device_id' => $device->device_id, 'deleted' => 1]);

        $this->artisan('maintenance:cleanup-ports')
            ->expectsOutputToContain('Purged 2 deleted ports')
            ->assertExitCode(0);

        $this->assertSame($kept->pluck('port_id')->all(), Port::orderBy('port_id')->pluck('port_id')->all());
    }

    public function testPortsPurgedIsEnabledByDefault(): void
    {
        $device = Device::factory()->create();
        Port::factory()->create(['device_id' => $device->device_id, 'deleted' => 1]);

        $this->artisan('maintenance:cleanup-ports')->assertExitCode(0);

        $this->assertSame(0, Port::count());
    }

    public function testAllPortsArePurgedAcrossChunks(): void
    {
        LibrenmsConfig::set('ports_purge', true);
        $device = Device::factory()->create();
        // more than one chunk (chunk size is 100) to catch chunking that skips rows while deleting
        Port::factory()->count(150)->create(['device_id' => $device->device_id, 'deleted' => 1]);
        $kept = Port::factory()->create(['device_id' => $device->device_id, 'deleted' => 0]);

        $this->artisan('maintenance:cleanup-ports')
            ->expectsOutputToContain('Purged 150 deleted ports')
            ->assertExitCode(0);

        $this->assertSame([$kept->port_id], Port::pluck('port_id')->all());
    }

    public function testDisabledPurgesNothing(): void
    {
        LibrenmsConfig::set('ports_purge', false);
        $device = Device::factory()->create();
        /** @var \Illuminate\Database\Eloquent\Collection<int, Port> $ports */
        $ports = Port::factory()->count(2)->create(['device_id' => $device->device_id, 'deleted' => 1]);

        $this->artisan('maintenance:cleanup-ports')->assertExitCode(0);

        $this->assertSame($ports->pluck('port_id')->all(), Port::orderBy('port_id')->pluck('port_id')->all());
    }

    public function testForcePurgesWhenDisabled(): void
    {
        LibrenmsConfig::set('ports_purge', false);
        $device = Device::factory()->create();
        $kept = Port::factory()->create(['device_id' => $device->device_id, 'deleted' => 0]);
        Port::factory()->count(2)->create(['device_id' => $device->device_id, 'deleted' => 1]);

        $this->artisan('maintenance:cleanup-ports', ['--force' => true])
            ->expectsOutputToContain('Purged 2 deleted ports')
            ->assertExitCode(0);

        $this->assertSame([$kept->port_id], Port::pluck('port_id')->all());
    }
}
