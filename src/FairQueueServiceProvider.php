<?php

namespace Aloware\FairQueue;

use Aloware\FairQueue\Facades\FairQueue;
use Aloware\FairQueue\Repositories\RedisRepository;
use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
        FairQueue::shouldProxyTo(RepositoryInterface::class);
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerResources();
    }

        /**
     * Register the FairQueue routes.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => 'fairqueue',
            'namespace' => 'Aloware\FairQueue\Http\Controllers',
            'middleware' => ['web', 'horizon']
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register the FairQueue resources.
     *
     * @return void
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'fairqueue');
    }
}