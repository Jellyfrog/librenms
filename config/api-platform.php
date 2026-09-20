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
        // DefaultAcceptJson goes first so that the 404 from a disabled v2 and
        // the 401 from a missing token are both JSON. It only fills in an
        // Accept header when the client did not send one; EnforceJson must not
        // be used here, it overwrites the header and would break content
        // negotiation. EnsureApiEnabled keeps the beta API behind the
        // api.v2.enabled setting.
        'middleware' => [
            \App\Http\Middleware\DefaultAcceptJson::class,
            \App\Http\Middleware\EnsureApiEnabled::class . ':v2',
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

    // No 'html': the bundled documentation UIs load their CSS and JS from
    // /vendor/api-platform, and nothing publishes those into html/. Point a
    // Swagger UI of your own at the OpenAPI document instead.
    'docs_formats' => [
        'jsonld' => ['application/ld+json'],
        'jsonapi' => ['application/vnd.api+json'],
        'jsonopenapi' => ['application/vnd.openapi+json'],
    ],

    'defaults' => [
        'pagination_enabled' => true,
        'pagination_partial' => false,
        'pagination_client_enabled' => false,
        'pagination_client_items_per_page' => true,
        'pagination_client_partial' => false,
        'pagination_items_per_page' => 50,
        'pagination_maximum_items_per_page' => 500,
        'route_prefix' => '/api/v2',
        'middleware' => [],
    ],

    // Null keeps the database column names (hostname, sysName, location_id,
    // ...) as the API field names, matching the rest of the LibreNMS API.
    'name_converter' => null,

    'swagger_ui' => [
        'enabled' => false,
    ],

    'redoc' => [
        'enabled' => false,
    ],

    'scalar' => [
        'enabled' => false,
    ],

    // LibreNMS does not expose an MCP endpoint.
    'mcp' => [
        'enabled' => false,
    ],
];
