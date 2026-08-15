<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Facades\LibrenmsConfig;
use App\Models\AuthLog;
use Carbon\Carbon;
use Symfony\Component\Console\Input\InputArgument;

class MaintenanceCleanupAuthlog extends LnmsCommand
{
    protected $name = 'maintenance:cleanup-authlog';

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
            $days = LibrenmsConfig::get('authlog_purge');

            if (! is_numeric($days)) {
                $this->warn(__('commands.maintenance:cleanup-authlog.bad_days_setting'));

                return 0;
            }
        } elseif (! is_numeric($days)) {
            $this->error(__('commands.maintenance:cleanup-authlog.bad_days_input'));

            return 1;
        }

        if ($days <= 0) {
            $this->warn(__('commands.maintenance:cleanup-authlog.disabled'));

            return 0;
        }

        $deleted_total = 0;
        $deleted_rows = 1;
        while ($deleted_rows > 0) {
            $deleted_rows = AuthLog::where('datetime', '<', Carbon::now()->subDays($days))
                ->limit(5000)
                ->delete();
            $deleted_total += $deleted_rows;
        }

        $this->line(trans('commands.maintenance:cleanup-authlog.delete', ['days' => $days, 'count' => $deleted_total]));

        return 0;
    }
}
