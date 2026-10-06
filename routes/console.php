<?php

use App\Facades\LibrenmsConfig;
use App\Jobs\MaintenanceJob;
use App\Jobs\PingCheck;
use App\Jobs\RunMaintenanceCommand;
use App\Models\MaintenanceJobRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schedule;
use LibreNMS\Util\Time;
use Symfony\Component\Process\Process;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('update', function (): void {
    (new Process([base_path('daily.sh')]))->setTimeout(null)->setIdleTimeout(null)->setTty(true)->run();
})->purpose(__('Update LibreNMS and run maintenance routines'));

Artisan::command('poller:ping
    {groups?* : ' . __('Optional List of distributed poller groups to poll') . '}
', function (): void {
    PingCheck::dispatch($this->argument('groups'));
})->purpose(__('Check if devices are up or down via icmp'));

Artisan::command('poller:alerts', function (): void {
    $command = [base_path('alerts.php')];
    if (($verbosity = $this->getOutput()->getVerbosity()) >= 128) {
        $command[] = '-d';
        if ($verbosity >= 256) {
            $command[] = '-v';
        }
    }

    (new Process($command))->setTimeout(null)->setIdleTimeout(null)->setTty(true)->run();
})->purpose(__('Check for any pending alerts and deliver them via defined transports'));

Artisan::command('poller:billing
    {bill id? : ' . __('The bill id to poll') . '}
', function (): void {
    /** @var Illuminate\Console\Command $this */
    $command = [base_path('poll-billing.php')];
    if ($this->argument('bill id')) {
        $command[] = '-b';
        $command[] = $this->argument('bill id');
    }

    if (($verbosity = $this->getOutput()->getVerbosity()) >= 128) {
        $command[] = '-d';
        if ($verbosity >= 256) {
            $command[] = '-v';
        }
    }
    (new Process($command))->setTimeout(null)->setIdleTimeout(null)->setTty(true)->run();
})->purpose(__('Collect billing data'));

Artisan::command('poller:services
    {device spec : ' . __('Device spec to poll: device_id, hostname, wildcard, all') . '}
    {--x|no-data : ' . __('Do not update datastores (RRD, InfluxDB, etc)') . '}
', function (): void {
    /** @var Illuminate\Console\Command $this */
    $command = [base_path('check-services.php')];
    if ($this->option('no-data')) {
        array_push($command, '-r', '-f', '-p');
    }
    if ($this->argument('device spec') !== 'all') {
        $command[] = '-h';
        $command[] = $this->argument('device spec');
    }

    if (($verbosity = $this->getOutput()->getVerbosity()) >= 128) {
        $command[] = '-d';
        if ($verbosity >= 256) {
            $command[] = '-v';
        }
    }
    (new Process($command))->setTimeout(null)->setIdleTimeout(null)->setTty(true)->run();
})->purpose(__('Update LibreNMS and run maintenance routines'));

Artisan::command('poller:billing-calculate
    {--c|clear-history : ' . __('Delete all billing history') . '}
', function (): void {
    /** @var Illuminate\Console\Command $this */
    $command = [base_path('billing-calculate.php')];
    if ($this->option('clear-history')) {
        $command[] = '-r';
    }

    (new Process($command))->setTimeout(null)->setIdleTimeout(null)->setTty(true)->run();
})->purpose(__('Run billing calculations'));

