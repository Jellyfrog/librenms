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
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

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
        $proc->disableOutput();
        $proc->run();

        if ($proc->getExitCode() > 0) {
            $this->job->fail();
        }
    }
}
