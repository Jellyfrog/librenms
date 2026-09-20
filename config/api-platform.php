<?php

/*
 * API Platform configuration for the LibreNMS v2 API.
 *
 * Only the keys LibreNMS overrides are listed. The package merges this on top
 * of its own config (a shallow merge, so a key listed here replaces the
 * vendor's whole value for it), and anything omitted keeps the vendor default.
 *
 * @see vendor/api-platform/laravel/config/api-platform.php
 * @see https://api-platform.com/docs/laravel/
 */

declare(strict_types=1);

return [
    'title' => 'LibreNMS',
    'description' => 'LibreNMS network monitoring API',
    'show_webby' => false,

    'routes' => [
        'domain' => null,
        // Applied to every API Platform route, including the documentation.
        // EnsureApiEnabled keeps the beta API behind the api.v2.enabled
        // setting. EnforceJson must not be used here, it overwrites the Accept
        // header and would break content negotiation; DefaultAcceptJson only
        // fills one in when the client did not send one.
        'middleware' => [
            \App\Http\Middleware\EnsureApiEnabled::class . ':v2',
            \App\Http\Middleware\DefaultAcceptJson::class,
            'auth:sanctum',
        ],
    ],

    // Only Eloquent models carry #[ApiResource] today. Add app/ApiResource
    // here if plain resource classes are introduced later.
    //
    // API Platform builds resource metadata from the database schema, so
    // scanning these paths needs a working connection. App\Providers\
    // ApiPlatformServiceProvider only registers API Platform for requests and
    // commands that need the v2 routes, keeping LibreNMS bootable without a
    // database.
    'resources' => [
        app_path('Models'),
    ],

    'formats' => [
        'jsonld' => ['application/ld+json'],
        'json' => ['application/json'],
        'jsonapi' => ['application/vnd.api+json'],
    ],

    'docs_formats' => [
        'jsonld' => ['application/ld+json'],
        'jsonapi' => ['application/vnd.api+json'],
        'jsonopenapi' => ['application/vnd.openapi+json'],
        'html' => ['text/html'],
    ],

    'defaults' => [
        'pagination_enabled' => true,
        'pagination_partial' => false,
        'pagination_client_enabled' => false,
        'pagination_client_items_per_page' => true,
        // Let a client paging through a large collection pass partial=true to
        // skip the COUNT(*) that the totals need.
        'pagination_client_partial' => true,
        'pagination_items_per_page' => 50,
        'pagination_maximum_items_per_page' => 500,
        'route_prefix' => '/api/v2',
        'middleware' => [],
    ],

    // Null keeps the database column names (hostname, sysName, location_id,
    // ...) as the API field names, matching the rest of the LibreNMS API.
    'name_converter' => null,

    'swagger_ui' => [
        'enabled' => true,
        'http_auth' => [
            'API token' => [
                'scheme' => 'bearer',
            ],
        ],
    ],

    // LibreNMS does not expose an MCP endpoint.
    'mcp' => [
        'enabled' => false,
    ],
];
