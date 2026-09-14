<?php

namespace App\Console\Commands;

use App\Actions\Maintenance\CleanupSyslog;
use App\Console\Commands\Traits\RendersTaskResult;
use App\Console\LnmsCommand;
use Symfony\Component\Console\Input\InputArgument;

class MaintenanceCleanupSyslog extends LnmsCommand
{
    use RendersTaskResult;

    protected $name = 'maintenance:cleanup-syslog';

    public function __construct()
    {
        parent::__construct();
        $this->addArgument('days', InputArgument::OPTIONAL);
    }

    /**
     * Execute the console command.
     */
    public function handle(CleanupSyslog $action): int
    {
        $days = $this->argument('days');

        // What someone typed is checked here; what the config says is checked
        // by the task, so a queued run gets the same answer as a manual one.
        if ($days !== null && ! is_numeric($days)) {
            $this->error(__('commands.maintenance:cleanup-syslog.bad_days_input'));

            return 1;
        }

        return $this->renderTaskResult($action->execute($days === null ? null : (int) $days));
    }
}
