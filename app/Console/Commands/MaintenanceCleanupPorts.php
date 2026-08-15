<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Facades\LibrenmsConfig;
use App\Models\Port;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Input\InputOption;

class MaintenanceCleanupPorts extends LnmsCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'maintenance:cleanup-ports';

    public function __construct()
    {
        parent::__construct();

        $this->addOption('force', null, InputOption::VALUE_NONE);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! LibrenmsConfig::get('ports_purge') && ! $this->option('force')) {
            return 0;
        }

        $deleted_total = 0;

        // Port has a deleting hook that removes related rows and rrd files, so each port must be
        // deleted individually.  The device is eager loaded because the rrd purge needs its hostname.
        Port::with(['device' => fn ($query) => $query->select('device_id', 'hostname')])
            ->isDeleted()
            ->chunkById(100, function (Collection $ports) use (&$deleted_total): void {
                /** @var Port $port */
                foreach ($ports as $port) {
                    $port->delete();
                    $deleted_total++;
                }
            });

        $this->line(trans('commands.maintenance:cleanup-ports.delete', ['count' => $deleted_total]));

        return 0;
    }
}
