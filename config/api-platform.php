<?php

/*
 * API Platform configuration for the LibreNMS v2 API.
 *
 * Published from api-platform/laravel. Only the values LibreNMS changes from
 * the vendor defaults are commented below, so this file stays easy to diff
 * against `vendor/api-platform/laravel/config/api-platform.php` on upgrade.
 *
 * @see https://api-platform.com/docs/laravel/
 */

declare(strict_types=1);

use ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

return [
    'title' => 'LibreNMS',
    'description' => 'LibreNMS network monitoring API',
    'version' => '1.0.0',
    'show_webby' => false,

    'routes' => [
        'domain' => null,
        // Applied to every API Platform route, including the documentation.
        // EnsureApiV2Enabled keeps the beta API behind the api.v2.enabled
        // setting. EnforceJson must not be used here, it overwrites the Accept
        // header and would break content negotiation; DefaultAcceptJson only
        // fills one in when the client did not send one.
        'middleware' => [
            \App\Http\Middleware\EnsureApiV2Enabled::class,
            \App\Http\Middleware\DefaultAcceptJson::class,
            'auth:sanctum',
        ],
    ],

    // Only Eloquent models carry #[ApiResource] today. Add app/ApiResource
    // here if plain resource classes are introduced later.
    //
    // API Platform builds resource metadata from the database schema, so
    // scanning these paths needs a working connection. App\Providers\
    // ApiPlatformServiceProvider empties this list for requests and commands
    // that do not need the v2 routes, keeping LibreNMS bootable without a
    // database.
    'resources' => [
        app_path('Models'),
    ],

    'formats' => [
        'jsonld' => ['application/ld+json'],
        'json' => ['application/json'],
        'jsonapi' => ['application/vnd.api+json'],
    ],

    'patch_formats' => [
        'json' => ['application/merge-patch+json'],
    ],

    // When true, 'required' validation rules are replaced with 'sometimes'
    // on PATCH operations, allowing partial updates without requiring all fields.
    'partial_patch_validation' => false,

    // When true (default), HEAD requests skip response body construction so
    // collections are not iterated. Set to false to process HEAD like GET.
    'enable_head_request_optimization' => true,

    'docs_formats' => [
        'jsonld' => ['application/ld+json'],
        'jsonapi' => ['application/vnd.api+json'],
        'jsonopenapi' => ['application/vnd.openapi+json'],
        'html' => ['text/html'],
    ],

    'error_formats' => [
        'jsonproblem' => ['application/problem+json'],
    ],

    'defaults' => [
        'pagination_enabled' => true,
        'pagination_partial' => false,
        'pagination_client_enabled' => false,
        'pagination_client_items_per_page' => true,
        'pagination_client_partial' => false,
        'pagination_items_per_page' => 50,
        'pagination_maximum_items_per_page' => 500,
        // Everything API Platform registers lives under the v2 prefix.
        'route_prefix' => '/api/v2',
        'middleware' => [],
    ],

    'pagination' => [
        'page_parameter_name' => 'page',
        'enabled_parameter_name' => 'pagination',
        'items_per_page_parameter_name' => 'itemsPerPage',
        'partial_parameter_name' => 'partial',
    ],

    'jsonapi' => [
        'use_iri_as_id' => false,
        'allow_client_generated_id' => false,
    ],

    'graphql' => [
        'enabled' => false,
        'nesting_separator' => '__',
        'introspection' => ['enabled' => true],
        'max_query_complexity' => 500,
        'max_query_depth' => 200,
    ],

    'graphiql' => [
        //
    ],

    // Null keeps the database column names (hostname, sysName, location_id,
    // ...) as the API field names, matching the rest of the LibreNMS API.
    'name_converter' => null,

    'exception_to_status' => [
        AuthenticationException::class => 401,
        AuthorizationException::class => 403,
    ],

    'redoc' => [
        'enabled' => true,
    ],

    'scalar' => [
        'enabled' => true,
        'extra_configuration' => [],
    ],

    'swagger_ui' => [
        'enabled' => true,
        'http_auth' => [
            'API token' => [
                'scheme' => 'bearer',
            ],
        ],
    ],

    'url_generation_strategy' => UrlGeneratorInterface::ABS_PATH,

    'path_segment_name_generator' => UnderscorePathSegmentNameGenerator::class,

    'serializer' => [
        'hydra_prefix' => false,
    ],

    'cache' => 'file',

    // Path to an Eloquent model metadata file produced by
    // `php artisan api-platform:metadata:dump`. When set (and APP_DEBUG is
    // false) model attributes are read from it instead of introspecting the
    // database, allowing the app to boot without a live database.
    'metadata_dump' => null,

    // LibreNMS does not expose an MCP endpoint.
    'mcp' => [
        'enabled' => false,
    ],

    'error_handler' => [
        'extend_laravel_handler' => true,
    ],
];