Artisan::command('scan
    {network?* : ' . __('CIDR notation network(s) to scan, can be ommited if \'nets\' config is set') . '}
    {--P|ping-only : ' . __('Add the device as a ping only device if it replies to ping but not SNMP') . '}
    {--o|dns-only : ' . __('Only DNS resolved Devices') . '}
    {--t|threads=32 : ' . __('How many IPs to scan at a time, more will increase the scan speed, but could overload your system') . '}
    {--l|legend : ' . __('Print the legend') . '}
', function () {
    /** @var Illuminate\Console\Command $this */
    $command = [base_path('snmp-scan.py')];

    if (empty($this->argument('network')) && ! LibrenmsConfig::has('nets')) {
        $this->error(__('Network is required if \'nets\' is not set in the config'));

        return 1;
    }

    if ($this->option('dns-only')) {
        $command[] = '-o';
    }

    if ($this->option('ping-only')) {
        $command[] = '-P';
    }

    $command[] = '-t';
    $command[] = $this->option('threads');

    if ($this->option('legend')) {
        $command[] = '-l';
    }

    $verbosity = $this->getOutput()->getVerbosity();
    if ($verbosity >= 64) {
        $command[] = '-v';
        if ($verbosity >= 128) {
            $command[] = '-v';
            if ($verbosity >= 256) {
                $command[] = '-v';
            }
        }
    }

    $command = array_merge($command, $this->argument('network'));

    $scan_process = (new Process($command))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setTty(Process::isTtySupported() && ! $this->option('quiet'));
    $scan_process->run();

    if (! Process::isTtySupported() && ! $this->option('quiet')) {
        // just dump the output after we are done if we couldn't use tty
        $this->line($scan_process->getOutput());
    }

    return $scan_process->getExitCode();
})->purpose(__('Scan the network for hosts and try to add them to LibreNMS'));

// mark schedule working
Schedule::call(function (): void {
    Cache::put('scheduler_working', now()->timestamp, now()->addMinutes(6));
})->name('schedule operational check')->everyFiveMinutes();

// schedule maintenance, should be after all others
$maintenance_log_file = LibrenmsConfig::get('log_dir') . '/maintenance.log';

Schedule::command('model:prune', ['--model' => [MaintenanceJobRun::class]])
    ->dailyAt(Time::pseudoRandomBetween('00:00', '00:59'))
    ->onOneServer()
    ->appendOutputTo($maintenance_log_file);

// Maintenance commands are queued on the maintenance queue, see App\Jobs\MaintenanceJob
Schedule::job(new RunMaintenanceCommand('maintenance:fetch-ouis'))
    ->weeklyOn(0, Time::pseudoRandomBetween('01:00', '01:59'))
    ->onOneServer();

Schedule::job(new RunMaintenanceCommand('maintenance:cleanup-networks'))
    ->weeklyOn(0, Time::pseudoRandomBetween('02:00', '02:59'))
    ->onOneServer();

Schedule::job(new RunMaintenanceCommand('maintenance:fetch-rss'))
    ->dailyAt(Time::pseudoRandomBetween('03:00', '03:59'))
    ->onOneServer();

Schedule::job(new RunMaintenanceCommand('maintenance:cleanup-syslog'))
    ->hourlyAt(17)
    ->onOneServer();

Schedule::job(new RunMaintenanceCommand('maintenance:discover-ssl-certificates'))
    ->dailyAt(Time::pseudoRandomBetween('04:00', '04:59'))
    ->onOneServer()
    ->when(fn () => LibrenmsConfig::get('ssl_certificates.auto_discover', false));

Schedule::job(new RunMaintenanceCommand('maintenance:refresh-ssl-certificates'))
    ->dailyAt(Time::pseudoRandomBetween('05:00', '05:59'))
    ->onOneServer();

Schedule::job(new RunMaintenanceCommand('maintenance:cache-peeringdb'))
    ->dailyAt(Time::pseudoRandomBetween('06:00', '06:59'))
    ->onOneServer()
    ->when(fn () => LibrenmsConfig::get('peeringdb.enabled'));

// Maintenance queue worker, processes App\Jobs\MaintenanceJob jobs one at a time.
// Runs in the background so long jobs do not block the scheduler and exits when the queue is empty.
// Defined after the jobs so jobs queued this minute are picked up right away.
Schedule::command('queue:work', ['maintenance', '--stop-when-empty'])
    ->name('maintenance queue worker')
    ->everyMinute()
    ->when(fn () => Queue::connection('maintenance')->size() > 0)
    ->onOneServer()
    ->withoutOverlapping(MaintenanceJob::MAX_RUNTIME / 60)
    ->runInBackground()
    ->appendOutputTo($maintenance_log_file);
