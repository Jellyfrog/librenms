<?php

namespace LibreNMS\Tests\Mocks\Maintenance;

use App\Jobs\Maintenance\MaintenanceJob;

/**
 * A maintenance job that does nothing and succeeds.
 */
class PassingJob extends MaintenanceJob
{
    public int $timeout = 60;

    public function handle(): void
    {
    }
}
