<?php

namespace App\Http\Middleware;

use App\Facades\LibrenmsConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hide an opt-in API version until it is switched on. 404 rather than 403, so
 * a disabled version is indistinguishable from one that does not exist.
 */
class EnsureApiEnabled
{
    public function handle(Request $request, Closure $next, string $version): Response
    {
        if (! LibrenmsConfig::get("api.$version.enabled", false)) {
            abort(404);
        }

        return $next($request);
    }
}
