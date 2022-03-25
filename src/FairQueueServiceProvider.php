<?php

namespace Aloware\FairQueue;

use Aloware\FairQueue\Repository\RedisRepository;
use Aloware\FairQueue\Repository\RepositoryInterface;
use Illuminate\Support\ServiceProvider;

class FairQueueServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(
            RepositoryInterface::class,
            RedisRepository::class
        );
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {

    }
}
