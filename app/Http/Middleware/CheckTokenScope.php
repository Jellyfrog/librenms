<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenScope
{
    /**
     * Handle an incoming request.
     *
     * Check that the Sanctum token has the "read" scope.
     * Web session users are not affected by this middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if not authenticated or not using a Sanctum token (e.g. web session)
        if (! $user || ! $user->currentAccessToken() instanceof PersonalAccessToken) {
            return $next($request);
        }

        if (! $user->tokenCan('read')) {
            return response()->json([
                'message' => 'Token does not have the required scope: read',
            ], 403);
        }

        return $next($request);
    }
}
