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

/**
 * The tasks run by maintenance:run, in the order they are listed here.
 *
 * Each task is an ordinary artisan command, so it can still be run by hand.
 * The registry adds no contract beyond "returns an exit code", and tasks must
 * be non-interactive: maintenance:rrd-step is not eligible, it requires an
 * argument and prompts for confirmation.
 *
 * Gating belongs in the task itself, not here. A task that is disabled by
 * config should check that config and return 0, so that running it by hand
 * behaves the same way as running it from the scheduler. That is why this
 * registry has no condition callbacks.
 *
 * Why subprocesses instead of a queue: LibreNMS does not ship a queue runner,
 * so there is nowhere to put queued jobs. Once one exists, each entry here
 * should become a queued job and maintenance:run can be deleted outright --
 * a worker provides sequencing, failure isolation, timeouts and retries
 * natively, and does it better than this does. Keeping the tasks as plain
 * commands with no orchestrator-specific contract is what keeps that
 * migration cheap.
 */
class TaskRegistry
{
    /**
     * Fallback timeout, in seconds, for tasks that do not set their own.
     */
    public const DEFAULT_TIMEOUT = 3600;

    /**
     * Tasks by cadence, in run order, mapped to their timeout in seconds.
     *
     * The timeout is what bounds a run: a task that hangs is killed and the
     * chain continues, which keeps total runtime predictable enough that a
     * run does not collide with the next one.
     *
     * @var array<string, array<string, int>>
     */
    private const TASKS = [
        'hourly' => [],
        'daily' => [],
        'weekly' => [],
    ];

    /** @var array<string, array<string, int>> */
    private array $tasks;

    /**
     * @param  array<string, array<string, int>>|null  $tasks  overrides the registered tasks, for tests
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
     * The tasks for a cadence, in run order, mapped to their timeout in seconds.
     *
     * @return array<string, int>
     */
    public function tasks(string $cadence): array
    {
        return $this->tasks[$cadence] ?? [];
    }

    /**
     * The timeout for a single task, in seconds.
     */
    public function timeout(string $cadence, string $task): int
    {
        return $this->tasks($cadence)[$task] ?? self::DEFAULT_TIMEOUT;
    }
}
