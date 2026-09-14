<?php

namespace LibreNMS\Tests\Mocks\Maintenance;

use App\Jobs\Maintenance\MaintenanceJob;

/**
 * A maintenance job whose process dies without the job failing: the stand-in
 * for a fatal error or being killed, neither of which the job can report.
 */
class ExitingJob extends MaintenanceJob
{
    public int $timeout = 60;

    public function handle(): void
    {
        exit(3);
    }
}
