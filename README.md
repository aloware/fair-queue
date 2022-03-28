# Aloware - Fair Queue

Laravel package to provide fair consumption of jobs against multiple partitions.

## Installation
```sh
composer require aloware/fair-queue
```

## Assets
Run the command below to publish the package asset files:

```sh
php artisan vendor:publish --tag=public --force
```

Run the command below to publish the package config file:

```sh
php artisan vendor:publish --tag=fairqueue-config --force
```


## Usage
This package uses Redis as data storage. By default it uses `default` redis connection. You may configure it to use another connection within the fair-queue configuration file or by setting in the environment file.

```
FAIR_QUEUE_REDIS_DB="default"
FAIR_QUEUE_KEY_PREFIX="fair-queue"
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

## Monitoring

To monitor queue partitions, jobs etc... Go to this route:

```
https://your.domain/fairqueue/dashboard
```

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
