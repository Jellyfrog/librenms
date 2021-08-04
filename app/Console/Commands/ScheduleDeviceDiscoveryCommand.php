<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Poller;
use App\Jobs\PollDevice;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;
use LibreNMS\Config;
use Illuminate\Support\Facades\Cache;

class ScheduleDeviceDiscoveryCommand extends Command
{
    protected $time_start;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:device:discovery {--debug}';

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
        // Save what interval this job belongs to
        $this->time_start = now()->startOfMinute();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        static::getDeviceQuery()
            ->get()
            ->groupBy('poller_group')
            ->each(function ($poller_devices, $poller_group) {
                $this->dispatchJobs($poller_group, $poller_devices);
            });

        return 0;
    }

    public static function getDeviceQuery() {
        return Device::select(['device_id', 'poller_group'])
            ->shouldBeDiscovered()
            ->orderByDesc('last_polled_timetaken')
            ->with('pollerGroup');
    }

    public function dispatchJobs($poller_group_id, $poller_devices)
    {
        $poller_group_name =
            optional($poller_devices[0]->pollerGroup)->group_name
            ?? 'Default';

        $batch = Bus::batch(
           $poller_devices->map(function ($device) {
                return $this->getJob($device, $this->time_start, $this->option('debug'));
            })
        )
        ->allowFailures()
        ->onConnection('polling')
        ->onQueue('poller_' . $poller_group_id)
        ->name($poller_group_name)
        ->catch(static::getCatchHandler())
        ->finally(static::getFinallyHandler())
        ->dispatch();

        // Setup key for counting successful jobb with a long enough TTL
        Cache::put("librenms-poller:" . $batch->id, 0, now()->addMinutes(Config::get('schedule.polling') * 10));

        dump($batch);

        dump("INFO: Starting the poller for $batch->name at $batch->createdAt");
    }

    /**
     * Set the exception catch handler
     */
    public static function getCatchHandler() {
        return function (Batch $batch, ?Throwable $e) {
            static::handleCatch($batch, $e);
        };
    }

    /**
     * Set the finally handler
     */
    public static function getFinallyHandler() {
        return function (Batch $batch, ?Throwable $e) {
            static::handleFinally($batch, $e);
        };
    }

    public function getJob(...$args) {
        return new DiscoverDevice(...$args);
    }

    public static function handleCatch(Batch $batch, $e)
    {
        Log::error($e);
    }

    public static function handleFinally(Batch $batch)
    {
        $time_total = $batch->createdAt->diffInSeconds($batch->finishedAt);
        $devices_polled = Cache::get("librenms-poller:" . $batch->id);
        //$devices_polled = $batch->totalJobs - $batch->failedJobs;


        printf("INFO DISCOVERY: Discovered %d/%d devices in %s seconds\n", $devices_polled, $batch->totalJobs, $time_total);


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
