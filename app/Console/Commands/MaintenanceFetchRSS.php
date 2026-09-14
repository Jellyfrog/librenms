<?php

namespace App\Console\Commands;

use App\Actions\Maintenance\FetchRss;
use App\Console\LnmsCommand;
use App\Console\Commands\Traits\RendersTaskResult;

class MaintenanceFetchRSS extends LnmsCommand
{
    use RendersTaskResult;

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'maintenance:fetch-rss';

    /**
     * Execute the console command.
     */
    public function handle(FetchRss $action): int
    {
        return $this->renderTaskResult($action->execute());
    }
}
