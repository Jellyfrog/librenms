<?php

/*
 * MaintenanceCleanupPortsFdbTest.php
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
use App\Models\PortsFdb;
use Carbon\Carbon;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupPortsFdbTest extends InMemoryDbTestCase
{
    public function testOldEntriesAreDeletedAndRecentKept(): void
    {
        LibrenmsConfig::set('ports_fdb_purge', 10);

        $this->createFdbEntry(1, Carbon::now()->subDays(30));
        $this->createFdbEntry(2, Carbon::now()->subDays(11));
        $this->createFdbEntry(3, Carbon::now()->subDays(9));
        $this->createFdbEntry(4, Carbon::now());

        $this->artisan('maintenance:cleanup-ports-fdb')->assertExitCode(0);

        $this->assertSame([3, 4], $this->remainingIds());
    }

    public function testZeroDaysSettingDeletesNothing(): void
    {
        LibrenmsConfig::set('ports_fdb_purge', 0);

        $this->createFdbEntry(1, Carbon::now()->subYears(2));

        $this->artisan('maintenance:cleanup-ports-fdb')->assertExitCode(0);

        $this->assertSame([1], $this->remainingIds());
    }

    public function testNonNumericSettingDeletesNothing(): void
    {
        LibrenmsConfig::set('ports_fdb_purge', 'never');

        $this->createFdbEntry(1, Carbon::now()->subYears(2));

        $this->artisan('maintenance:cleanup-ports-fdb')->assertExitCode(0);

        $this->assertSame([1], $this->remainingIds());
    }

    public function testDaysArgumentOverridesConfig(): void
    {
        LibrenmsConfig::set('ports_fdb_purge', 100);

        $this->createFdbEntry(1, Carbon::now()->subDays(10));
        $this->createFdbEntry(2, Carbon::now()->subDays(2));

        $this->artisan('maintenance:cleanup-ports-fdb', ['days' => 5])->assertExitCode(0);

        $this->assertSame([2], $this->remainingIds());
    }

    public function testZeroDaysArgumentDeletesNothing(): void
    {
        LibrenmsConfig::set('ports_fdb_purge', 10);

        $this->createFdbEntry(1, Carbon::now()->subYears(2));

        $this->artisan('maintenance:cleanup-ports-fdb', ['days' => 0])->assertExitCode(0);

        $this->assertSame([1], $this->remainingIds());
    }

    public function testNonNumericDaysArgumentFails(): void
    {
        LibrenmsConfig::set('ports_fdb_purge', 10);

        $this->createFdbEntry(1, Carbon::now()->subYears(2));

        $this->artisan('maintenance:cleanup-ports-fdb', ['days' => 'yesterday'])->assertExitCode(1);

        $this->assertSame([1], $this->remainingIds());
    }

    private function createFdbEntry(int $id, Carbon $updatedAt): PortsFdb
    {
        return PortsFdb::factory()->create([
            'ports_fdb_id' => $id,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);
    }

    /** @return list<int> */
    private function remainingIds(): array
    {
        return PortsFdb::orderBy('ports_fdb_id')->pluck('ports_fdb_id')->all();
    }
}
