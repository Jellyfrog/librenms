<?php

/*
|--------------------------------------------------------------------------
| API v2 Custom Routes
|--------------------------------------------------------------------------
|
| Custom endpoints for the v2 API that are not covered by API Platform's
| automatic resource routing (e.g., RRD graph/data endpoints).
|
| API Platform handles CRUD for Eloquent models automatically.
| These routes handle specialized functionality.
|
*/

use App\Http\Controllers\Api\V2\RrdController;
use Illuminate\Support\Facades\Route;

Route::prefix('v2')->group(function (): void {
    // Health check
    Route::get('ping', fn () => response()->json(['message' => 'pong', 'api_version' => 'v2']))->name('api.v2.ping');

    // RRD endpoints
    Route::prefix('rrd')->group(function (): void {
        Route::get('graph', [RrdController::class, 'graph'])->name('api.v2.rrd.graph');
        Route::get('data', [RrdController::class, 'data'])->name('api.v2.rrd.data');
        Route::get('files/{deviceId}', [RrdController::class, 'files'])
            ->where('deviceId', '[0-9]+')
            ->name('api.v2.rrd.files');
        Route::get('graph-types/{deviceId}', [RrdController::class, 'graphTypes'])
            ->where('deviceId', '[0-9]+')
            ->name('api.v2.rrd.graph-types');
    });
});
