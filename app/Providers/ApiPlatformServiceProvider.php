<?php

namespace App\Providers;

use ApiPlatform\Laravel\ApiPlatformDeferredProvider;
use ApiPlatform\Laravel\ApiPlatformProvider;
use ApiPlatform\Laravel\Eloquent\ApiPlatformEventProvider;
use Illuminate\Routing\Events\Routing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Loads API Platform (the v1 API) only where it is used: it reflects every
 * class under app/ when it registers, which is too much to pay on each poller
 * run. The package is excluded from auto-discovery in composer.json.
 */
class ApiPlatformServiceProvider extends ServiceProvider
{
    /**
     * Not the commands that build the route cache: API Platform reads the
     * database schema of its resources, and that cache has to build without one.
     */
    private const COMMANDS = ['route:list', 'api:openapi:export', 'api-platform:install'];

    /** Set by tests of the API before the application is created. */
    public static bool $forTesting = false;

    public function register(): void
    {
        // Always: a config cache built without API Platform still needs its defaults.
        $this->mergeConfigFrom(base_path('vendor/api-platform/laravel/config/api-platform.php'), 'api-platform');

        if ($this->isNeeded()) {
            $this->app->register(ApiPlatformProvider::class);
            $this->app->register(ApiPlatformDeferredProvider::class);
            $this->app->register(ApiPlatformEventProvider::class);
        }
    }

    public function boot(): void
    {
        // Just before a request is matched: cached routes are loaded last and replace the route list.
        Event::listen(Routing::class, fn () => $this->loadMissingRoutes());
    }

    private function isNeeded(): bool
    {
        if (self::$forTesting) {
            return true;
        }

        if ($this->app->runningInConsole()) {
            return $this->app->runningConsoleCommand(...self::COMMANDS);
        }

        $prefix = trim((string) config('api-platform.defaults.route_prefix'), '/');

        return $this->app->make('request')->is($prefix, $prefix . '/*');
    }

    /**
     * The route cache has no API routes in it, and API Platform does not load
     * its routes when a cache exists. Add them for this request.
     */
    private function loadMissingRoutes(): void
    {
        if (! $this->app->providerIsLoaded(ApiPlatformProvider::class) || ! $this->app->routesAreCached() || Route::has('api_entrypoint')) {
            return;
        }

        $routes = Route::getRoutes();
        $cached = count($routes->getRoutes());

        require base_path('vendor/api-platform/laravel/routes/api.php');

        // A cached route list only records a route's name when the route is
        // added, and routes are named afterwards. Add the new ones again.
        foreach (array_slice($routes->getRoutes(), $cached) as $route) {
            $routes->add($route);
        }
    }
}
