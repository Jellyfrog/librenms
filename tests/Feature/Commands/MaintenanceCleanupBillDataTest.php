<?php

/*
 * MaintenanceCleanupBillDataTest.php
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
use App\Models\Bill;
use App\Models\BillData;
use LibreNMS\Tests\InMemoryDbTestCase;

final class MaintenanceCleanupBillDataTest extends InMemoryDbTestCase
{
    public function testAnniversaryAlreadyPassedThisMonth(): void
    {
        // billing day 10 has already passed on the 15th, so the last anniversary is 2026-08-10
        // minus 3 months of retention leaves a threshold of 2026-05-10 00:00:00
        $this->travelTo('2026-08-15 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 3);

        $bill = $this->bill(10);
        $this->data($bill, '2026-05-09 23:55:00');
        $keep = [
            $this->data($bill, '2026-05-10 00:00:00')->id,
            $this->data($bill, '2026-05-11 00:00:00')->id,
            $this->data($bill, '2026-08-14 00:00:00')->id,
        ];

        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testAnniversaryNotYetThisMonth(): void
    {
        // billing day 20 has not happened yet on the 5th, so the last anniversary is 2026-07-20
        // minus 1 month of retention leaves a threshold of 2026-06-20 00:00:00
        $this->travelTo('2026-08-05 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 1);

        $bill = $this->bill(20);
        $this->data($bill, '2026-06-19 23:55:00');
        $this->data($bill, '2026-05-20 00:00:00');
        $keep = [
            $this->data($bill, '2026-06-20 00:00:00')->id,
            $this->data($bill, '2026-07-20 00:00:00')->id,
        ];

        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testEachBillUsesItsOwnThreshold(): void
    {
        // on the 15th with one month of retention:
        //   bill_day 10 (already passed)  -> 2026-08-10 - 1 month = 2026-07-10
        //   bill_day 20 (not yet)         -> 2026-07-20 - 1 month = 2026-06-20
        $this->travelTo('2026-08-15 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 1);

        $early = $this->bill(10);
        $late = $this->bill(20);

        // deleted by its own threshold, but would survive the other bill's threshold
        $this->data($early, '2026-07-05 00:00:00');
        // deleted by its own threshold, but would survive the other bill's threshold
        $this->data($late, '2026-06-15 00:00:00');

        $keep = [
            // survives its own threshold, but would be deleted by the other bill's threshold
            $this->data($late, '2026-06-25 00:00:00')->id,
            $this->data($early, '2026-07-15 00:00:00')->id,
        ];
        sort($keep);

        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testBillingDayIsClampedToShortMonths(): void
    {
        // billing day 31 on 2026-04-15: the anniversary has not happened yet this month, so the
        // last one was 2026-03-31, and one month of retention clamps that to 2026-02-28.
        // (the legacy SQL rolled 2026-04-01 + 30 days into 2026-05-01 and ended up at 2026-03-01)
        $this->travelTo('2026-04-15 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 1);

        $bill = $this->bill(31);
        $this->data($bill, '2026-02-27 12:00:00');
        $keep = [
            $this->data($bill, '2026-02-28 00:00:00')->id,
            $this->data($bill, '2026-02-28 12:00:00')->id,
            $this->data($bill, '2026-03-15 00:00:00')->id,
        ];

        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testBillingDayIsRestoredInLongMonths(): void
    {
        // billing day 31 on 2026-05-15: last anniversary 2026-04-30, minus one month is 2026-03-31
        // (clamping must not "stick" - March does have a 31st)
        $this->travelTo('2026-05-15 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 1);

        $bill = $this->bill(31);
        $this->data($bill, '2026-03-30 12:00:00');
        $keep = [
            $this->data($bill, '2026-03-31 00:00:00')->id,
        ];

        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testEvaluatedDuringFebruary(): void
    {
        // billing day 31 on 2026-02-10: last anniversary 2026-01-31, minus one month is 2025-12-31
        $this->travelTo('2026-02-10 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 1);

        $bill = $this->bill(31);
        $this->data($bill, '2025-12-30 12:00:00');
        $keep = [
            $this->data($bill, '2025-12-31 00:00:00')->id,
            $this->data($bill, '2026-01-31 00:00:00')->id,
        ];

        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testDisabledByDefault(): void
    {
        $this->travelTo('2026-08-15 09:00:00');

        $bill = $this->bill(10);
        $keep = [$this->data($bill, '2001-01-01 00:00:00')->id];

        // bill_data_purge has no config definition, so it is unset and the job is a no-op
        $this->assertNull(LibrenmsConfig::get('bill_data_purge'));
        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);
        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());

        LibrenmsConfig::set('bill_data_purge', 0);
        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);
        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());

        LibrenmsConfig::set('bill_data_purge', 'not a number');
        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);
        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testArgumentOverridesConfig(): void
    {
        $this->travelTo('2026-08-15 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 12);

        $bill = $this->bill(10);
        // threshold with the config value (12) is 2025-08-10, with the argument (1) it is 2026-07-10
        $this->data($bill, '2026-07-09 00:00:00');
        $keep = [$this->data($bill, '2026-07-10 00:00:00')->id];

        $this->artisan('maintenance:cleanup-bill-data', ['months' => 1])->assertExitCode(0);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testNonNumericArgument(): void
    {
        $this->travelTo('2026-08-15 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 1);

        $bill = $this->bill(10);
        $keep = [$this->data($bill, '2001-01-01 00:00:00')->id];

        $this->artisan('maintenance:cleanup-bill-data', ['months' => 'tuesday'])->assertExitCode(1);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    public function testInvalidBillingDayIsSkipped(): void
    {
        $this->travelTo('2026-08-15 09:00:00');
        LibrenmsConfig::set('bill_data_purge', 1);

        $bill = $this->bill(0);
        $keep = [$this->data($bill, '2001-01-01 00:00:00')->id];

        $this->artisan('maintenance:cleanup-bill-data')->assertExitCode(0);

        $this->assertSame($keep, BillData::orderBy('id')->pluck('id')->all());
    }

    private function bill(int $bill_day): Bill
    {
        return Bill::factory()->create(['bill_day' => $bill_day]);
    }

    private function data(Bill $bill, string $timestamp): BillData
    {
        return BillData::create([
            'bill_id' => $bill->bill_id,
            'timestamp' => $timestamp,
            'period' => 300,
            'delta' => 0,
            'in_delta' => 0,
            'out_delta' => 0,
        ]);
    }
}
