<?php

/**
 * crawler_config.php
 *
 * Configuration file for LibreNMS Website Crawler Tests
 * This file contains default settings and examples for
 * configuring the crawler test suite.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2024 LibreNMS Contributors
 * @author     LibreNMS Contributors
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Crawler Configuration
    |--------------------------------------------------------------------------
    |
    | These are the default settings used by the crawler test suite.
    | You can override these settings in individual test classes.
    |
    */

    'default' => [
        'max_depth' => 3,
        'delay_between_requests' => 0, // milliseconds
        'timeout' => 30, // seconds
        'concurrent_requests' => 1,
        'ignore_robots' => true,
        'user_agent' => 'LibreNMS Test Crawler/1.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile-Specific Configurations
    |--------------------------------------------------------------------------
    |
    | Define different crawler configurations for different testing scenarios.
    | You can reference these profiles in your test classes.
    |
    */

    'profiles' => [
        'fast' => [
            'max_depth' => 2,
            'delay_between_requests' => 0,
            'timeout' => 10,
            'concurrent_requests' => 5,
            'ignore_robots' => true,
            'user_agent' => 'LibreNMS Fast Crawler/1.0',
        ],

        'thorough' => [
            'max_depth' => 5,
            'delay_between_requests' => 500,
            'timeout' => 60,
            'concurrent_requests' => 1,
            'ignore_robots' => false,
            'user_agent' => 'LibreNMS Thorough Crawler/1.0',
        ],

        'polite' => [
            'max_depth' => 3,
            'delay_between_requests' => 1000,
            'timeout' => 30,
            'concurrent_requests' => 1,
            'ignore_robots' => false,
            'user_agent' => 'LibreNMS Polite Crawler/1.0',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Patterns
    |--------------------------------------------------------------------------
    |
    | Define URL patterns for different types of content that should be
    | handled differently during crawling.
    |
    */

    'url_patterns' => [
        'api_endpoints' => [
            '/api/',
            '/rest/',
            '/graphql',
        ],

        'static_assets' => [
            '/css/',
            '/js/',
            '/images/',
            '/fonts/',
            '/assets/',
        ],

        'admin_pages' => [
            '/admin/',
            '/dashboard/',
            '/settings/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Validation Rules
    |--------------------------------------------------------------------------
    |
    | Define validation rules for different types of responses.
    |
    */

    'validation' => [
        'html_pages' => [
            'required_elements' => ['html', 'head', 'body'],
            'max_response_time' => 5.0, // seconds
            'min_content_length' => 100, // bytes
        ],

        'api_endpoints' => [
            'expected_content_types' => ['application/json', 'application/xml'],
            'max_response_time' => 2.0, // seconds
        ],

        'static_assets' => [
            'max_response_time' => 10.0, // seconds
            'cache_headers_required' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure how different types of errors should be handled.
    |
    */

    'error_handling' => [
        'allowed_network_errors' => [
            'Connection refused',
            'Connection timed out',
            'Name or service not known',
            'No route to host',
        ],

        'allowed_http_errors' => [
            404, // Not Found - might be expected for some URLs
            403, // Forbidden - might be expected for protected resources
        ],

        'critical_errors' => [
            500, // Internal Server Error
            502, // Bad Gateway
            503, // Service Unavailable
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Environment Settings
    |--------------------------------------------------------------------------
    |
    | Settings that control test behavior in different environments.
    |
    */

    'environment' => [
        'skip_external_urls' => env('CRAWLER_SKIP_EXTERNAL', true),
        'max_crawl_time' => env('CRAWLER_MAX_TIME', 300), // seconds
        'enable_screenshots' => env('CRAWLER_SCREENSHOTS', false),
        'log_level' => env('CRAWLER_LOG_LEVEL', 'info'),
    ],
];