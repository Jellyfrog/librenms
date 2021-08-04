<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Device;
use LibreNMS\Config;
use Symfony\Component\Process\Process;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Jobs\Middleware\MaxTries;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class PollDevice implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $php_process_timeout;
    private $time_start;
    private $device;
    private $debug;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return $this->time_start->addSeconds(Config::getPollingInterval());
    }

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [
            new MaxTries,
            (new WithoutOverlapping($this->device->device_id))
                ->dontRelease()                            // Delete the overlapping job
                ->expireAfter($this->php_process_timeout), // TTL of the lock
        ];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Device $device, Carbon $time_start, bool $debug = false)
    {
        $this->device = $device;
        $this->debug = $debug;
        $this->time_start = $time_start;

        // When poller.php process should timeout
        // 3 times the polling interval seems like a sane value
        $this->php_process_timeout = Config::getPollingInterval() * 3;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Determine if the batch has been cancelled
        if (optional($this->batch())->cancelled()) {
            return;
        }

        $process = new Process($this->getCommand());
        $process->setTimeout($this->php_process_timeout);
        $process->disableOutput();
        $process->run();

        // TODO: exit code 6 means "unrechable device"

        if ($process->getExitCode() > 0) {
            dump("exit code:" . $process->getExitCode());
            $this->job->fail();
            return;
        }

        /**
         * Record devices actually polled
         *
         * This is needed due to not possible to distinguish between
         * jobs filtered by middleware and actual succesful jobs
         */
        if ($this->batch()) {
            Cache::increment("librenms-poller:" . $this->batch()->id);
        }
    }

    /**
     *  Build command array
     *
     */
    private function getCommand(): array
    {
        $command = [
            '/usr/bin/env',
            'php',
            Config::get('install_dir') . '/poller.php',
            '-h',
            $this->device->device_id,
        ];

        $output = $this->debug ?
            [
                "-d",
                ">>",
                Config::get('log_dir') . "/poll_device_{$this->device->device_id}.log", // TODO: Replace with Log()... ?
            ]
            :
            [">> /dev/null"];

        $command = array_merge($command, $output);
        $command[] = "2>&1";

        return $command;
    }
}
