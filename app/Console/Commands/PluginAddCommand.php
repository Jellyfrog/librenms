<?php

namespace App\Console\Commands;

use App\Plugins\PluginRoot;
use Illuminate\Console\Command;

class PluginAddCommand extends Command
{
    protected $signature = 'plugin:add {package : Composer package name with an optional version constraint, for example vendor/my-plugin:^1.0}';

    protected $description = 'Install a plugin package';

    public function handle(): int
    {
        $package = (string) $this->argument('package');
        $name = strtolower(preg_split('/[:= ]/', $package, 2)[0]);

        if (array_key_exists($name, PluginRoot::replaceBlock())) {
            $this->error("$name is already installed by LibreNMS.");

            return 1;
        }

        if (PluginRoot::composer(['require', '--update-no-dev', '--', $package]) !== 0) {
            $this->error('Failed to install, see the composer output above.');

            return 1;
        }

        return 0;
    }
}
