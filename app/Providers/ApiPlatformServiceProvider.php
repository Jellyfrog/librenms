<?php

namespace App\Providers;

use ApiPlatform\Laravel\ApiPlatformDeferredProvider;
use ApiPlatform\Laravel\ApiPlatformProvider;
use ApiPlatform\Laravel\Eloquent\ApiPlatformEventProvider;
use App\Api\UnconvertedNameConverter;
use Illuminate\Support\ServiceProvider;
use LibreNMS\Util\EnvHelper;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Registers API Platform (the v2 API) only where it is needed.
 *
 * API Platform registers a route per resource operation while the application
 * boots, and building that metadata reflects every class under app/ and reads
 * the database schema of every #[ApiResource] model. That is far too much to
 * pay on a poller run or a web page, and far too early to depend on a
 * database: a checkout without one, the installer, and the test suite would
 * all fail to boot.
 *
 * So the package is excluded from auto-discovery (composer.json
 * extra.laravel.dont-discover) and registered here instead, only for requests
 * to /api/v2, for the commands that build the route cache, and for tests run
 * with DBTEST=1.
 */
class ApiPlatformServiceProvider extends ServiceProvider
{
    /**
     * Console commands that need to see every v2 route.
     */
    private const ROUTE_COMMANDS = [
        'optimize',
        'route:cache',
        'route:list',
        'api-platform:metadata:dump',
        'api-platform:openapi:export',
    ];

    public function register(): void
    {
        if (! $this->needsApiPlatform()) {
            return;
        }

        // Keep api-platform's query parameter metadata on the real column
        // names, see UnconvertedNameConverter.
        $this->app->bind(CamelCaseToSnakeCaseNameConverter::class, UnconvertedNameConverter::class);

        $this->app->register(ApiPlatformProvider::class);
        $this->app->register(ApiPlatformDeferredProvider::class);
        $this->app->register(ApiPlatformEventProvider::class);
    }

    private function needsApiPlatform(): bool
    {
        // Nothing to serve from a checkout that has no database yet.
        if (! EnvHelper::isInstalled()) {
            return false;
        }

        if ($this->app->runningUnitTests()) {
            return (bool) getenv('DBTEST');
        }

        if ($this->app->runningInConsole()) {
            return $this->app->runningConsoleCommand(...self::ROUTE_COMMANDS);
        }

        return $this->app['request']->is('api/v2', 'api/v2/*');
    }
}
