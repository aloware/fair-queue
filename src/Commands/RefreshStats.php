<?php

namespace Aloware\FairQueue\Commands;

use Aloware\FairQueue\Facades\FairQueue;
use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Aloware\FairQueue\Repositories\RedisKeys;
use Illuminate\Console\Command;

class RefreshStats extends Command
{
    use RedisKeys;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fair-queue:refresh-stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Jobs Stats';

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(RepositoryInterface $repository)
    {
        parent::__construct();

        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $redis = FairQueue::getConnection();

        $this->refreshKeys($redis, $redis->keys($this->recentProcesedJobsPattern(1)), 1);
        $this->refreshKeys($redis, $redis->keys($this->recentProcesedJobsPattern(20)), 20);
        $this->refreshKeys($redis, $redis->keys($this->recentProcesedJobsPattern(60)), 60);

        $this->info('Fair-Queue Stats Refreshed Successfully');
    }

    /**
     * Refresh Redis Keys
     *
     * @return mixed
     */
    public function refreshKeys($redis, $keys, $minute = 1)
    {
        $timestamp = now()->subMinutes($minute)->getPreciseTimestamp(3);
        foreach ($keys as $key)
        {
            $redis->zremrangebyscore($key, '-inf', "({$timestamp}");
        }
    }
}
