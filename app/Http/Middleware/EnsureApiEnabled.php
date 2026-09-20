<?php

namespace App\Http\Middleware;

use App\Facades\LibrenmsConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hide an opt-in API version until it is switched on.
 *
 * Used as `EnsureApiEnabled::class . ':v1'`, which gates on api.v1.enabled.
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
