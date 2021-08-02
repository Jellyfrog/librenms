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
        // TODO: Do it lazy way; https://freek.dev/1734-how-to-group-queued-jobs-using-laravel-8s-new-batch-class
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
        )
        ->catch(function (Batch $batch, ?Throwable $e) {
            self::handleCatch($batch, $e);
        })
        ->finally(function (Batch $batch) {
            self::handleFinally($batch);
        })
        ->allowFailures()
        ->onQueue('poller_' . $poller_group)
        ->dispatch();

        dump($batch);

        echo("INFO: starting the poller at $batch->createdAt");
    }

    private static function handleCatch(Batch $batch, $e)
    {
        //TODO: Log?
    }

    private static function handleFinally(Batch $batch)
    {
        $time_total = $batch->createdAt->diffInSeconds($batch->finishedAt);
        $devices_polled = $batch->totalJobs - $batch->failedJobs;

        printf("INFO: polled %s devices in %s seconds\n", $devices_polled, $time_total);
        dump($batch->id);

dump($batch->toArray());
dump($batch->name);
dump($batch->totalJobs);
dump($batch->pendingJobs);
dump($batch->failedJobs);
dump($batch->processedJobs());
dump($batch->progress());
dump($batch->finished());
dump($batch->cancelled());

        $group_name = 'Default';
        $poller_id = str_replace("poller_", null, $batch->options["queue"]);

        if($poller_id > 0) {
            $group_name = PollerGroup::find(
                $poller_id,
                ['group_name']
            )->group_name;
        }

        Poller::updateOrCreate(
            ['poller_name' => $group_name],
            [
                'last_polled' => DB::raw('now()'),
                'devices' => $batch->totalJobs,
                'time_taken' => $time_total,
            ]
        );

        if ($time_total > Config::get('rrd.step')) {
            printf("WARNING: the process took more than %s seconds to finish, you need more workers!", $time_total);
        }
    }
}
