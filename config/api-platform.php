<?php

use ApiPlatform\Metadata\Operation\UnderscorePathSegmentNameGenerator;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

// The response formats of the resources; the first one is the default.
$formats = [
    'jsonapi' => ['application/vnd.api+json'],
    'json' => ['application/json'],
    'jsonld' => ['application/ld+json'],
    'jsonhal' => ['application/hal+json'],
];

return [
    'title' => 'LibreNMS API',
    'description' => 'The LibreNMS v1 API (beta).',
    'version' => '1.0.0',
    'show_webby' => false,

    'routes' => [
        'domain' => null,
        // Applied to every API Platform route, including the docs.
        'middleware' => [
            App\Http\Middleware\EnsureApiV1Enabled::class,
            App\Http\Middleware\Api\AuthenticateApiSession::class,
            'auth:sanctum',
        ],
    ],

    // Where #[ApiResource] is looked for: on models, and on the classes for
    // resources that are not a table.
    'resources' => [
        app_path('Models'),
        app_path('ApiResource'),
    ],

    'formats' => $formats,

    'patch_formats' => [
        'json' => ['application/merge-patch+json'],
    ],

    'docs_formats' => [
        'jsonopenapi' => ['application/vnd.openapi+json'],
        'html' => ['text/html'],
        'json' => ['application/json'],
        'jsonld' => ['application/ld+json'],
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
        'route_prefix' => '/api/v1',
        // Named here as well so that a resource answers a browser with data: left
        // to the list above, it would also get the documentation page.
        'formats' => $formats,
        'middleware' => [],
    ],

    'pagination' => [
        'page_parameter_name' => 'page',
        'enabled_parameter_name' => 'pagination',
        'items_per_page_parameter_name' => 'itemsPerPage',
        'partial_parameter_name' => 'partial',
    ],

    'graphql' => [
        'enabled' => false,
    ],

    // Fields keep their column names, except where a model renames one.
    'name_converter' => null,

    'exception_to_status' => [
        AuthenticationException::class => 401,
        AuthorizationException::class => 403,
    ],

    // Its static files have to be in the web root, at vendor/api-platform.
    'swagger_ui' => [
        'enabled' => true,
        // documents the bearer token in the OpenAPI description
        'http_auth' => [
            'token' => ['scheme' => 'bearer'],
        ],
    ],

    'redoc' => [
        'enabled' => false,
    ],

    'scalar' => [
        'enabled' => false,
    ],

    'mcp' => [
        'enabled' => false,
    ],

    // Resource metadata is cached here. Not the default store: routes are built
    // from it on every boot, and that has to work before there is a database.
    'cache' => env('API_PLATFORM_CACHE', 'file'),

    'url_generation_strategy' => UrlGeneratorInterface::ABS_PATH,
    'path_segment_name_generator' => UnderscorePathSegmentNameGenerator::class,
];
