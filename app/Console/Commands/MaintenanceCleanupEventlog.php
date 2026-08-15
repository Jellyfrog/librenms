<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Facades\LibrenmsConfig;
use App\Models\Eventlog;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;

class MaintenanceCleanupEventlog extends LnmsCommand
{
    protected $name = 'maintenance:cleanup-eventlog';

    public function __construct()
    {
        parent::__construct();
        $this->addArgument('days', InputArgument::OPTIONAL);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->argument('days');

        if ($days === null) {
            $days = LibrenmsConfig::get('eventlog_purge');

            if (! is_numeric($days)) {
                $this->warn(__('commands.maintenance:cleanup-eventlog.bad_days_setting'));

                return 0;
            }
        } elseif (! is_numeric($days)) {
            $this->error(__('commands.maintenance:cleanup-eventlog.bad_days_input'));

            return 1;
        }

        if ($days <= 0) {
            $this->warn(__('commands.maintenance:cleanup-eventlog.disabled'));

            return 0;
        }

        $deleted_total = 0;
        $deleted_rows = 1;
        while ($deleted_rows > 0) {
            $deleted_rows = Eventlog::where('datetime', '<', Carbon::now()->subDays($days))
                ->limit(5000)
                ->delete();
            $deleted_total += $deleted_rows;
        }

        $this->line(trans('commands.maintenance:cleanup-eventlog.delete', ['days' => $days, 'count' => $deleted_total]));

        return 0;
    }
}
