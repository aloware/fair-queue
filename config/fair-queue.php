<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FairQueue Status
    |--------------------------------------------------------------------------
    |
    | Set this option to false if you don't want queues to be processed fairly.
    */
    'enabled' => env('FAIR_QUEUE_ENABLED', true),

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

    /*
    |--------------------------------------------------------------------------
    | FairQueue Redis DB
    |--------------------------------------------------------------------------
    |
    | If you have a seperated redis connection for fair-queue, you can specify
    | Redis connection name here
    */
    'database'   => env('FAIR_QUEUE_REDIS_DB', 'default'),

    /*
    |--------------------------------------------------------------------------
    | FairQueue Redis DB
    |--------------------------------------------------------------------------
    |
    | If you want to have a custom redis key prefix for your fair-queue queues
    | you can set it here
    */
    'key_prefix' => env('FAIR_QUEUE_KEY_PREFIX', 'fair-queue'),
];
