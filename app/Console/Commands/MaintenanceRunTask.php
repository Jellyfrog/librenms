<?php

/**
 * MaintenanceRunTask.php
 *
 * Runs one maintenance job in this process. Used by maintenance:run.
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

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Jobs\Maintenance\MaintenanceJob;
use Symfony\Component\Console\Input\InputArgument;

/**
 * The exit code is a contract with maintenance:run. 0: the job ran clean.
 * 1: the job failed, and has already recorded that itself in failed(). Anything
 * else: the process never got as far as the job, so nothing has been recorded
 * and the runner should. That is what lets the runner report a task that was
 * killed or crashed without also reporting, a second time, one that simply
 * failed.
 */
class MaintenanceRunTask extends LnmsCommand
{
    protected $name = 'maintenance:run-task';

    protected $hidden = true;

    public function __construct()
    {
        parent::__construct();

        $this->addArgument('job', InputArgument::REQUIRED);
    }

    public function handle(): int
    {
        $job = (string) $this->argument('job');

        // never dispatch an arbitrary class name off the command line
        if (! class_exists($job) || ! is_subclass_of($job, MaintenanceJob::class)) {
            $this->error(trans('commands.maintenance:run-task.not_a_task', ['job' => $job]));

            return 2;
        }

        try {
            $job::dispatchSync();

            return 0;
        } catch (\Throwable $e) {
            // recorded by the job's failed() before it got here
            $this->error($e->getMessage());

            return 1;
        }
    }
}
