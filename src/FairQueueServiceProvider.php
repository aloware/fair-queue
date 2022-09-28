<?php

namespace Aloware\FairQueue;

use Aloware\FairQueue\Commands\GenerateSignal;
use Aloware\FairQueue\Commands\Publish;
use Aloware\FairQueue\Commands\PurgeFailedJobs;
use Aloware\FairQueue\Commands\RecoverLostJobs;
use Aloware\FairQueue\Commands\RecoverStuckJobs;
use Aloware\FairQueue\Commands\RetryFailedJobs;
use Aloware\FairQueue\Commands\RefreshStats;
use Aloware\FairQueue\Facades\FairQueue;
use Aloware\FairQueue\Repositories\RedisRepository;
use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Aloware\FairQueue\Repositories\RedisKeys;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;

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
            Publish::class,
            RecoverLostJobs::class,
            RecoverStuckJobs::class,
            GenerateSignal::class,
            RetryFailedJobs::class,
            PurgeFailedJobs::class,
            RefreshStats::class,
        ]);

        $this->registerRoutes();
        $this->registerResources();
        $this->publishAssets();
        $this->registerQueueEvents();

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            if(config('fair-queue.recover_lost_jobs.enabled')) {
                $age = config('fair-queue.recover_lost_jobs.age', 3600);
                // recover lost jobs since `$age` seconds ago
                $schedule->command(RecoverLostJobs::class, [$age])->hourly();
            }

            if(config('fair-queue.recover_stuck_jobs.enabled')) {
                // recover stuck jobs
                $schedule->command(RecoverStuckJobs::class)->everyFiveMinutes();
            }
            // refresh stats for dashboard
            $schedule->command(RefreshStats::class)->everyMinute();
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
     * Register the FairQueue Queue Events.
     *
     * @return void
     */
    protected function registerQueueEvents(): void
    {
        Queue::after(function (JobProcessed $event) {
            $redis = FairQueue::getConnection();
            $payload = $event->job->payload();
            if (!isset($payload['data']) || !isset($payload['data']['command'])) {
                return;
            }
            $command = unserialize($payload['data']['command']);
            if (!$command instanceof FairSignalJob) {
                return;
            }
            $queue = $command->queue;
            $partition = $command->partition;
            $past_minute_key = $this->partitionProcessedJobsInPastMinutesKey($queue, $partition, 1);
            $past_20minute_key = $this->partitionProcessedJobsInPastMinutesKey($queue, $partition, 20);
            $past_60minute_key = $this->partitionProcessedJobsInPastMinutesKey($queue, $partition, 60);
            $redis->zadd($past_minute_key, now()->getPreciseTimestamp(3), $payload['id']);
            $redis->zadd($past_20minute_key, now()->getPreciseTimestamp(3), $payload['id']);
            $redis->zadd($past_60minute_key, now()->getPreciseTimestamp(3), $payload['id']);
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
