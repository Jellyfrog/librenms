<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Facades\LibrenmsConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;

class MaintenanceCleanupRrd extends LnmsCommand
{
    protected $name = 'maintenance:cleanup-rrd';

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
            $days = LibrenmsConfig::get('rrd_purge');

            if (! is_numeric($days)) {
                $this->warn(__('commands.maintenance:cleanup-rrd.bad_days_setting'));

                return 0;
            }
        } elseif (! is_numeric($days)) {
            $this->error(__('commands.maintenance:cleanup-rrd.bad_days_input'));

            return 1;
        }

        if ($days <= 0) {
            $this->warn(__('commands.maintenance:cleanup-rrd.disabled'));

            return 0;
        }

        $rrd_dir = (string) LibrenmsConfig::get('rrd_dir');

        if ($rrd_dir === '' || ! is_dir($rrd_dir) || ! is_readable($rrd_dir)) {
            $this->warn(trans('commands.maintenance:cleanup-rrd.bad_directory', ['dir' => $rrd_dir]));

            return 0;
        }

        // find -mtime +N deletes files last modified more than N*24 hours ago
        $cutoff = Carbon::now()->subDays((int) $days)->getTimestamp();

        $finder = Finder::create()
            ->files() // never remove directories
            ->in($rrd_dir)
            ->ignoreDotFiles(false) // find does not skip dot files
            ->ignoreVCS(false)
            ->ignoreVCSIgnored(false)
            ->ignoreUnreadableDirs()
            ->notName('.gitignore');

        $deleted = 0;
        $failed = 0;
        $verbose = $this->getOutput()->isVerbose();

        foreach ($finder as $file) {
            $path = $file->getPathname();

            if ($file->getMTime() >= $cutoff) {
                continue;
            }

            if (File::delete($path)) {
                $deleted++;

                if ($verbose) {
                    $this->line(trans('commands.maintenance:cleanup-rrd.deleted_file', ['file' => $path]));
                }
            } else {
                $failed++;
                $this->error(trans('commands.maintenance:cleanup-rrd.delete_failed', ['file' => $path]));
            }
        }

        $this->line(trans('commands.maintenance:cleanup-rrd.delete', ['days' => $days, 'count' => $deleted]));

        if ($failed > 0) {
            $this->warn(trans_choice('commands.maintenance:cleanup-rrd.delete_failed_count', $failed, ['count' => $failed]));
        }

        return 0;
    }
}
