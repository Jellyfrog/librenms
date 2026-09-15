<?php

namespace App\Console\Commands;

use App\Plugins\PluginRoot;
use Illuminate\Console\Command;

class PluginRemoveCommand extends Command
{
    protected $signature = 'plugin:remove {package* : Composer package name to remove}';

    protected $description = 'Remove an installed plugin package';

    public function handle(): int
    {
        if (PluginRoot::composer(array_merge(['remove', '--update-no-dev', '--'], (array) $this->argument('package'))) !== 0) {
            $this->error('Failed to remove, see the composer output above.');

            return 1;
        }

        return 0;
    }
}
