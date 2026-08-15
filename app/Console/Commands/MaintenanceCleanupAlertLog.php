<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Facades\LibrenmsConfig;
use App\Models\AlertLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use LibreNMS\Enum\AlertState;
use Symfony\Component\Console\Input\InputArgument;

class MaintenanceCleanupAlertLog extends LnmsCommand
{
    protected $name = 'maintenance:cleanup-alert-log';

    public function __construct()
    {
        parent::__construct();
        $this->addArgument('days', InputArgument::OPTIONAL);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $alert_log_purge = $this->argument('days');

        if ($alert_log_purge === null) {
            $alert_log_purge = LibrenmsConfig::get('alert_log_purge');

            if (! is_numeric($alert_log_purge)) {
                $this->warn(__('commands.maintenance:cleanup-alert-log.bad_days_setting'));

                return 0;
            }
        } elseif (! is_numeric($alert_log_purge)) {
            $this->error(__('commands.maintenance:cleanup-alert-log.bad_days_input'));

            return 1;
        }

        if ($alert_log_purge <= 0) {
            $this->warn(__('commands.maintenance:cleanup-alert-log.disabled'));

            return 0;
        }

        $cutoff = Carbon::now()->subDays($alert_log_purge);

        $this->line(trans('commands.maintenance:cleanup-alert-log.delete_cleared', [
            'days' => $alert_log_purge,
            'count' => $this->purgeClearedAlerts($cutoff),
        ]));

        $this->line(trans('commands.maintenance:cleanup-alert-log.delete_history', [
            'days' => $alert_log_purge,
            'count' => $this->purgeHistory($cutoff),
        ]));

        return 0;
    }

    /**
     * Remove the complete history of alerts that are not currently firing.
     */
    private function purgeClearedAlerts(Carbon $cutoff): int
    {
        $deleted_total = 0;
        $deleted_rows = 1;
        while ($deleted_rows > 0) {
            $deleted_rows = AlertLog::where('time_logged', '<', $cutoff)
                ->whereHas('alert', fn (Builder $query) => $query->where('state', AlertState::CLEAR))
                ->limit(5000)
                ->delete();
            $deleted_total += $deleted_rows;
        }

        return $deleted_total;
    }

    /**
     * Alerts flap, which fills alert_log with entries. For the remaining alerts, keep
     * only the most recent entry per device and rule so the alert details stay available.
     */
    private function purgeHistory(Carbon $cutoff): int
    {
        $deleted_total = 0;

        // the number of device and rule combinations is bounded, but chunk them to be safe
        // deleting never empties a group, so the paged result set is stable
        AlertLog::where('time_logged', '<', $cutoff)
            ->select(['device_id', 'rule_id'])
            ->distinct()
            ->orderBy('device_id')
            ->orderBy('rule_id')
            ->chunk(1000, function (Collection $groups) use ($cutoff, &$deleted_total) {
                foreach ($groups as $group) {
                    $newest = AlertLog::where('device_id', $group->device_id)
                        ->where('rule_id', $group->rule_id)
                        ->where('time_logged', '<', $cutoff)
                        ->max('time_logged');

                    if ($newest === null) {
                        continue; // deleted by another process
                    }

                    $deleted_rows = 1;
                    while ($deleted_rows > 0) {
                        $deleted_rows = AlertLog::where('device_id', $group->device_id)
                            ->where('rule_id', $group->rule_id)
                            ->where('time_logged', '<', $newest)
                            ->limit(5000)
                            ->delete();
                        $deleted_total += $deleted_rows;
                    }
                }
            });

        return $deleted_total;
    }
}
