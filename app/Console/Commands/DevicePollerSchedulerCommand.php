<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Poller;
use App\Models\PollerGroup;
use App\Jobs\PollDevice;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;
use LibreNMS\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


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
            ->shouldBePolled()
            ->orderByDesc('last_polled_timetaken')
            ->with('pollerGroup')
            ->get()
            ->groupBy('poller_group')
            ->each(function ($poller_devices, $poller_group) {
                $this->dispatchJobs($poller_group, $poller_devices);
            });

        return 0;
    }

    private function dispatchJobs($poller_group_id, $poller_devices)
    {
        $poller_group_name =
            optional($poller_devices[0]->pollerGroup)->group_name
            ?? 'Default';

        $batch = Bus::batch(
           $poller_devices->map(function ($device) {
                return new PollDevice($device, $this->option('debug'));
            })
        )
        ->catch(function (Batch $batch, ?Throwable $e) {
            self::handleCatch($batch, $e);
        })
        ->finally(function (Batch $batch) {
            self::handleFinally($batch);
        })
        ->allowFailures()
        ->onConnection('polling')
        ->onQueue('poller_' . $poller_group_id)
        ->name($poller_group_name)
        ->dispatch();

        // Setup key for counting successful jobb with a long enough TTL
        Cache::put("librenms-poller:" . $batch->id, 0, Config::get('rrd.step') * 10);

        dump($batch);

        dump("INFO: Starting the poller for $batch->name at $batch->createdAt");
    }

    private static function handleCatch(Batch $batch, $e)
    {
        //TODO: Log?

        dump("------------------HANDLE CATCH----------------");
        dump($e->getMessage());
        dump("------------------!HANDLE CATCH----------------");
    }

    private static function handleFinally(Batch $batch)
    {
        $time_total = $batch->createdAt->diffInSeconds($batch->finishedAt);
        $devices_polled = Cache::get("librenms-poller:" . $batch->id);
        //$devices_polled = $batch->totalJobs - $batch->failedJobs;


        printf("INFO: polled %d/%d devices in %s seconds\n", $devices_polled, $batch->totalJobs, $time_total);

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

        if ($time_total > Config::get('rrd.step')) {
            printf("WARNING: the process took more than %s seconds to finish, you need more workers!", $time_total);
        }
    }
}
