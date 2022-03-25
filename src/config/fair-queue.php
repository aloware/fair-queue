<?php

return [
    'url'      => env('FAIR_QUEUE_REDIS_URL'),
    'host'     => env('FAIR_QUEUE_REDIS_HOST', '127.0.0.1'),
    'password' => env('FAIR_QUEUE_REDIS_PASSWORD', null),
    'port'     => env('FAIR_QUEUE_REDIS_PORT', 6379),
    'database' => env('FAIR_QUEUE_REDIS_DB', 1),
    'prefix'   => env('FAIR_QUEUE_KEY_PREFIX', 'fair-queue'),
];
