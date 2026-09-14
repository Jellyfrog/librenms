<?php

/**
 * CleanupNetworks.php
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
use App\Models\Ipv4Network;
use App\Models\Ipv6Network;

class CleanupNetworks
{
    /**
     * @param  bool  $force  ignore the networks_purge setting
     */
    public function execute(bool $force = false): TaskResult
    {
        $result = TaskResult::make();

        // The gate lives here rather than in the command so that a scheduled
        // run, a queued run and a manual run all respect the same setting.
        if (LibrenmsConfig::get('networks_purge') !== true && ! $force) {
            return $result->warning(trans('commands.maintenance:cleanup-networks.disabled'));
        }

        $this->prune($result, Ipv4Network::class, 'ipv4');
        $this->prune($result, Ipv6Network::class, 'ipv6');

        return $result;
    }

    /**
     * Delete networks that no longer have any addresses in them.
     *
     * One statement. delete() returns the row count the message needs, so
     * there is no id list to fetch first, hold in memory, or expand into a
     * WHERE ... IN with one placeholder per row -- which matters most right
     * after a bulk device removal, exactly when this task has the most to do.
     *
     * @param  class-string<Ipv4Network|Ipv6Network>  $model
     */
    private function prune(TaskResult $result, string $model, string $relation): void
    {
        $deleted = $model::whereDoesntHave($relation)->delete();

        if ($deleted > 0) {
            $result->info(trans('commands.maintenance:cleanup-networks.delete', ['count' => $deleted]));
        }
    }
}
