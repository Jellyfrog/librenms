<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Redis;

class MaxTries
{
    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     * 
     * Built in check only supports checking either retryUntil or tries
     * https://github.com/laravel/framework/blob/eaacad4ad2a8e4cecdb1d98a344e1d16206415f4/src/Illuminate/Queue/Worker.php#L522
     *
     * @param  mixed  $job
     * @param  callable  $next
     * @return mixed
     */
    public function handle($job, $next)
    {
	dump("maxtries");
	if($job->tries > 0 && $job->attempts() >= $job->tries) {
		dump("middlewere: should fail here");
		$job->fail();
		return;
	}
       $next($job);
    }
}
