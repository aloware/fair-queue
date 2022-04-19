# Aloware - Fair Queue

Laravel package to provide fair consumption of jobs against multiple partitions.

## Installation
```sh
composer require aloware/fair-queue
```

## Assets
Run the following command to publish assets and config file:

```sh
php artisan fair-queue publish
```

## Usage
This package uses Redis as data storage. By default it uses `default` 
redis connection. You may configure to use another connection within
the fair-queue config file or by setting in the environment file.

```
FAIR_QUEUE_REDIS_DB="default"
FAIR_QUEUE_KEY_PREFIX="fair-queue"
```

Now, you need to replace `use Dispatchable;` with `use FairDispatchable;`
in the Job class you need fair consumption functionality.
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

You can partition your data using `->fairConsume()` at dispatch time
and let your queue jobs be consumed fairly among those partitions.
```
ExampleJob::dispatch()
    ->onConnection($connection)
    ->onQueue($queue)
    ->fairConsume($companyId);
```

### Retries
That is very important to understand the mechanics of this package.
You may understand that the FairSignalJob which has been sent to the
queue instead of the original job has no idea about the exact job which is
going to be processed after the signal is received by the consumer.

There is no guarantee that the same job will
be selected to be processed by the same signal in case of failure/retry.

So the number of tries you configure on `queue:work` command is not
effective. It is recommended to set it to the biggest number you
can imagine for max tries (ie. 10) and set the number of tries using
the fair queue's `->tries()` chain call.
```
ExampleJob::dispatch()
    ->onConnection($connection)
    ->onQueue($queue)
    ->fairConsume($companyId)
    ->tries(3);
```

## Monitoring

Check out the dashboard to monitor queue partitions, jobs, etc.:

```
https://your.domain/fairqueue/dashboard
```

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
