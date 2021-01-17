<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Jobs\PollDevice;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;
use LibreNMS\Config;
use LibreNMS\Util\OS;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DevicePollerSchedulerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'poller:devices:schedule {--debug}';

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
        Device::select(['device_id', 'poller_group'])
            ->where('disabled', 0)
            ->orderByDesc('last_polled_timetaken')
            ->get()
            ->groupBy('poller_group')
            ->each(function ($poller_devices, $poller_group) {
                $this->dispatchJobs($poller_group, $poller_devices);
            });

        return 0;
    }

    private function dispatchJobs($poller_group, $poller_devices)
    {
        $batch = Bus::batch(
           $poller_devices->map(function ($device) {
                return new PollDevice($device, $this->option('debug'));
            })
        )->then(function (Batch $batch) {
            dump("batch then()");

        })->catch(function (Batch $batch, Throwable $e) {
            self::handleCatch($batch, $e);
        })->finally(function (Batch $batch) {
            self::handleFinally($batch);
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

    private static function handleFinally($batch)
    {
        dump("batch finally()");
        dump("Started: $batch->createdAt");
        dump("Finished: $batch->finishedAt");
        dump("Seconds: ". $batch->createdAt->diffInSeconds($batch->finishedAt));
    //    dump($batch);

        $devices_polled = $batch->totalJobs - $batch->failedJobs;


    }

}
