<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Prepper HTTP ingest URL
    |--------------------------------------------------------------------------
    |
    | Laravel runs on the host; Data Prepper publishes port 2021, so use
    | localhost. Inside the same Docker Compose network, use:
    | http://data-prepper:2021/laravel/logs
    |
    */

    'url' => env('DATA_PREPPER_URL', 'http://127.0.0.1:2021/laravel/logs'),

    'timeout' => (int) env('DATA_PREPPER_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Queue name for log shipping jobs
    |--------------------------------------------------------------------------
    |
    | Keep log delivery on a dedicated queue so it does not block higher-
    | priority application jobs.
    |
    */

    'queue' => env('DATA_PREPPER_QUEUE_NAME', 'logs'),

];
