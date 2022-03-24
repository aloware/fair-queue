<?php

namespace Aloware\FairQueue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Redis;

class FairSignalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $partition;

    public $originalJob;

    public function __construct($job)
    {
        $this->originalJob = $job;
    }

    public function handle()
    {
        $prefix = config('database.redis.fair-queue.prefix');

        $redis = Redis::connection($prefix);

        $selectPartition = function () use ($redis, $prefix, &$partitionIndex) {
            $pattern = sprintf(
                '*%s:%s:*',
                $prefix,
                $this->queue
            );

            $partitions = $redis->keys($pattern);

            if (empty($partitions)) {
                return 'null';
            }

            $partitionIndex = random_int(0, count($partitions) - 1);

            return substr(
                $partitions[$partitionIndex],
                strpos($partitions[$partitionIndex], $prefix.':')
            );
        };

        $partition = $selectPartition();

        $tries = 0;
        while (empty($jobSerialized = $redis->lpop($partition))) {
            // maybe this partition has run out of jobs during the
            //  random selection process, so try getting a fresh list
            //  of partitions and pick another one.

            sleep(1);

            $tries++;
            if ($tries >= 10) {
                // no jobs available to process (concluded after 10
                // times retry with a second delay each).
                return;
            }

            $partition = $selectPartition();
        }

        $job = unserialize($jobSerialized);

        try {
            $job->handle();
        } catch (\Throwable $e) {
            // push it to the list to be retried later

            $key = sprintf(
                '%s:%s:%s',
                $prefix,
                $this->queue,
                $partition
            );

            $redis->rpush($key, $jobSerialized);

            throw $e;
        }
    }

    public function addToPartition()
    {
        $prefix = config('database.redis.fair-queue.prefix');

        $redis = Redis::connection($prefix);

        $key = sprintf(
            '%s:%s:%s',
            $prefix,
            $this->queue,
            $this->partition
        );

        $redis->rpush($key, serialize($this->originalJob));

        // avoid unnecessary size allocation
        $this->originalJob = null;
    }

    public function onPartition($partition)
    {
        $this->partition = $partition;

        return $this;
    }
}
