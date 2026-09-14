<?php

namespace App\Console\Commands;

use App\Actions\Maintenance\RefreshSslCertificates;
use App\Console\Commands\Traits\RendersTaskResult;
use App\Console\LnmsCommand;
use Symfony\Component\Console\Input\InputOption;

class MaintenanceRefreshSslCertificates extends LnmsCommand
{
    use RendersTaskResult;

    protected $name = 'maintenance:refresh-ssl-certificates';

    public function __construct()
    {
        parent::__construct();
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL);
    }

    /**
     * Execute the console command.
     */
    public function handle(RefreshSslCertificates $action): int
    {
        $id = $this->option('id');

        return $this->renderTaskResult($action->execute(is_numeric($id) ? (int) $id : null));
    }
}
