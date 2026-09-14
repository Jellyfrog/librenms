<?php

namespace LibreNMS\Tests\Mocks\Maintenance;

use App\Jobs\Maintenance\MaintenanceJob;

/**
 * A maintenance job that hangs, for exercising the timeout.
 */
class SleepingJob extends MaintenanceJob
{
    public int $timeout = 30;

    public function handle(): void
    {
        sleep(30);
    }
}
