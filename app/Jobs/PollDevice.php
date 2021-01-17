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
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    // TODO: should we set this? might be good for stuck jobs?
    // needs to be set to whatever polling frequency we have, or slightly less?
    public $timeout;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        // TODO: This cannot be used alone, because it will override $this->tries,
        // so it will just re-run the job as many times as it can if it fails.
     //   return now()->addSeconds(300);
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
        // Make job timeout 1 second before next one should run
        $this->timeout = Config::get('rrd.step') - 1;
    }

    public function getCommand(): array
    {
        $output = $this->debug ?
            [
                "-d",
                ">>",
                Config::get('log_dir') . "/poll_device_{$this->device->device_id}.log",
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
        if ($this->batch()->cancelled()) {
            return;
        }

        $proc = new Process($this->getCommand());
        $proc->setTimeout($this->timeout);
        $proc->disableOutput();
        $proc->run();

        if ($proc->getExitCode() > 0) {
            $this->job->fail();
        }
    }
}
