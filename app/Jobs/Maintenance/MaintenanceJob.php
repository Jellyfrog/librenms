<?php

/**
 * MaintenanceJob.php
 *
 * Shared queue configuration for maintenance tasks.
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

namespace App\Jobs\Maintenance;

use App\Maintenance\TaskResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Maintenance work is heavy on the database, so only one task may run at a
 * time. Dispatching these as a batch would run them in parallel -- that is what
 * batches are for -- so the shared lock below is what actually serialises them,
 * whatever number of workers are listening. A chain would serialise them too,
 * but a chain abandons every job after one that fails, and these tasks are
 * meant to be independent.
 *
 * The lock is released rather than dropped, so a task that has to wait still
 * runs later instead of being skipped for the day. Releases consume attempts,
 * hence retryUntil() rather than a tries count; maxExceptions still fails the
 * job on the first genuine error, so a broken task is not retried all day.
 */
abstract class MaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Seconds this task may run before the worker kills it. Override per task.
     */
    public int $timeout = 3600;

    /**
     * A real error fails the job immediately; only waiting for the lock retries.
     */
    public int $maxExceptions = 1;

    /**
     * Keep re-attempting a task that is waiting its turn, but not forever.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(6);
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('maintenance'))
                ->shared() // one key across all maintenance job classes
                ->releaseAfter(60)
                ->expireAfter($this->timeout + 60), // outlive the task it guards
        ];
    }

    /**
     * Turn a task result into queue terms: messages go to the log, and a
     * failure throws so the queue records it rather than passing silently.
     */
    protected function report(TaskResult $result): void
    {
        foreach ($result->messages() as $message) {
            match ($message['level']) {
                TaskResult::ERROR => Log::error($message['text']),
                TaskResult::WARNING => Log::warning($message['text']),
                default => Log::info($message['text']),
            };
        }

        if ($result->failed()) {
            throw new RuntimeException($result->summary());
        }
    }
}
