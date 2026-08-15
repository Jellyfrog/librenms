<?php

/*
 * MaintenanceCleanupAlertLogTest.php
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
use App\Models\Alert;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Device;
use Carbon\Carbon;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupAlertLogTest extends InMemoryDbTestCase
{
    public function testClearedAlertHistoryIsRemoved(): void
    {
        $device = Device::factory()->create();
        $rule = AlertRule::factory()->create();
        Alert::factory()->clear()->create(['device_id' => $device->device_id, 'rule_id' => $rule->id]);

        $this->createLogs($device, $rule, ['-40 days', '-30 days', '-20 days']);
        $recent = $this->createLogs($device, $rule, ['-5 days', '-1 day']);

        LibrenmsConfig::set('alert_log_purge', 10);

        $this->artisan('maintenance:cleanup-alert-log')->assertExitCode(0);

        // all old entries removed by the cleared pass, recent ones untouched
        $this->assertSame($recent, AlertLog::orderBy('id')->pluck('id')->all());
    }

    public function testActiveAlertKeepsOnlyNewestOldEntry(): void
    {
        $device = Device::factory()->create();
        $rule = AlertRule::factory()->create();
        Alert::factory()->create(['device_id' => $device->device_id, 'rule_id' => $rule->id]);

        $old = $this->createLogs($device, $rule, ['-40 days', '-30 days', '-20 days']);
        $recent = $this->createLogs($device, $rule, ['-5 days', '-1 day']);

        LibrenmsConfig::set('alert_log_purge', 10);

        $this->artisan('maintenance:cleanup-alert-log')->assertExitCode(0);

        // only the newest entry older than the cutoff survives, recent ones untouched
        $this->assertSame([$old[2], ...$recent], AlertLog::orderBy('id')->pluck('id')->all());
    }

    public function testAcknowledgedAlertIsTreatedAsActive(): void
    {
        $device = Device::factory()->create();
        $rule = AlertRule::factory()->create();
        Alert::factory()->acknowledged()->create(['device_id' => $device->device_id, 'rule_id' => $rule->id]);

        $old = $this->createLogs($device, $rule, ['-40 days', '-20 days']);

        LibrenmsConfig::set('alert_log_purge', 10);

        $this->artisan('maintenance:cleanup-alert-log')->assertExitCode(0);

        $this->assertSame([$old[1]], AlertLog::pluck('id')->all());
    }

    public function testOrphanedEntriesAreTrimmedButNotFullyRemoved(): void
    {
        // no alerts row at all: the legacy inner join skipped these in pass 1,
        // but pass 2 still trims them down to the newest entry older than the cutoff
        $device = Device::factory()->create();
        $rule = AlertRule::factory()->create();

        $old = $this->createLogs($device, $rule, ['-40 days', '-30 days', '-20 days']);
        $recent = $this->createLogs($device, $rule, ['-2 days']);

        LibrenmsConfig::set('alert_log_purge', 10);

        $this->artisan('maintenance:cleanup-alert-log')->assertExitCode(0);

        $this->assertSame([$old[2], ...$recent], AlertLog::orderBy('id')->pluck('id')->all());
    }

    public function testEntriesAreGroupedByDeviceAndRule(): void
    {
        $devices = Device::factory()->count(2)->create();
        $rules = AlertRule::factory()->count(2)->create();

        $expected = [];
        foreach ($devices as $device) {
            foreach ($rules as $rule) {
                Alert::factory()->create(['device_id' => $device->device_id, 'rule_id' => $rule->id]);
                $old = $this->createLogs($device, $rule, ['-40 days', '-20 days']);
                $expected[] = $old[1];
            }
        }

        // a cleared alert for one combination should lose everything
        $cleared_device = Device::factory()->create();
        Alert::factory()->clear()->create(['device_id' => $cleared_device->device_id, 'rule_id' => $rules[0]->id]);
        $this->createLogs($cleared_device, $rules[0], ['-40 days', '-20 days']);

        LibrenmsConfig::set('alert_log_purge', 10);

        $this->artisan('maintenance:cleanup-alert-log')->assertExitCode(0);

        sort($expected);
        $this->assertSame($expected, AlertLog::orderBy('id')->pluck('id')->all());
    }

    public function testDaysArgumentOverridesConfig(): void
    {
        $device = Device::factory()->create();
        $rule = AlertRule::factory()->create();
        Alert::factory()->clear()->create(['device_id' => $device->device_id, 'rule_id' => $rule->id]);

        $logs = $this->createLogs($device, $rule, ['-40 days', '-20 days']);

        LibrenmsConfig::set('alert_log_purge', 10);

        // 30 days only covers the oldest entry
        $this->artisan('maintenance:cleanup-alert-log', ['days' => 30])->assertExitCode(0);

        $this->assertSame([$logs[1]], AlertLog::pluck('id')->all());
    }

    public function testDisabledAndInvalidSettingsDeleteNothing(): void
    {
        $device = Device::factory()->create();
        $rule = AlertRule::factory()->create();
        Alert::factory()->clear()->create(['device_id' => $device->device_id, 'rule_id' => $rule->id]);

        $logs = $this->createLogs($device, $rule, ['-40 days', '-20 days']);

        LibrenmsConfig::set('alert_log_purge', 0);
        $this->artisan('maintenance:cleanup-alert-log')->assertExitCode(0);
        $this->assertSame($logs, AlertLog::pluck('id')->all());

        LibrenmsConfig::set('alert_log_purge', 'not a number');
        $this->artisan('maintenance:cleanup-alert-log')->assertExitCode(0);
        $this->assertSame($logs, AlertLog::pluck('id')->all());

        LibrenmsConfig::set('alert_log_purge', 10);
        $this->artisan('maintenance:cleanup-alert-log', ['days' => 'tomorrow'])->assertExitCode(1);
        $this->assertSame($logs, AlertLog::pluck('id')->all());

        $this->artisan('maintenance:cleanup-alert-log', ['days' => 0])->assertExitCode(0);
        $this->assertSame($logs, AlertLog::pluck('id')->all());
    }

    /**
     * Create alert_log entries at the given relative times, returns the created ids in order.
     *
     * @param  string[]  $times
     * @return int[]
     */
    private function createLogs(Device $device, AlertRule $rule, array $times): array
    {
        return array_map(fn ($time) => AlertLog::factory()->create([
            'device_id' => $device->device_id,
            'rule_id' => $rule->id,
            'time_logged' => Carbon::now()->modify($time),
        ])->id, $times);
    }
}
