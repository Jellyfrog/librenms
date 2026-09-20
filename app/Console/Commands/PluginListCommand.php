<?php

namespace App\Console\Commands;

use App\Plugins\PluginRoot;
use Illuminate\Console\Command;

class PluginListCommand extends Command
{
    protected $signature = 'plugin:list';

    protected $description = 'List installed plugin packages';

    public function handle(): int
    {
        $required = PluginRoot::required();

        if ($required === []) {
            $this->line('No plugin packages are installed. Install one with: lnms plugin:add vendor/package');

            return 0;
        }

        $installed = PluginRoot::installed();

        $this->table(['Package', 'Required', 'Installed'], array_map(
            fn ($name, $constraint) => [$name, $constraint, $installed[$name] ?? '<comment>not installed</comment>'],
            array_keys($required),
            $required
        ));

        return 0;
    }
}
