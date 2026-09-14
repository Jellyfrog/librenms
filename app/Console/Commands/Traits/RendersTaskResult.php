<?php

/**
 * RendersTaskResult.php
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

namespace App\Console\Commands\Traits;

use App\Maintenance\TaskResult;

/**
 * The console half of a maintenance task: print what it did and turn the
 * outcome into an exit code. The queue half is MaintenanceJob::report().
 */
trait RendersTaskResult
{
    protected function renderTaskResult(TaskResult $result): int
    {
        foreach ($result->messages() as $message) {
            match ($message['level']) {
                TaskResult::ERROR => $this->error($message['text']),
                TaskResult::WARNING => $this->warn($message['text']),
                default => $this->line($message['text']),
            };
        }

        return $result->failed() ? 1 : 0;
    }
}
