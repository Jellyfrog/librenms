<?php

namespace App\Jobs;

use App\Facades\LibrenmsConfig;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Run a maintenance artisan command on the maintenance queue.
 */
class RunMaintenanceCommand extends MaintenanceJob
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(public readonly string $command, public readonly array $parameters = [])
    {
        parent::__construct();
    }

    public function displayName(): string
    {
        return $this->command;
    }

    public function uniqueId(): string
    {
        return $this->command;
    }

    public function handle(): void
    {
        $log = fopen(LibrenmsConfig::get('log_dir') . '/maintenance.log', 'a');
        if ($log === false) {
            throw new RuntimeException('Unable to open maintenance.log');
        }

        try {
            $exitCode = Artisan::call($this->command, $this->parameters, new StreamOutput($log));
        } finally {
            fclose($log);
        }

        if ($exitCode !== 0) {
            throw new RuntimeException("$this->command exited with code $exitCode, check maintenance.log for details");
        }
    }
}
