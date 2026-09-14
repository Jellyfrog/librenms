<?php

namespace LibreNMS\Tests\Mocks\Maintenance;

use App\Jobs\Maintenance\MaintenanceJob;
use App\Maintenance\TaskResult;

/**
 * A maintenance job whose task reports a failure, the way a real one would.
 */
class FailingJob extends MaintenanceJob
{
    public int $timeout = 60;

    public function handle(): void
    {
        $this->report(TaskResult::make()->error('FailingJob failed on purpose'));
    }
}
