<?php

namespace App\Observers;

use App\Models\Eventlog;
use App\Models\Package;
use Log;

class PackageObserver
{
    /**
     * Handle the Package "created" event.
     */
    public function created(Package $package): void
    {
        Eventlog::log('Package installed: ' . $package, $package->device_id, 'package', 3);
        Log::info("+ $package");
    }

    /**
     * Handle the Package "updated" event.
     */
    public function updated(Package $package): void
    {
        if ($package->getOriginal('version') !== $package->version || $package->getOriginal('build') !== $package->build) {
            $message = $package . ' from ' . $package->getOriginal('version') . ($package->getOriginal('build') ? '-' . $package->getOriginal('build') : '');
            Eventlog::log('Package updated: ' . $message, $package->device_id, 'package', 3);
            Log::info("u $message");
        }
    }

    /**
     * Handle the Package "deleted" event.
     */
    public function deleted(Package $package): void
    {
        Eventlog::log('Package removed: ' . $package, $package->device_id, 'package', 3);
        Log::info("- $package");
    }

    /**
     * Handle the Package "restored" event.
     */
    public function restored(Package $package): void
    {
        //
    }

    /**
     * Handle the Package "force deleted" event.
     */
    public function forceDeleted(Package $package): void
    {
        //
    }
}
