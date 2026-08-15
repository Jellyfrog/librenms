<?php

/*
 * MaintenanceCleanupAuthlogTest.php
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
 * @package    LibreNMS
 * @link       https://www.librenms.org
 * @copyright  2026 LibreNMS
 * @author     LibreNMS Contributors
 */

namespace LibreNMS\Tests\Feature\Commands;

use App\Facades\LibrenmsConfig;
use App\Models\AuthLog;
use Carbon\Carbon;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupAuthlogTest extends InMemoryDbTestCase
{
    public function testOldEntriesAreDeletedAndRecentKept(): void
    {
        LibrenmsConfig::set('authlog_purge', 30);

        $old = AuthLog::factory()->create(['datetime' => Carbon::now()->subDays(60)]);
        $justOverTheEdge = AuthLog::factory()->create(['datetime' => Carbon::now()->subDays(31)]);
        $recent = AuthLog::factory()->create(['datetime' => Carbon::now()->subDays(2)]);
        $now = AuthLog::factory()->create(['datetime' => Carbon::now()]);

        $this->artisan('maintenance:cleanup-authlog')
            ->expectsOutputToContain('Cleared authlog entries older than 30 days (2 rows)')
            ->assertExitCode(0);

        $this->assertSame(
            [$recent->id, $now->id],
            AuthLog::orderBy('id')->pluck('id')->all()
        );
        $this->assertDatabaseMissing('authlog', ['id' => $old->id]);
        $this->assertDatabaseMissing('authlog', ['id' => $justOverTheEdge->id]);
    }

    public function testZeroPurgeSettingDeletesNothing(): void
    {
        LibrenmsConfig::set('authlog_purge', 0);

        AuthLog::factory()->create(['datetime' => Carbon::now()->subYears(5)]);

        $this->artisan('maintenance:cleanup-authlog')
            ->expectsOutputToContain('Authlog cleanup disabled, days <= 0')
            ->assertExitCode(0);

        $this->assertSame(1, AuthLog::count());
    }

    public function testNonNumericPurgeSettingDeletesNothing(): void
    {
        LibrenmsConfig::set('authlog_purge', 'nonsense');

        AuthLog::factory()->create(['datetime' => Carbon::now()->subYears(5)]);

        $this->artisan('maintenance:cleanup-authlog')
            ->expectsOutputToContain('Authlog cleanup disabled due to invalid authlog_purge setting')
            ->assertExitCode(0);

        $this->assertSame(1, AuthLog::count());
    }

    public function testDaysArgumentOverridesConfig(): void
    {
        LibrenmsConfig::set('authlog_purge', 30);

        $old = AuthLog::factory()->create(['datetime' => Carbon::now()->subDays(10)]);
        $recent = AuthLog::factory()->create(['datetime' => Carbon::now()->subDays(2)]);

        $this->artisan('maintenance:cleanup-authlog', ['days' => 5])
            ->expectsOutputToContain('Cleared authlog entries older than 5 days (1 rows)')
            ->assertExitCode(0);

        $this->assertSame([$recent->id], AuthLog::pluck('id')->all());
        $this->assertDatabaseMissing('authlog', ['id' => $old->id]);
    }

    public function testNonNumericDaysArgumentFails(): void
    {
        LibrenmsConfig::set('authlog_purge', 30);

        AuthLog::factory()->create(['datetime' => Carbon::now()->subYears(5)]);

        $this->artisan('maintenance:cleanup-authlog', ['days' => 'abc'])
            ->expectsOutputToContain('Days must be numeric')
            ->assertExitCode(1);

        $this->assertSame(1, AuthLog::count());
    }
}
