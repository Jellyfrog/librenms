<?php

namespace App\Console\Commands;

use App\Plugins\PluginRoot;
use Illuminate\Console\Command;

class PluginSyncCommand extends Command
{
    protected $signature = 'plugin:sync';

    protected $description = 'Update plugin packages and re-resolve them against the current LibreNMS dependencies';

    public function handle(): int
    {
        if (PluginRoot::required() === []) {
            $this->line('No plugin packages are installed.');

            return 0;
        }

        if (PluginRoot::composer(['update', '--no-dev']) !== 0) {
            $this->error('Plugin packages could not be resolved against the current LibreNMS dependencies.');
            $this->line('LibreNMS itself is unaffected. See the composer output above.');

            return 1;
        }

        $this->info('Plugin packages are in sync.');

        return 0;
    }
}
