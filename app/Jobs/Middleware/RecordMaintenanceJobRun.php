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
        $started = now();

        $run = MaintenanceJobRun::create([
            'job' => $job->displayName(),
            'poller_name' => LibrenmsConfig::get('distributed_poller_name'),
            'queued_at' => $createdAt ? Carbon::createFromTimestamp($createdAt, $started->getTimezone()) : null,
            'started_at' => $started,
            'status' => MaintenanceJobRun::STATUS_RUNNING,
        ]);

        $status = MaintenanceJobRun::STATUS_FAILED;
        $error = null;

        try {
            $result = $next($job);
            $status = MaintenanceJobRun::STATUS_SUCCESS;

            return $result;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Eventlog::log("Maintenance job {$job->displayName()} failed: $error", null, 'maintenance', Severity::Error);

            throw $e;
        } finally {
            $finished = now();
            $run->update([
                'finished_at' => $finished,
                'duration_ms' => (int) $started->diffInMilliseconds($finished),
                'status' => $status,
                'exception' => $error,
            ]);
        }
    }
}
