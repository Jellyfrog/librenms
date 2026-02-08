<?php

/*
 * API Platform Configuration for LibreNMS v2 API.
 *
 * This configures the API Platform-based v2 API endpoints.
 * The legacy v0 API remains untouched at /api/v0/.
 */

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

return [
    'title' => 'LibreNMS API',
    'description' => 'LibreNMS Network Monitoring System API v2',
    'version' => '2.0.0',
    'show_webby' => false,

    'routes' => [
        'domain' => null,
        'middleware' => [
            \App\Http\Middleware\EnforceJson::class,
            'auth:token',
        ],
    ],

    'resources' => [
        app_path('Models'),
    ],

    'formats' => [
        'json' => ['application/json'],
        'jsonld' => ['application/ld+json'],
    ],

    'patch_formats' => [
        'json' => ['application/merge-patch+json', 'application/json'],
    ],

    'docs_formats' => [
        'jsonopenapi' => ['application/vnd.openapi+json'],
        'html' => ['text/html'],
    ],

    'error_formats' => [
        'jsonproblem' => ['application/problem+json'],
    ],

    'defaults' => [
        'pagination_enabled' => true,
        'pagination_partial' => false,
        'pagination_client_enabled' => true,
        'pagination_client_items_per_page' => true,
        'pagination_client_partial' => false,
        'pagination_items_per_page' => 50,
        'pagination_maximum_items_per_page' => 200,
        'route_prefix' => '/api/v2',
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

    // Keep snake_case to match LibreNMS conventions
    'name_converter' => null,

    'exception_to_status' => [
        AuthenticationException::class => 401,
        AuthorizationException::class => 403,
    ],

    'swagger_ui' => [
        'enabled' => true,
        'apiKeys' => [
            'apiToken' => [
                'name' => 'X-Auth-Token',
                'type' => 'header',
            ],
        ],
    ],

    'openapi' => [
        'tags' => [
            ['name' => 'Device', 'description' => 'Network device management'],
            ['name' => 'Port', 'description' => 'Network port/interface management'],
            ['name' => 'Alert', 'description' => 'Alert management'],
            ['name' => 'AlertRule', 'description' => 'Alert rule management'],
            ['name' => 'Sensor', 'description' => 'Sensor data'],
            ['name' => 'Location', 'description' => 'Location management'],
            ['name' => 'Service', 'description' => 'Service monitoring'],
            ['name' => 'Bill', 'description' => 'Billing management'],
            ['name' => 'DeviceGroup', 'description' => 'Device group management'],
            ['name' => 'PortGroup', 'description' => 'Port group management'],
            ['name' => 'Routing', 'description' => 'Routing protocols (BGP, OSPF, VRF)'],
            ['name' => 'Log', 'description' => 'Event, syslog, and alert logs'],
            ['name' => 'Network', 'description' => 'IP addresses, ARP, FDB'],
            ['name' => 'RRD', 'description' => 'RRD graph and data endpoints'],
        ],
    ],

    'serializer' => [
        'hydra_prefix' => false,
    ],

    'cache' => 'file',
];
