<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ApiPlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->isDatabaseAvailable()) {
            return;
        }

        $this->app->register(\ApiPlatform\Laravel\ApiPlatformProvider::class);
        $this->app->register(\ApiPlatform\Laravel\ApiPlatformDeferredProvider::class);
        $this->app->register(\ApiPlatform\Laravel\Eloquent\ApiPlatformEventProvider::class);
    }

    private function isDatabaseAvailable(): bool
    {
        try {
            $this->app->make('db')->connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
