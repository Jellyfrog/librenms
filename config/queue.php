<?php

return [

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

];
