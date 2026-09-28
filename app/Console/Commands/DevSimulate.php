<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LibreNMS\Util\Snmpsim;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessSignaledException;

class DevSimulate extends LnmsCommand
{
    protected $name = 'dev:simulate';
    protected bool $developer = true;
    /**
     * @var Snmpsim
     */
    protected $snmpsim = null;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate devices using test data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->addArgument('file', InputArgument::OPTIONAL);
        $this->addOption('all', 'a', InputOption::VALUE_NONE);
        $this->addOption('multiple', 'm', InputOption::VALUE_NONE);
        $this->addOption('remove', 'r', InputOption::VALUE_NONE);
        $this->addOption('setup-venv', mode: InputOption::VALUE_NONE);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $file = $this->argument('file');
        if ($file && $this->option('all')) {
            $this->error(trans('commands.dev:simulate.file_and_all'));

            return 1;
        }

        if ($file && ! file_exists(base_path("tests/snmpsim/$file.snmprec"))) {
            $this->error("$file does not exist");

            return 1;
        }

        $this->snmpsim = new Snmpsim;
        if (! $this->snmpsim->isVenvSetUp()) {
            $this->line(trans('commands.dev:simulate.setup', ['dir' => $this->snmpsim->getVenvPath()]));
            $this->snmpsim->setupVenv($this->getOutput()->isVeryVerbose());
        }

        if ($this->option('setup-venv')) {
            return 0; // venv is set up exit
        }

        $this->snmpsim->start();
        $this->line($this->snmpsim->waitForStartup());
        $this->started();
        $this->line(trans('commands.dev:simulate.exit'));
        try {
            $this->snmpsim->wait();
        } catch(ProcessSignaledException $e) {
            $this->error($e->getMessage());

            return 1;
        }

        if (! $this->snmpsim->isSuccessful()) {
            $this->line($this->snmpsim->getErrorOutput());

            return 1;
        }

        return 0;
    }

    private function started(): void
    {
        $communities = $this->option('all') ? $this->snmprecFiles() : array_filter([$this->argument('file')]);
        if (empty($communities)) {
            return;
        }

        // snmpsim selects the snmprec file by community, so all devices share one ip:port
        $use_community_hostname = $this->option('multiple') || $this->option('all');

        if (count($communities) == 1) {
            $community = reset($communities);
            $device = $this->addDevice($community, $use_community_hostname ? $community : 'snmpsim');
            $action = $device->wasRecentlyCreated ? 'added' : 'updated';
            $this->info(trans("commands.dev:simulate.$action", ['hostname' => $device->hostname, 'id' => $device->device_id]));
            $device_ids = [$device->device_id];
        } else {
            $device_ids = [];
            $this->withProgressBar($communities, function (string $community) use (&$device_ids): void {
                $device_ids[] = $this->addDevice($community, $community)->device_id;
            });
            $this->newLine();
            $this->info(trans('commands.dev:simulate.added_all', ['count' => count($device_ids)]));
        }

        $this->queueRemoval($device_ids);
    }

    private function addDevice(string $community, string $hostname): Device
    {
        $device = Device::firstOrNew(['hostname' => $hostname]);
        $device->overwrite_ip = $this->snmpsim->ip;
        $device->port = $this->snmpsim->port;
        $device->snmpver = 'v2c';
        $device->transport = 'udp';
        $device->community = $community;
        $device->last_discovered = null;
        $device->status_reason = '';
        $device->save();

        return $device;
    }

    /**
     * Set up a single removal shutdown function if requested
     *
     * @param  int[]  $device_ids
     */
    private function queueRemoval(array $device_ids): void
    {
        if (! $this->option('remove')) {
            return;
        }

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function (): void {
                exit; // exit normally on SIGINT
            });
        }

        register_shutdown_function(function () use ($device_ids): void {
            if (function_exists('pcntl_signal')) {
                pcntl_signal(SIGINT, SIG_IGN); // don't abort removal part way through
            }

            DB::transaction(function () use ($device_ids): void {
                Device::whereIn('device_id', $device_ids)->get()->each(function (Device $device): void {
                    $device->delete(); // per-model delete so the observer cleans up related data
                    $this->info(trans('commands.dev:simulate.removed', ['id' => $device->device_id]));
                });
            });
            exit;
        });
    }

    /**
     * @return string[]
     */
    private function snmprecFiles(): array
    {
        return array_map(fn ($file) => basename($file, '.snmprec'), glob(base_path('tests/snmpsim/*.snmprec')));
    }

    public function completeArgument($name, $value)
    {
        if ($name == 'file') {
            return collect($this->snmprecFiles())->filter(fn ($snmprec) => ! $value || Str::startsWith($snmprec, $value))->all();
        }

        return false;
    }
}
