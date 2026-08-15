<?php

/*
 * MaintenanceCleanupRouteTest.php
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
 */

namespace LibreNMS\Tests\Feature\Commands;

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use App\Models\Port;
use App\Models\Route;
use Carbon\Carbon;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupRouteTest extends InMemoryDbTestCase
{
    public function testOldRoutesDeletedAndRecentKept(): void
    {
        LibrenmsConfig::set('route_purge', 10);

        $old = $this->createRoute(Carbon::now()->subDays(30));
        $recent = $this->createRoute(Carbon::now()->subDays(2));

        $this->artisan('maintenance:cleanup-route')
            ->assertExitCode(0);

        $this->assertSame([$recent->route_id], Route::pluck('route_id')->all());
        $this->assertNull(Route::find($old->route_id));
    }

    public function testDisabledByZeroDaysSetting(): void
    {
        LibrenmsConfig::set('route_purge', 0);

        $route = $this->createRoute(Carbon::now()->subDays(365));

        $this->artisan('maintenance:cleanup-route')
            ->assertExitCode(0);

        $this->assertSame([$route->route_id], Route::pluck('route_id')->all());
    }

    public function testDisabledByNonNumericSetting(): void
    {
        LibrenmsConfig::set('route_purge', 'nonsense');

        $route = $this->createRoute(Carbon::now()->subDays(365));

        $this->artisan('maintenance:cleanup-route')
            ->assertExitCode(0);

        $this->assertSame([$route->route_id], Route::pluck('route_id')->all());
    }

    public function testDaysArgumentOverridesConfig(): void
    {
        LibrenmsConfig::set('route_purge', 100);

        $old = $this->createRoute(Carbon::now()->subDays(10));
        $recent = $this->createRoute(Carbon::now()->subDays(1));

        $this->artisan('maintenance:cleanup-route', ['days' => 5])
            ->assertExitCode(0);

        $this->assertSame([$recent->route_id], Route::pluck('route_id')->all());
        $this->assertNull(Route::find($old->route_id));
    }

    public function testZeroDaysArgumentDeletesNothing(): void
    {
        LibrenmsConfig::set('route_purge', 10);

        $route = $this->createRoute(Carbon::now()->subDays(365));

        $this->artisan('maintenance:cleanup-route', ['days' => 0])
            ->assertExitCode(0);

        $this->assertSame([$route->route_id], Route::pluck('route_id')->all());
    }

    public function testNonNumericDaysArgumentFails(): void
    {
        LibrenmsConfig::set('route_purge', 10);

        $route = $this->createRoute(Carbon::now()->subDays(365));

        $this->artisan('maintenance:cleanup-route', ['days' => 'abc'])
            ->assertExitCode(1);

        $this->assertSame([$route->route_id], Route::pluck('route_id')->all());
    }

    private function createRoute(Carbon $updated_at): Route
    {
        $device = Device::factory()->create();
        /** @var Port $port */
        $port = Port::factory()->create(['device_id' => $device->device_id]);

        /** @var Route $route */
        $route = Route::factory()->create([
            'device_id' => $device->device_id,
            'port_id' => $port->port_id,
        ]);

        // timestamps are managed by Eloquent, force the desired value
        $route->timestamps = false;
        $route->updated_at = $updated_at;
        $route->save();
        $route->timestamps = true;

        return $route;
    }
}
