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

        $this->prune($result, Ipv4Network::class, 'ipv4', 'ipv4_network_id');
        $this->prune($result, Ipv6Network::class, 'ipv6', 'ipv6_network_id');

        return $result;
    }

    /**
     * Delete networks that no longer have any addresses in them.
     *
     * This used to be withCount()->having($count, 0)->pluck($key), but pluck()
     * replaces the select and drops the count subquery, leaving HAVING pointing
     * at a column that is no longer there. whereDoesntHave says the same thing
     * without the aggregate.
     *
     * @param  class-string<Ipv4Network|Ipv6Network>  $model
     */
    private function prune(TaskResult $result, string $model, string $relation, string $key): void
    {
        $unused = $model::whereDoesntHave($relation)->pluck($key);

        if ($unused->isEmpty()) {
            return;
        }

        $result->info(trans('commands.maintenance:cleanup-networks.delete', ['count' => $unused->count()]));

        $model::whereIn($key, $unused)->delete();
    }
}
