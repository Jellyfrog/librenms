<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Device;
use LibreNMS\Alert\AlertRules;
use LibreNMS\Data\Store\Datastore;
use App\Facades\DeviceCache;
use App\Models\DeviceGroup;

class Poller implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    private $device;
    private $i;
    private $module_override;
    private $options;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    // TODO: should we set this? will kill the worker to prevent running over time, might be good for stuck jobs?
    // needs to be set to whatever polling frequency we have, or slightly less?
    //public $timeout = 300;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        // TODO: This cannot be used alone, because it will override $this->tries,
        // so it will just re-run the job as many times as it can if it fails.
     //   return now()->addSeconds(300);
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Device $device, $i = 0, $module_override = null)
    {
        $this->device = $device;
        $this->i = $i;
        $this->module_override = $module_override;

        // TODO:
        // $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        dump("poller job: #" . $this->i, $this->device->device_id);

        // TODO: Is this actually needed?
        $datastore = Datastore::init($this->options);

        DeviceCache::setPrimary($this->device->device_id);

        // FIXME
        if ($this->device->os_group == 'cisco') {
            $this->device->vrf_lite_cisco = $this->device->vrfLites;
        } else {
            $this->device->vrf_lite_cisco = '';
        }

        if (! \poll_device($this->device, $this->module_override)) {
            $device_unreachable = true;
        }

        // Update device_groups
        echo "### Start Device Groups ###\n";
        $dg_start = microtime(true);
        $group_changes = DeviceGroup::updateGroupsFor($this->device->device_id);
        d_echo('Groups Added: ' . implode(',', $group_changes['attached']) . PHP_EOL);
        d_echo('Groups Removed: ' . implode(',', $group_changes['detached']) . PHP_EOL);
        echo '### End Device Groups, runtime: ' . round(microtime(true) - $dg_start, 4) . "s ### \n\n";

        echo "#### Start Alerts ####\n";
        $rules = new AlertRules();
        $rules->runRules($this->device->device_id);
        echo "#### End Alerts ####\r\n";

        Datastore::terminate();
    }


}
