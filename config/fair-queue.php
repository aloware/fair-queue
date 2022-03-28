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
    'middleware' => ['web'],

    'database'   => env('FAIR_QUEUE_REDIS_DB', 'default'),

    'key_prefix' => env('FAIR_QUEUE_KEY_PREFIX', 'fair-queue'),
];
