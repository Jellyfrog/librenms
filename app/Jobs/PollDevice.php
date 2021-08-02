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

use Log;

class PollDevice implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function middleware()
    {
        return [
            new MaxTries,
            (new WithoutOverlapping($this->device->device_id))
                ->dontRelease()     // TODO: COMMENT
                ->expireAfter(180), // TODO: Fix
        ];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Device $device, bool $debug = false)
    {
        $this->device = $device;
        $this->debug = $debug;
    }

    /**
     *  Build command array
     *
     */
    public function getCommand(): array
    {
        $output = $this->debug ?
            [
                "-d",
                ">>",
                Config::get('log_dir') . "/poll_device_{$this->device->device_id}.log", // TODO: Replace with Log()... ?
            ]
            :
            [">> /dev/null"];

        $command = [
            '/usr/bin/env',
            'php',
            Config::get('install_dir') . '/poller.php',
            '-h',
            $this->device->device_id,
        ];
        $command = array_merge($command, $output);
        $command[] = "2>&1";

        return $command;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Determine if the batch has been cancelled
        if (optional($this->batch())->cancelled()) {
            return;
        }

        Log::error("running");

        if($this->device->device_id == 1) {
            sleep(10);
        }

        sleep(1);
        return;

        // TODO: set timeout to X
        $proc = new Process($this->getCommand());
        $proc->disableOutput();
        $proc->run();

        if ($proc->getExitCode() > 0) {
            $this->job->fail();
        }
    }
}
