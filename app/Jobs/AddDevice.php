<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Device;
use LibreNMS\Config;
use Symfony\Component\Process\Process;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Jobs\Middleware\MaxTries;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Log;

class AddDevice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $device;
    private $debug;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware()
    {

    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Device $device, bool $debug = false)
    {
        $this->device = $device;
        $this->debug = $debug;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        //dump($this->job);

        sleep(1);
        return;

     
    }

 
}
