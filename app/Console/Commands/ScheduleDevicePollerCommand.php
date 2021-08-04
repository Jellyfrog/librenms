<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Poller;
use App\Jobs\PollDevice;
use Illuminate\Bus\Batch;
use Throwable;
use LibreNMS\Config;
use Illuminate\Support\Facades\Cache;
use Log;

class ScheduleDevicePollerCommand extends ScheduleDeviceDiscoveryCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:device:poller {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Set the finally handler
     */
    public static function getFinallyHandler() {
        return function (Batch $batch, ?Throwable $e) {
            static::handleFinally($batch, $e);
        };
    }

    public function getJob(...$args) {
        return new PollDevice(...$args);
    }

    public static function handleFinally(Batch $batch)
    {
        dump("polling finally");
        $time_total = $batch->createdAt->diffInSeconds($batch->finishedAt);
        $devices_polled = Cache::get("librenms-poller:" . $batch->id);
        //$devices_polled = $batch->totalJobs - $batch->failedJobs;


        printf("INFO POLLER: polled %d/%d devices in %s seconds\n", $devices_polled, $batch->totalJobs, $time_total);

        /*
        $poller_id = (int) str_replace("poller_", null, $batch->options["queue"]);
        if($poller_id > 0) {
            $group_name = PollerGroup::find(
                $poller_id,
                ['group_name']
            )->group_name;
        }
        */

        Poller::updateOrCreate(
            ['poller_name' => $batch->name],
            [
                'last_polled' => now(),
                'devices' => $batch->totalJobs,
                'time_taken' => $time_total,
            ]
        );


        // TODO: FIX ME
        if($batch->failedJobs > 0) {

        }

        // TODO: FIX ME
        if ($devices_polled < $batch->totalJobs) {

        }

        if ($time_total > Config::get('schedule.polling')) {
            printf("WARNING: the process took more than %s seconds to finish, you need more workers!", $time_total);
        }
    }
}
