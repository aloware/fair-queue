<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FairQueue Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each FairQueue route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */
    'middleware' => ['web', 'horizon'],

    'host'     => env('FAIR_QUEUE_REDIS_HOST', '127.0.0.1'),
    'password' => env('FAIR_QUEUE_REDIS_PASSWORD', null),
    'port'     => env('FAIR_QUEUE_REDIS_PORT', 6379),
    'database' => env('FAIR_QUEUE_REDIS_DB', 1),
    'prefix'   => env('FAIR_QUEUE_KEY_PREFIX', 'fair-queue'),
];
