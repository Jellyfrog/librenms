<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection
    |--------------------------------------------------------------------------
    |
    | Only the maintenance queue has a worker, run other jobs in process unless
    | a queue is configured (see Distributed-Poller docs).
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Merged with the framework defaults.
    |
    | maintenance: serial queue for long running maintenance jobs, processed by
    | the scheduler launched worker (see routes/console.php).
    | retry_after must be longer than the longest job, otherwise a running job
    | is handed out again.
    |
    */

    'connections' => [
        'maintenance' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => 'maintenance',
            'retry_after' => App\Jobs\MaintenanceJob::MAX_RUNTIME,
            'after_commit' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | DB_CONNECTION is usually not set, use the same default as config/database.php
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', env('DBTEST') ? 'testing' : 'mysql'),
        'table' => 'failed_jobs',
    ],

];
