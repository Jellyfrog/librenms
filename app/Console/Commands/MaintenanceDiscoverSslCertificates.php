<?php

namespace App\Console\Commands;

use App\Actions\Maintenance\DiscoverSslCertificates;
use App\Console\Commands\Traits\RendersTaskResult;
use App\Console\LnmsCommand;
use Symfony\Component\Console\Input\InputOption;

class MaintenanceDiscoverSslCertificates extends LnmsCommand
{
    use RendersTaskResult;

    protected $name = 'maintenance:discover-ssl-certificates';

    public function __construct()
    {
        parent::__construct();
        $this->addOption('device', 'd', InputOption::VALUE_OPTIONAL);
        $this->addOption('force', null, InputOption::VALUE_NONE);
    }

    /**
     * Execute the console command.
     */
    public function handle(DiscoverSslCertificates $action): int
    {
        return $this->renderTaskResult($action->execute(
            $this->option('device') ?? 'all',
            (bool) $this->option('force'),
        ));
    }
}
