<?php

namespace App\Jobs\Middleware;

use App\Facades\LibrenmsConfig;
use App\Jobs\MaintenanceJob;
use App\Models\Eventlog;
use App\Models\MaintenanceJobRun;
use Carbon\Carbon;
use Closure;
use LibreNMS\Enum\Severity;
use Throwable;

/**
 * Record start, end, duration and result of each maintenance job run.
 * The row is created before the job runs so killed jobs stay visible as running.
 */
class RecordMaintenanceJobRun
{
    public function handle(MaintenanceJob $job, Closure $next): mixed
    {
        $createdAt = $job->job?->payload()['createdAt'] ?? null;

        $run = MaintenanceJobRun::create([
            'job' => $job->displayName(),
            'poller_name' => LibrenmsConfig::get('distributed_poller_name'),
            'queued_at' => $createdAt ? Carbon::createFromTimestamp($createdAt) : null,
            'started_at' => now(),
            'status' => MaintenanceJobRun::STATUS_RUNNING,
        ]);

        $start = hrtime(true);

        try {
            $result = $next($job);
        } catch (Throwable $e) {
            $run->update([
                'finished_at' => now(),
                'duration_ms' => intdiv(hrtime(true) - $start, 1_000_000),
                'status' => MaintenanceJobRun::STATUS_FAILED,
                'exception' => $e->getMessage(),
            ]);

            Eventlog::log("Maintenance job {$job->displayName()} failed: {$e->getMessage()}", null, 'maintenance', Severity::Error);

            throw $e;
        }

        $run->update([
            'finished_at' => now(),
            'duration_ms' => intdiv(hrtime(true) - $start, 1_000_000),
            'status' => MaintenanceJobRun::STATUS_SUCCESS,
        ]);

        return $result;
    }
}
