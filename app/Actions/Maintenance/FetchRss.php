<?php

/**
 * FetchRss.php
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

use App\Maintenance\TaskResult;
use Illuminate\Support\Facades\Cache;
use LibreNMS\Util\Notifications;

class FetchRss
{
    public function execute(): TaskResult
    {
        $result = TaskResult::make();

        // A mutex, not a rate limiter: it is released as soon as the post is
        // done, and the long TTL only covers the process dying mid-fetch. So
        // failing to take it means another poller is fetching right now, which
        // is a skip. This used to return exit code 1, which the scheduler then
        // reported as a failure.
        $lock = Cache::lock('notifications', 86000);

        if (! $lock->get()) {
            return $result->warning(trans('commands.maintenance:fetch-rss.already_running'));
        }

        try {
            Notifications::post();
        } finally {
            // released on the way out either way, so a failed fetch does not
            // block the next attempt for the lifetime of the lock
            $lock->release();
        }

        return $result;
    }
}
