<?php

namespace App\Observers;

use App\Models\Service;

class ServiceObserver
{
    /**
     * Handle the service "created" event.
     */
    public function created(Service $service): void
    {
        //
    }

    /**
     * Handle the service "updated" event.
     */
    public function updated(Service $service): void
    {
        //
    }

    /**
     * Handle the service "deleted" event.
     */
    public function deleted(Service $service): void
    {
        //
    }

    /**
     * Handle the service "restored" event.
     */
    public function restored(Service $service): void
    {
        //
    }

    /**
     * Handle the service "force deleted" event.
     */
    public function forceDeleted(Service $service): void
    {
        //
    }
}
