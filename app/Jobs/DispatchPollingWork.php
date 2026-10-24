<?php

namespace App\Jobs;

use App\Facades\LibrenmsConfig;
use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use LibreNMS\Util\ModuleList;

class DispatchPollingWork implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** seconds between dispatcher runs, keep in sync with the schedule in routes/console.php */
    public const INTERVAL = 10;
    /** work due before the next dispatcher run (plus slack for a late run) is dispatched now with a delay */
    private const LOOKAHEAD = self::INTERVAL + 5;
    /**
     * Start each run a few seconds early so worker pickup latency (queue:work --sleep defaults to 3s)
     * cannot stretch the interval past the frequency and skip a window. Start times slowly drift earlier,
     * which spreads the load around the window.
     */
    private const EARLY_START = 3;

    public int $uniqueFor = 60; // only one waiting in the queue
    private string $pollingQueueConnection;
    private int $poll_interval;
    private int $discovery_interval;

    /**
     * @param  bool|null  $poll  force polling dispatch on or off, null uses schedule_type.poller
     * @param  bool|null  $discover  force discovery dispatch on or off, null uses schedule_type.discovery
     */
    public function __construct(
        private ?bool $poll = null,
        private ?bool $discover = null,
    ) {
        $this->poll ??= LibrenmsConfig::get('schedule_type.poller') == 'scheduler';
        $this->discover ??= LibrenmsConfig::get('schedule_type.discovery') == 'scheduler';

        $this->poll_interval = self::pollInterval();
        $this->discovery_interval = LibrenmsConfig::get('service_discovery_frequency', 21600) - self::EARLY_START;

        $default = \config('queue.default');
        // database minimum driver, redis recommended
        $this->pollingQueueConnection = $default == 'sync' ? 'database' : $default;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->poll && ! $this->discover) {
            return;
        }

        // based on the python dispatcher (LibreNMS/service.py)
        $devices = Device::query()
            ->select(['device_id', 'poller_group', 'status', 'last_polled', 'last_polled_timetaken', 'last_discovered', 'last_discovered_timetaken'])
            ->where('disabled', 0)
            ->orderByRaw('last_discovered IS NULL DESC')
            ->orderByDesc('last_polled_timetaken')
            ->get();

        $modules = ModuleList::fromUserOverrides([]);
        $discovered = [];
        $polled = [];
        $now = time();

        foreach ($devices as $device) {
            // seconds until due, measured from the start of the previous run (last_* is recorded at the end of the run)
            $poll_wait = $device->last_polled ? $device->last_polled->getTimestamp() - (int) $device->last_polled_timetaken + $this->poll_interval - $now : null;
            $discovery_wait = $device->last_discovered ? $device->last_discovered->getTimestamp() - (int) $device->last_discovered_timetaken + $this->discovery_interval - $now : null;
            // new devices are not polled until discovered, a rediscover request (last_discovered null) keeps polling
            $new_device = $device->last_discovered === null && $device->last_polled === null;

            $poll = ! $new_device && ($poll_wait === null || $poll_wait <= self::LOOKAHEAD);
            // polling retries down devices, only new devices rely on discovery for that
            $discover = $device->status ? ($discovery_wait === null || $discovery_wait <= self::LOOKAHEAD) : $new_device;

            if ($this->discover && $discover) {
                DiscoverDevice::dispatch($device->device_id, $modules)
                    ->onConnection($this->pollingQueueConnection)
                    ->onQueue($device->poller_group ? "discovery-$device->poller_group" : 'discovery')
                    ->delay($this->delay($discovery_wait));
                $discovered[] = $device->device_id;
            }

            if ($this->poll && $poll) {
                PollDevice::dispatch($device->device_id, $modules)
                    ->onConnection($this->pollingQueueConnection)
                    ->onQueue($device->poller_group ? "poll-$device->poller_group" : 'poll')
                    ->delay($this->delay($poll_wait));
                $polled[] = $device->device_id;
            }
        }

        Log::debug('Due for discovery: ' . implode(',', $discovered));
        Log::debug('Due for polling: ' . implode(',', $polled));
    }

    /**
     * Seconds from the start of one poll to the start of the next
     */
    public static function pollInterval(): int
    {
        return LibrenmsConfig::get('service_poller_frequency', LibrenmsConfig::get('rrd.step', 300)) - self::EARLY_START;
    }

    /**
     * Retry delays for a down device, using 90% of the poll interval.
     * The rest is left for the attempts themselves and the next dispatch.
     *
     * @return array<int, int>
     */
    public static function downRetryBackoff(): array
    {
        $interval = self::pollInterval();

        return array_map(fn (float $fraction): int => (int) round($interval * $fraction), [0.1, 0.3, 0.5]);
    }

    /**
     * Hold work that is not due yet until its due time, so start times are not rounded up to the next dispatcher run
     */
    private function delay(mixed $wait): int
    {
        return max(0, (int) ceil((float) $wait));
    }
}
