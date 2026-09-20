<?php

/*
 * API Platform configuration for the LibreNMS v2 API.
 *
 * The package merges this on top of its own config, so anything omitted keeps
 * the vendor default. The merge is shallow, so a key listed here replaces the
 * vendor's whole value for it - which is why some entries below repeat a
 * vendor default: a sibling in the same top-level array is overridden.
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
        // Applied to every API Platform route. DefaultAcceptJson goes first so
        // that the 401 from a missing token is JSON rather than a redirect to
        // the login page (see that class for why it is not EnforceJson). It is
        // not in the middleware priority list in bootstrap/app.php, so this
        // order is the one that applies.
        'middleware' => [
            \App\Http\Middleware\DefaultAcceptJson::class,
            'auth:sanctum',
        ],
    ],

    // Only Eloquent models carry #[ApiResource] today. Add app/ApiResource
    // here if plain resource classes are introduced later.
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
