<?php

namespace App\Providers;

use ApiPlatform\Laravel\ApiPlatformDeferredProvider;
use ApiPlatform\Laravel\ApiPlatformProvider;
use ApiPlatform\Laravel\Eloquent\ApiPlatformEventProvider;
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
 * to an enabled /api/v2 and for the commands that build the route cache.
 * Tests opt in for themselves with $registerForTesting, see ApiV2TestCase.
 */
class ApiPlatformServiceProvider extends ServiceProvider
{
    /**
     * Set by the v2 tests before the application is created, so this provider
     * does not have to know anything about the test suite. Without it the test
     * run counts as console and API Platform is skipped, which is what keeps
     * it off the other ~100 database tests.
     */
    public static bool $registerForTesting = false;

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

        // config/api-platform.php sets name_converter to null, meaning the
        // database column names are the API field names. api-platform honours
        // that when serializing, but builds its query parameter metadata with
        // a CamelCaseToSnakeCaseNameConverter regardless, which would rewrite
        // a parameter's property to snake_case and then query that: a filter
        // or sort on a column with a capital in it (sysName, ifOperStatus)
        // would look for sys_name and silently match nothing. An empty
        // attribute list makes the converter leave every name alone.
        $this->app->bind(
            CamelCaseToSnakeCaseNameConverter::class,
            fn () => new CamelCaseToSnakeCaseNameConverter(attributes: []),
        );

        $this->app->register(ApiPlatformProvider::class);
        $this->app->register(ApiPlatformDeferredProvider::class);
        $this->app->register(ApiPlatformEventProvider::class);
    }

    private function needsApiPlatform(): bool
    {
        if ($this->app->runningUnitTests()) {
            return self::$registerForTesting;
        }

        if ($this->app->runningInConsole()) {
            return $this->app->runningConsoleCommand(...self::ROUTE_COMMANDS)
                && EnvHelper::isInstalled();
        }

        // Nothing to serve from a checkout that has no database yet.
        if (! EnvHelper::isInstalled()) {
            return false;
        }

        $prefix = trim((string) config('api-platform.defaults.route_prefix'), '/');

        // Deliberately not also checking api.v2.enabled, which would save
        // registering all of this just for EnsureApiEnabled to 404 a disabled
        // v2: that setting lives in the database behind the config cache, and
        // resolving it this early leaves the deferred cache provider unusable
        // for the rest of the request.
        return $this->app['request']->is($prefix, $prefix . '/*');
    }
}
