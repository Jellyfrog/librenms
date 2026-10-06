<?php

namespace App\Jobs;

use App\Facades\LibrenmsConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Run a maintenance artisan command on the maintenance queue.
 */
class RunMaintenanceCommand extends MaintenanceJob
{
    public readonly string $command;

    /**
     * @param  class-string<Command>|string  $command  command class or name
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(string $command, public readonly array $parameters = [])
    {
        parent::__construct();

        $this->command = class_exists($command) ? (string) app($command)->getName() : $command;
    }

    public function displayName(): string
    {
        return $this->command;
    }

    public function handle(): void
    {
        $output = new BufferedOutput;
        $exitCode = Artisan::call($this->command, $this->parameters, $output);

        File::append(LibrenmsConfig::get('log_dir') . '/maintenance.log', $output->fetch());

        if ($exitCode !== 0) {
            throw new RuntimeException("$this->command exited with code $exitCode, check maintenance.log for details");
        }
    }
}
