<?php

namespace App\Console\Commands;

use App\Actions\Maintenance\FetchOuis;
use App\Console\Commands\Traits\RendersTaskResult;
use App\Console\LnmsCommand;
use App\Facades\LibrenmsConfig;
use Symfony\Component\Console\Input\InputOption;

class MaintenanceFetchOuis extends LnmsCommand
{
    use RendersTaskResult;

    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'maintenance:fetch-ouis';

    public function __construct()
    {
        parent::__construct();

        $this->addOption('force', null, InputOption::VALUE_NONE);
    }

    /**
     * Execute the console command.
     */
    public function handle(FetchOuis $action): int
    {
        $force = (bool) $this->option('force');

        // Offering to switch the feature on is a conversation with whoever is
        // at the keyboard, so it lives here and nowhere near the job. persist()
        // updates the loaded config too, so the task sees the answer.
        if (LibrenmsConfig::get('mac_oui.enabled') !== true && ! $force) {
            $this->line(trans('commands.maintenance:fetch-ouis.disabled', ['setting' => 'mac_oui.enabled']));

            if (! $this->confirm(trans('commands.maintenance:fetch-ouis.enable_question'))) {
                return 0;
            }

            LibrenmsConfig::persist('mac_oui.enabled', true);
        }

        return $this->renderTaskResult($action->execute($force));
    }
}
