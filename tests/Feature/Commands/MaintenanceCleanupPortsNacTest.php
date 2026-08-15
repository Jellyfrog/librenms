<?php

/*
 * MaintenanceCleanupPortsNacTest.php
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
 * @copyright  2026 LibreNMS
 */

namespace LibreNMS\Tests\Feature\Commands;

use App\Facades\LibrenmsConfig;
use App\Models\PortsNac;
use Carbon\Carbon;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupPortsNacTest extends InMemoryDbTestCase
{
    public function testOldEntriesAreDeletedAndRecentKept(): void
    {
        LibrenmsConfig::set('ports_nac_purge', 10);

        $this->createNacEntry('ancient', Carbon::now()->subDays(60));
        $this->createNacEntry('stale', Carbon::now()->subDays(11));
        $this->createNacEntry('fresh', Carbon::now()->subDays(9));
        $this->createNacEntry('current', Carbon::now());

        $this->artisan('maintenance:cleanup-ports-nac')
            ->expectsOutputToContain('2 rows')
            ->assertExitCode(0);

        $this->assertSame(['fresh', 'current'], $this->remaining());
    }

    public function testZeroDaysSettingDeletesNothing(): void
    {
        LibrenmsConfig::set('ports_nac_purge', 0);

        $this->createNacEntry('ancient', Carbon::now()->subDays(60));
        $this->createNacEntry('current', Carbon::now());

        $this->artisan('maintenance:cleanup-ports-nac')->assertExitCode(0);

        $this->assertSame(['ancient', 'current'], $this->remaining());
    }

    public function testNonNumericSettingDeletesNothing(): void
    {
        LibrenmsConfig::set('ports_nac_purge', 'forever');

        $this->createNacEntry('ancient', Carbon::now()->subDays(60));
        $this->createNacEntry('current', Carbon::now());

        $this->artisan('maintenance:cleanup-ports-nac')->assertExitCode(0);

        $this->assertSame(['ancient', 'current'], $this->remaining());
    }

    public function testDaysArgumentOverridesSetting(): void
    {
        LibrenmsConfig::set('ports_nac_purge', 10);

        $this->createNacEntry('ancient', Carbon::now()->subDays(60));
        $this->createNacEntry('stale', Carbon::now()->subDays(11));
        $this->createNacEntry('current', Carbon::now());

        $this->artisan('maintenance:cleanup-ports-nac', ['days' => 30])->assertExitCode(0);

        $this->assertSame(['stale', 'current'], $this->remaining());
    }

    public function testZeroDaysArgumentDeletesNothing(): void
    {
        LibrenmsConfig::set('ports_nac_purge', 10);

        $this->createNacEntry('ancient', Carbon::now()->subDays(60));

        $this->artisan('maintenance:cleanup-ports-nac', ['days' => 0])->assertExitCode(0);

        $this->assertSame(['ancient'], $this->remaining());
    }

    public function testNonNumericDaysArgumentFails(): void
    {
        LibrenmsConfig::set('ports_nac_purge', 10);

        $this->createNacEntry('ancient', Carbon::now()->subDays(60));

        $this->artisan('maintenance:cleanup-ports-nac', ['days' => 'yesterday'])->assertExitCode(1);

        $this->assertSame(['ancient'], $this->remaining());
    }

    private function createNacEntry(string $username, Carbon $updated_at): PortsNac
    {
        return PortsNac::factory()->create([
            'username' => $username,
            'created_at' => $updated_at,
            'updated_at' => $updated_at,
        ]);
    }

    /** @return string[] */
    private function remaining(): array
    {
        return PortsNac::orderBy('ports_nac_id')->pluck('username')->all();
    }
}
