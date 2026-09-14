<?php

namespace App\Console\Commands;

use App\Actions\Maintenance\CleanupNetworks;
use App\Console\Commands\Traits\RendersTaskResult;
use App\Console\LnmsCommand;
use Symfony\Component\Console\Input\InputOption;

class MaintenanceCleanupNetworks extends LnmsCommand
{
    use RendersTaskResult;

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'maintenance:cleanup-networks';

    public function __construct()
    {
        parent::__construct();

        $this->addOption('force', null, InputOption::VALUE_NONE);
    }

    /**
     * Execute the console command.
     */
    public function handle(CleanupNetworks $action): int
    {
        return $this->renderTaskResult($action->execute((bool) $this->option('force')));
    }
}
