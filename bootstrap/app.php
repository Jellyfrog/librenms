<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Register plugin packages installed in the plugin composer root (storage/plugins).
// This has to happen before the application is created so that plugin classes are
// available to every entry point: the web UI, artisan/lnms and the cron pollers.
// The plugin autoloader appends itself, so the core autoloader always wins a lookup.
// An interrupted composer run in the plugin root would otherwise fatal every entry point,
// lnms included, leaving no way to repair it. Skipping the plugins keeps that door open.
// Some failures, such as a function declared twice, are fatal and cannot be caught, so the
// commands that manage plugins never load them.
if (! App\Plugins\PluginRoot::skipsPlugins() && is_file($pluginAutoload = __DIR__ . '/../storage/plugins/vendor/autoload.php')) {
    try {
        require_once $pluginAutoload;
    } catch (Throwable $e) {
        error_log('Failed to load plugin packages, run lnms plugin:sync: ' . $e->getMessage());
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->registered(function ($app) {
        $app->usePublicPath(path: realpath(base_path('html')));
    })
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            '/auth/*/callback',
        ]);

        $middleware->authenticateSessions();

        $middleware->web([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\LegacyExternalAuth::class,
            \App\Http\Middleware\VerifyUserEnabled::class,
            \App\Http\Middleware\VerifyTwoFactor::class,
            \App\Http\Middleware\LoadUserPreferences::class,
        ]);

        $middleware->api([
            \App\Http\Middleware\EnforceJson::class,  // prevent redirect to login page
            'auth:sanctum',
        ]);

        $middleware->replace(\Illuminate\Http\Middleware\TrustProxies::class, \App\Http\Middleware\TrustProxies::class);
        $middleware->replace(\Illuminate\Http\Middleware\HandleCors::class, \App\Http\Middleware\HandleCors::class);

        $middleware->alias([
            'deny-demo' => \App\Http\Middleware\DenyDemoUser::class,
            'saved-filter' => \App\Http\Middleware\MergeSavedFilter::class,
        ]);

        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\EnforceJson::class, // must be before auth
            \App\Http\Middleware\LegacyExternalAuth::class, // must be before auth
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        new \App\Exceptions\ErrorReporting($exceptions);

        $exceptions->map(
            \Binaryk\LaravelRestify\Exceptions\RepositoryNotFoundException::class,
            fn (\Binaryk\LaravelRestify\Exceptions\RepositoryNotFoundException $e) => new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException($e->getMessage(), $e),
        );
    })->create();
