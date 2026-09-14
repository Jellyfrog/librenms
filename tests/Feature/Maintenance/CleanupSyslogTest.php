<?php

/*
 * CleanupSyslogTest.php
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
 */

namespace LibreNMS\Tests\Feature\Maintenance;

use App\Actions\Maintenance\CleanupSyslog;
use App\Facades\LibrenmsConfig;
use App\Models\Syslog;
use Carbon\Carbon;
use LibreNMS\Tests\InMemoryDbTestCase;

final class CleanupSyslogTest extends InMemoryDbTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Syslog::query()->insert([
            ['msg' => 'old', 'timestamp' => Carbon::now()->subDays(40)->toDateTimeString()],
            ['msg' => 'recent', 'timestamp' => Carbon::now()->subDay()->toDateTimeString()],
        ]);
    }

    public function testEntriesOlderThanTheSettingAreDeleted(): void
    {
        LibrenmsConfig::set('syslog_purge', 30);

        $result = (new CleanupSyslog)->execute();

        $this->assertFalse($result->failed());
        $this->assertSame(['recent'], Syslog::pluck('msg')->all(), 'only the old entry should have gone');
        $this->assertStringContainsString(
            trans('commands.maintenance:cleanup-syslog.delete', ['days' => 30, 'count' => 1]),
            $result->summary()
        );
    }

    public function testAnExplicitDayCountOverridesTheSetting(): void
    {
        LibrenmsConfig::set('syslog_purge', 30);

        (new CleanupSyslog)->execute(days: 0);

        $this->assertSame(2, Syslog::count(), 'days=0 disables the cleanup regardless of the setting');
    }

    public function testNothingIsDeletedWhenTheSettingIsNotANumber(): void
    {
        LibrenmsConfig::set('syslog_purge', 'soon');

        $result = (new CleanupSyslog)->execute();

        $this->assertFalse($result->failed(), 'a bad setting is not a task failure');
        $this->assertStringContainsString(
            trans('commands.maintenance:cleanup-syslog.bad_days_setting'),
            $result->summary()
        );
        $this->assertSame(2, Syslog::count());
    }

    public function testNothingIsDeletedWhenTheSettingIsZeroOrLess(): void
    {
        LibrenmsConfig::set('syslog_purge', 0);

        $result = (new CleanupSyslog)->execute();

        $this->assertFalse($result->failed());
        $this->assertStringContainsString(
            trans('commands.maintenance:cleanup-syslog.disabled'),
            $result->summary()
        );
        $this->assertSame(2, Syslog::count());
    }

    /**
     * Typed input is a command concern, so it is the one check that stays
     * outside the task.
     */
    public function testTheCommandRejectsANonNumericArgument(): void
    {
        $this->artisan('maintenance:cleanup-syslog', ['days' => 'lots'])
            ->expectsOutputToContain(trans('commands.maintenance:cleanup-syslog.bad_days_input'))
            ->assertExitCode(1);

        $this->assertSame(2, Syslog::count());
    }
}
