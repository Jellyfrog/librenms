<?php

use App\Http\Controllers\Api\V1\PortController;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ActionRegistrar;
use LaravelJsonApi\Laravel\Routing\Relationships;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

JsonApiRoute::server('v1')->prefix('v1')->resources(function (ResourceRegistrar $server) {
    $server->resource('devices', JsonApiController::class)
        ->readOnly()
        ->relationships(function (Relationships $relations) {
            $relations->hasMany('ports')->readOnly();
            $relations->hasMany('vlans')->readOnly();
            $relations->hasMany('bgp-peers')->readOnly();
            $relations->hasOne('location')->readOnly();
        });

    $server->resource('ports', PortController::class)
        ->readOnly()
        ->relationships(function (Relationships $relations) {
            $relations->hasOne('device')->readOnly();
        })->actions('-actions', function (ActionRegistrar $actions) {
            $actions->withId()->get('graph');
        });

    $server->resource('locations', JsonApiController::class)
        ->readOnly()
        ->relationships(function (Relationships $relations) {
            $relations->hasMany('devices')->readOnly();
        });

    $server->resource('vlans', JsonApiController::class)
        ->readOnly()
        ->relationships(function (Relationships $relations) {
            $relations->hasOne('device')->readOnly();
        });

    $server->resource('bgp-peers', JsonApiController::class)
        ->readOnly()
        ->relationships(function (Relationships $relations) {
            $relations->hasOne('device')->readOnly();
        });
});
