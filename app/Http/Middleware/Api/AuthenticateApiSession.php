<?php

namespace App\Http\Middleware\Api;

use App\Http\Middleware\VerifyTwoFactor;
use App\Http\Middleware\VerifyUserEnabled;
use Closure;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a user who is logged in to the web interface use the API from their
 * browser. Must run before auth:sanctum, which accepts the web session.
 *
 * API routes are stateless, so the session is only started for a request that
 * carries the session cookie and no token. Such a request gets the same checks
 * as a web page: CSRF on writes, the user must be enabled and must have
 * completed two-factor verification.
 */
class AuthenticateApiSession
{
    private const SESSION_MIDDLEWARE = [
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ValidateCsrfToken::class,
        AuthenticateSession::class,
        VerifyUserEnabled::class,
        VerifyTwoFactor::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $usesSession = $request->bearerToken() === null && $request->hasCookie(config('session.cookie'));

        return (new Pipeline(app()))
            ->send($request)
            ->through($usesSession ? self::SESSION_MIDDLEWARE : [])
            ->then(fn (Request $request) => $next($request));
    }
}
