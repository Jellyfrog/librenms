<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Keeps the API Platform (v2) resource scan off the hot path.
 *
 * API Platform registers a route per resource operation, and building that
 * metadata introspects the database schema of every #[ApiResource] model. That
 * happens while routes are being registered, which is far too early to depend
 * on a database: a checkout without a database, the installer, and the test
 * suite would all fail to boot. So the resource paths are only handed to API
 * Platform when the full v2 route table is actually needed.
 *
 * Once the routes are cached (`artisan optimize`) none of this runs at all.
 */
class ApiPlatformServiceProvider extends ServiceProvider
{
    /**
     * Console commands that need to see every v2 route.
     */
    private const ROUTE_COMMANDS = ['optimize', 'route:cache', 'route:list'];

    public function register(): void
    {
        if (! $this->needsResources()) {
            config(['api-platform.resources' => []]);
        }
    }

    private function needsResources(): bool
    {
        // No .env (or mid-install) means there is no database to introspect.
        if (config('librenms.install') || ! file_exists(base_path('.env'))) {
            return false;
        }

        if ($this->app->runningUnitTests()) {
            return (bool) getenv('DBTEST');
        }

        if ($this->app->runningInConsole()) {
            $command = $_SERVER['argv'][1] ?? '';

            return in_array($command, self::ROUTE_COMMANDS, true)
                || str_starts_with($command, 'api-platform:');
        }

        return $this->app->bound('request')
            && $this->app['request']->is('api/v2', 'api/v2/*');
    }
}
