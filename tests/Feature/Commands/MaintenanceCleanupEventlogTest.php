<?php

/*
 * MaintenanceCleanupEventlogTest.php
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
use App\Models\Eventlog;
use Carbon\Carbon;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupEventlogTest extends InMemoryDbTestCase
{
    private Device $device;

    protected function setUp(): void
    {
        parent::setUp();

        // creating a device logs an eventlog entry, discard it so tests control the table contents
        $this->device = Device::factory()->create(); /** @phpstan-ignore-line */
        Eventlog::query()->delete();
    }

    public function testOldEntriesAreDeletedAndRecentKept(): void
    {
        LibrenmsConfig::set('eventlog_purge', 30);

        $old = $this->makeEventlog(Carbon::now()->subDays(60));
        $recent = $this->makeEventlog(Carbon::now()->subDays(2));

        $this->artisan('maintenance:cleanup-eventlog')->assertExitCode(0);

        $this->assertSame([$recent->event_id], Eventlog::pluck('event_id')->all());
        $this->assertNull(Eventlog::find($old->event_id));
    }

    public function testZeroDaysSettingDeletesNothing(): void
    {
        LibrenmsConfig::set('eventlog_purge', 0);

        $old = $this->makeEventlog(Carbon::now()->subDays(365));

        $this->artisan('maintenance:cleanup-eventlog')->assertExitCode(0);

        $this->assertSame([$old->event_id], Eventlog::pluck('event_id')->all());
    }

    public function testNonNumericSettingDeletesNothing(): void
    {
        LibrenmsConfig::set('eventlog_purge', 'not a number');

        $old = $this->makeEventlog(Carbon::now()->subDays(365));

        $this->artisan('maintenance:cleanup-eventlog')->assertExitCode(0);

        $this->assertSame([$old->event_id], Eventlog::pluck('event_id')->all());
    }

    public function testDaysArgumentOverridesConfig(): void
    {
        LibrenmsConfig::set('eventlog_purge', 365);

        $old = $this->makeEventlog(Carbon::now()->subDays(10));
        $recent = $this->makeEventlog(Carbon::now()->subDays(2));

        $this->artisan('maintenance:cleanup-eventlog', ['days' => 5])->assertExitCode(0);

        $this->assertSame([$recent->event_id], Eventlog::pluck('event_id')->all());
        $this->assertNull(Eventlog::find($old->event_id));
    }

    public function testNonNumericDaysArgumentFails(): void
    {
        LibrenmsConfig::set('eventlog_purge', 30);

        $old = $this->makeEventlog(Carbon::now()->subDays(365));

        $this->artisan('maintenance:cleanup-eventlog', ['days' => 'abc'])->assertExitCode(1);

        $this->assertSame([$old->event_id], Eventlog::pluck('event_id')->all());
    }

    private function makeEventlog(Carbon $datetime): Eventlog
    {
        return Eventlog::factory()->create([/** @phpstan-ignore-line */
            'datetime' => $datetime,
            'device_id' => $this->device->device_id,
        ]);
    }
}
