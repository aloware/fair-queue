<?php

namespace Aloware\FairQueue;

use Aloware\FairQueue\Commands\GenerateSignal;
use Aloware\FairQueue\Commands\Publish;
use Aloware\FairQueue\Commands\PurgeFailedJobs;
use Aloware\FairQueue\Commands\RecoverLostJobs;
use Aloware\FairQueue\Commands\RecoverStuckJobs;
use Aloware\FairQueue\Commands\RetryFailedJobs;
use Aloware\FairQueue\Commands\RefreshStats;
use Aloware\FairQueue\Commands\RemoveExtraHorizonSignals;
use Aloware\FairQueue\Facades\FairQueue;
use Aloware\FairQueue\Repositories\RedisRepository;
use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Aloware\FairQueue\Repositories\RedisKeys;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class FairQueueServiceProvider extends ServiceProvider
{
    use RedisKeys;
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
        $this->commands([
            RemoveExtraHorizonSignals::class,
            RecoverStuckJobs::class,
            PurgeFailedJobs::class,
            RecoverLostJobs::class,
            RetryFailedJobs::class,
            GenerateSignal::class,
            RefreshStats::class,
            Publish::class,
        ]);

        $this->registerRoutes();
        $this->registerResources();
        $this->publishAssets();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            if(config('fair-queue.recover_lost_jobs.enabled')) {
                $age = config('fair-queue.recover_lost_jobs.age', 3600);
                // recover lost jobs since `$age` seconds ago
                $schedule->command(RecoverLostJobs::class, [$age])->hourly()->withoutOverlapping();
            }

            if(config('fair-queue.recover_stuck_jobs.enabled')) {
                // recover stuck jobs
                $schedule->command(RecoverStuckJobs::class)->everyFiveMinutes()->withoutOverlapping();
                // remove extra Horizon signals
                $schedule->command(RemoveExtraHorizonSignals::class)->everyFiveMinutes()->withoutOverlapping();
            }
            if(config('fair-queue.stats.enabled')) {
                // refresh stats for dashboard
                $schedule->command(RefreshStats::class)->everyMinute()->withoutOverlapping();
            }
        });
    }

    /**
     * Register the FairQueue routes.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix'     => 'fairqueue',
            'namespace'  => 'Aloware\FairQueue\Http\Controllers',
            'middleware' => config('fair-queue.middleware', 'web'),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Register the FairQueue resources.
     *
     * @return void
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'fairqueue');
    }

    /**
     * Publish public assets.
     */
    protected function publishAssets()
    {
        $this->publishes([
            __DIR__ . '/../config/fair-queue.php' => config_path('fair-queue.php'),
        ], 'fairqueue-config');

        $this->publishes([
            realpath(__DIR__ . '/../public') => public_path('vendor/fairqueue'),
        ], 'public');
    }
}
