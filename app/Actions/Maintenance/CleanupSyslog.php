<?php

/**
 * CleanupSyslog.php
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

namespace App\Actions\Maintenance;

use App\Facades\LibrenmsConfig;
use App\Maintenance\TaskResult;
use App\Models\Syslog;
use Carbon\Carbon;

class CleanupSyslog
{
    /**
     * @param  int|null  $days  keep this many days; null reads the syslog_purge setting
     */
    public function execute(?int $days = null): TaskResult
    {
        $result = TaskResult::make();

        if ($days === null) {
            $days = LibrenmsConfig::get('syslog_purge');

            if (! is_numeric($days)) {
                return $result->warning(trans('commands.maintenance:cleanup-syslog.bad_days_setting'));
            }
        }

        if ($days <= 0) {
            return $result->warning(trans('commands.maintenance:cleanup-syslog.disabled'));
        }

        $cutoff = Carbon::now()->subDays($days)->toDateTimeString();

        // in chunks, so one enormous delete does not hold locks for its whole run
        $total = 0;
        do {
            $deleted = Syslog::where('timestamp', '<=', $cutoff)->limit(5000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $result->info(trans('commands.maintenance:cleanup-syslog.delete', [
            'days' => $days,
            'count' => $total,
        ]));
    }
}
