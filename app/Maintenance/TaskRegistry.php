<?php

/**
 * TaskRegistry.php
 *
 * Ordered registry of maintenance tasks, grouped by how often they run.
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

namespace App\Maintenance;

use App\Jobs\Maintenance\CleanupNetworks;
use App\Jobs\Maintenance\CleanupSyslog;
use App\Jobs\Maintenance\DiscoverSslCertificates;
use App\Jobs\Maintenance\FetchOuis;
use App\Jobs\Maintenance\FetchRss;
use App\Jobs\Maintenance\MaintenanceJob;
use App\Jobs\Maintenance\RefreshSslCertificates;

/**
 * Which maintenance jobs run, and in what order, for each cadence.
 *
 * That is all it says. Everything about a task -- how long it may run, what
 * setting switches it off, how it guards against overlapping -- belongs on
 * the job itself, so the same class can be dispatched to a queue unchanged.
 *
 * Today maintenance:run walks a cadence and runs each job in its own process,
 * standing in for a queue worker. When LibreNMS ships one, that command goes
 * and these lists are dispatched as a batch instead. This class stays as it is.
 */
class TaskRegistry
{
    /**
     * @var array<string, array<int, class-string<MaintenanceJob>>>
     */
    private const TASKS = [
        'hourly' => [
            CleanupSyslog::class,
        ],
        'daily' => [
            FetchRss::class,
            DiscoverSslCertificates::class,
            RefreshSslCertificates::class,
        ],
        'weekly' => [
            FetchOuis::class,
            CleanupNetworks::class,
        ],
    ];

    /** @var array<string, array<int, class-string<MaintenanceJob>>> */
    private array $tasks;

    /**
     * @param  array<string, array<int, class-string<MaintenanceJob>>>|null  $tasks  a different set of tasks, otherwise the built-in one
     */
    public function __construct(?array $tasks = null)
    {
        $this->tasks = $tasks ?? self::TASKS;
    }

    /**
     * The known cadences, which are also the valid arguments to maintenance:run.
     *
     * @return array<int, string>
     */
    public function cadences(): array
    {
        return array_keys($this->tasks);
    }

    public function has(string $cadence): bool
    {
        return array_key_exists($cadence, $this->tasks);
    }

    /**
     * The jobs for a cadence, in run order.
     *
     * @return array<int, class-string<MaintenanceJob>>
     */
    public function tasks(string $cadence): array
    {
        return $this->tasks[$cadence] ?? [];
    }
}
