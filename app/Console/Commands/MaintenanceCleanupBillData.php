<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Facades\LibrenmsConfig;
use App\Models\Bill;
use App\Models\BillData;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;

class MaintenanceCleanupBillData extends LnmsCommand
{
    protected $name = 'maintenance:cleanup-bill-data';

    public function __construct()
    {
        parent::__construct();
        $this->addArgument('months', InputArgument::OPTIONAL);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = $this->argument('months');

        if ($months === null) {
            $months = LibrenmsConfig::get('bill_data_purge');

            if (! is_numeric($months)) {
                $this->warn(__('commands.maintenance:cleanup-bill-data.bad_months_setting'));

                return 0;
            }
        } elseif (! is_numeric($months)) {
            $this->error(__('commands.maintenance:cleanup-bill-data.bad_months_input'));

            return 1;
        }

        $months = (int) $months;

        if ($months <= 0) {
            $this->warn(__('commands.maintenance:cleanup-bill-data.disabled'));

            return 0;
        }

        // Each bill has its own threshold, but bills sharing a billing day share a threshold,
        // so group the bill ids and issue one delete per distinct threshold.
        $thresholds = [];
        foreach (Bill::select(['bill_id', 'bill_day'])->cursor() as $bill) {
            $bill_day = (int) $bill->bill_day;

            // ignore bills with an unusable billing day instead of purging with bogus dates
            if ($bill->bill_day === null || $bill_day < 1 || $bill_day > 31) {
                continue;
            }

            $thresholds[$this->threshold($bill_day, $months)->toDateTimeString()][] = $bill->bill_id;
        }

        $deleted_total = 0;
        foreach ($thresholds as $threshold => $bill_ids) {
            $deleted_rows = 1;
            while ($deleted_rows > 0) {
                $deleted_rows = BillData::whereIn('bill_id', $bill_ids)
                    ->where('timestamp', '<', $threshold)
                    ->limit(5000)
                    ->delete();
                $deleted_total += $deleted_rows;
            }
        }

        $this->line(trans('commands.maintenance:cleanup-bill-data.delete', ['months' => $months, 'count' => $deleted_total]));

        return 0;
    }

    /**
     * Find the billing anniversary of the last completed billing cycle, then step back
     * the configured number of months.  All month arithmetic is done on the first of the
     * month (where it can never overflow) and the billing day is clamped into the
     * resulting month afterwards.  A billing day of 31 therefore lands on the last day of
     * a shorter month (2026-02-28, 2026-04-30) instead of rolling into the next month,
     * which is what the old MySQL ADDDATE(start_of_month, bill_day - 1) did.
     */
    private function threshold(int $bill_day, int $months): Carbon
    {
        $now = Carbon::now();
        $month = $now->copy()->startOfMonth();

        if ($bill_day > $now->day) {
            // this month's anniversary has not happened yet, use the previous one
            $month->subMonthNoOverflow();
        }

        $month->subMonthsNoOverflow($months);

        return $month->addDays(min($bill_day, $month->daysInMonth) - 1);
    }
}
