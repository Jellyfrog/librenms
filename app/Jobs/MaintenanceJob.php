<?php

namespace App\Jobs;

use App\Jobs\Middleware\RecordMaintenanceJobRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Base class for maintenance jobs.
 *
 * Jobs are pushed to the maintenance queue and processed one at a time by the
 * worker the scheduler launches (see routes/console.php), so a long running job
 * never blocks the scheduler and maintenance jobs never run in parallel.
 * Each run is recorded in maintenance_job_runs.
 *
 * Schedule a job with:
 *   Schedule::job(new MyMaintenanceJob)->dailyAt('03:00')->onOneServer();
 */
abstract class MaintenanceJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Longest a maintenance job may run, in seconds, before it is considered dead */
    public const MAX_RUNTIME = 86400;

    /** Maintenance jobs can run for hours, never time out */
    public int $timeout = 0;

    /** Do not retry automatically, the next scheduled run will try again */
    public int $tries = 1;

    /** Release the unique lock after this many seconds, in case a run was killed */
    public int $uniqueFor = self::MAX_RUNTIME;

    public function __construct()
    {
        $this->onConnection('maintenance');
    }

    /**
     * Name used in the schedule, queue payload and maintenance_job_runs.
     */
    public function displayName(): string
    {
        return static::class;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new RecordMaintenanceJobRun];
    }
}
