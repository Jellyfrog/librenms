<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Jobs\Poller;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;
use LibreNMS\Config;
use LibreNMS\Util\OS;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PollerScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poller:schedule {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if($this->option('debug')) {
            // Force update of OS Cache
            OS::updateCache(true);
        }

        $devices = Device::poller()
            ->get()
            ->groupBy('poller_group');


        foreach($devices as $poller_group => $poller_devices) {

            $jobs = $poller_devices->map(function ($device) {
                return new Poller($device);
            });

            // FIX
            $doing = 'all';

            $this->dispatchJobs($jobs, $poller_group, $doing);
        }

        return 0;
    }

    private function dispatchJobs($jobs, $poller_group, $doing)
    {
        $batch = Bus::batch(
           $jobs,

           /*[
            new Poller(Device::first(), 1),
            new Poller(Device::first(), 2),
            new Poller(Device::first(), 'fail'),
            new Poller(Device::first(), 4),
            new Poller(Device::first(), 5),
            ]*/
        )->then(function (Batch $batch) {
            dump("batch then()");

        })->catch(function (Batch $batch, Throwable $e) {
            self::handleCatch($batch, $e);
        })->finally(function (Batch $batch) use($doing) {
            self::handleFinally($batch, $doing);
        })
        ->allowFailures()
        ->onQueue('poller_' . $poller_group)
        ->dispatch();

        return $batch;
    }

    private static function handleCatch($batch, $e)
    {
        dump("batch catch()");

        //TODO: Log
        if($e instanceof \Illuminate\Queue\MaxAttemptsExceededException) {
            dump("Timeout reached!");
            // didnt make it in 5min time
        } else {
            // other errors
            dump($e);
        }
    }

    private static function handleFinally($batch, $doing)
    {
        dump("batch finally()");
        dump("Started: $batch->createdAt");
        dump("Finished: $batch->finishedAt");
        dump("Seconds: ". $batch->createdAt->diffInSeconds($batch->finishedAt));
 //       dump($batch);

        $devices_polled = $batch->totalJobs - $batch->failedJobs;

        // This does not work like the original code
        if ($devices_polled) {
            DB::table('perf_times')->insert([
                'type' => 'poll',
                'doing' => $doing,
                'start' => $batch->createdAt->unix(),
                'duration' => $batch->createdAt->diffInSeconds($batch->finishedAt),
                'devices' => $devices_polled,
                'poller' => Config::get('base_url'),
            ]);
        }

        $string = " $doing " . date(Config::get('dateformat.compact')) . " - $devices_polled devices polled in $poller_time secs";
        echo("$string\n");

        //TODO: own log-channel for poller?
        Log::info($string);
    }

}
