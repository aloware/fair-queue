# Aloware - Fair Queue

Laravel package to provide fair consumption of jobs against multiple partitions.

## Installation
```sh
composer require aloware/fair-queue
```

## Usage
This package uses Redis as storage. So you need to add **fair-queue** database configuration
to `config/database.php`.

```
...
    'redis' => [
        ...
        'fair-queue' => [
            'url'      => env('FAIR_QUEUE_REDIS_URL'),
            'host'     => env('FAIR_QUEUE_REDIS_HOST', '127.0.0.1'),
            'password' => env('FAIR_QUEUE_REDIS_PASSWORD', null),
            'port'     => env('FAIR_QUEUE_REDIS_PORT', 6379),
            'database' => env('FAIR_QUEUE_REDIS_DB', 1),
            'prefix'   => env('FAIR_QUEUE_KEY_PREFIX', 'fair-queue'),
        ],
    ]
];
```

Now, you need to replace `use Dispatchable;` with `use FairDispatchable;` in the Job class you
need fair consumption functionality.
```
<?php

namespace App\Jobs;

use Aloware\FairQueue\FairDispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExampleJob implements ShouldQueue
{
    use FairDispatchable, InteractsWithQueue, Queueable, SerializesModels;
...
```

Finally, when dispatching your job you can partition your data using `->fairConsume()`
chain call and let your queue jobs be consumed fairly between those partitions.
```
ExampleJob::dispatch()
    ->onConnection($connection)
    ->onQueue($queue)
    ->fairConsume("company-$companyId");
```

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
