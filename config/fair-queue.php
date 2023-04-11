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
    | FairQueue Default Queue Name
    |--------------------------------------------------------------------------
    |
    | When a job goes to fair-queue without specified queue name, it will go
    | to this queue name.
    |
    */
    'default_queue_name' => 'default',

    /*
    |--------------------------------------------------------------------------
    | FairQueue Queue Tries
    |--------------------------------------------------------------------------
    |
    | After a jobs how many times it should requeue? You can specify the
    | number of tries here.
    |
    */
    'queues' => [
        'default' => ['tries' => 3],
    ],

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
    | FairQueue Signal Redis DB
    |--------------------------------------------------------------------------
    |
    | If you have a seperated redis connection for fair-queue signals, you can specify
    | Redis connection name here
    */
    'signals_database'   => env('FAIR_QUEUE_SIGNALS_REDIS_DB', 'default'),

    /*
    |--------------------------------------------------------------------------
    | FairQueue Redis DB
    |--------------------------------------------------------------------------
    |
    | If you want to have a custom redis key prefix for your fair-queue queues
    | you can set it here
    */
    'key_prefix' => env('FAIR_QUEUE_KEY_PREFIX', 'fair-queue'),

    /*
    |--------------------------------------------------------------------------
    | FairQueue Signal Key Prefix for Horizon
    |--------------------------------------------------------------------------
    |
    | This prefix will add to the FairQueue signal key prefix
    */
    'signal_key_prefix_for_horizon' => env('FAIR_QUEUE_SIGNAL_KEY_PREFIX_FOR_HORIZON', null),

    /*
    |--------------------------------------------------------------------------
    | FairQueue Recover Lost Jobs
    |--------------------------------------------------------------------------
    |
    | Tries to recover jobs which have been on in-progress mode for a long time.
    | You can set recover jobs with a certain age in seconds
    */
    'recover_lost_jobs' => [
        'enabled' => env('FAIR_QUEUE_RECOVER_LOST_JOBS_ENABLED', false),
        'age' => 3600
    ],

    /*
    |--------------------------------------------------------------------------
    | FairQueue Recover Stuck Jobs
    |--------------------------------------------------------------------------
    |
    | Tries to recover stuck jobs
    */
    'recover_stuck_jobs' => [
        'enabled' => env('FAIR_QUEUE_RECOVER_STUCK_JOBS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | FairQueue Refresh Stats
    |--------------------------------------------------------------------------
    | FairQueue Stats Configurations
    |
    */
    'stats' => [
        'enabled' => env('FAIR_QUEUE_REFRESH_STATS_ENABLED', true),
    ],
];
