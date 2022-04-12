<?php

namespace Aloware\FairQueue\Repositories;

use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Support\Facades\Redis;

class RedisRepository implements RepositoryInterface
{
    use RedisKeys;

    public function partitions($queue)
    {
        return $this->partitionsPrivate($queue);
    }

    public function failedPartitions($queue)
    {
        return $this->partitionsPrivate(
            $queue,
            'queueFailedJobsPartitionListPattern',
            'extractPartitionNameFromFailedJobsPartitionKey'
        );
    }

    public function queues()
    {
        $redis = $this->getConnection();

        $keys = $redis->keys($this->queueListPattern());

        $queues = array_map(function ($key) {
            return $this->extractQueueNameFromPartitionKey($key);
        }, $keys);

        return array_values(array_unique($queues));
    }

    public function queuesWithPartitions()
    {
        $queues = [];

        foreach ($this->queues() as $queue) {
            $queues[] = [
                'queue' => $queue,
                'count' => count($this->partitions($queue))
            ];
        }

        return $queues;
    }

    public function partitionsWithCount($queue)
    {
        $redis = $this->getConnection();

        $pattern = $this->queuePartitionListPattern($queue);

        $keys       = $redis->keys($pattern);
        $partitions = [];

        foreach ($keys as $key) {
            $partition = $this->extractPartitionNameFromPartitionKey($key);

            $partitionKey       = $this->partitionKey($queue, $partition);
            $partitionPerSecKey = $this->partitionPerSecKey($queue, $partition);

            list ($lastAccess, $lastPersec) = explode(',', $redis->get($partitionPerSecKey) ?? '0,0');

            $partitions[] = [
                'name'       => $partition,
                'count'      => $redis->llen($partitionKey) ?: 0,
                'per_second' => $lastPersec
            ];
        }

        return $partitions;
    }

    public function totalJobsCount($queues)
    {
        $redis = $this->getConnection();

        $jobsCount = 0;

        foreach ($queues as $queue) {
            foreach ($this->partitions($queue) as $partition) {
                $jobsCount += $redis->llen($this->partitionKey($queue, $partition));
            }
        }

        return $jobsCount;
    }

    public function jobs($queue, $partition)
    {
        $redis      = $this->getConnection();
        $perPage    = request('limit', 25);
        $startingAt = request('starting_at', 0);

        $partitionKey = $this->partitionKey($queue, $partition);

        $jobs           = $redis->lrange($partitionKey, $startingAt, $perPage + $startingAt);
        $jobsTotalPages = ceil(count($redis->lrange($partitionKey, 0, -1)) / $perPage);

        $jobsArray = [];

        foreach ($jobs as $index => $job) {
            $jobsArray[] = [
                'id'   => $index + $startingAt,
                'name' => get_class(unserialize($job))
            ];
        }

        $hasMore = count($jobs) > $perPage;

        if ($hasMore) {
            array_pop($jobsArray);
        }

        return [
            'jobs'     => $jobsArray,
            'has_more' => $hasMore,
            'total'    => $jobsTotalPages
        ];
    }

    public function job($queue, $partition, $index)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->partitionKey($queue, $partition);

        $jobs = $redis->lrange($partitionKey, $index, $index);

        return $jobs ? $jobs[0] : null;
    }

    public function push($queue, $partition, $job)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->partitionKey($queue, $partition);

        $redis->rpush($partitionKey, $job);
    }

    public function pushFailed($queue, $partition, $job)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->failedJobsPartitionKey($queue, $partition);

        $redis->rpush($partitionKey, $job);
    }

    public function pop($queue, $partition)
    {
        return $this->popPrivate($queue, $partition, 'partitionKey');
    }

    public function popFailed($queue, $partition)
    {
        return $this->popPrivate($queue, $partition, 'failedJobsPartitionKey');
    }

    public function expectAcknowledge($connection, $queue, $partition, $jobUuid, $job, $wait = 60)
    {
        $redis = $this->getConnection();

        $key = $this->inProgressJobKey($queue, $partition, $jobUuid);

        $redis->set($key, $job);
    }

    public function acknowledge($connection, $queue, $partition, $jobUuid)
    {
        $redis = $this->getConnection();

        $key = $this->inProgressJobKey($queue, $partition, $jobUuid);

        $redis->del($key);
    }

    public function recoverLost()
    {
        // TODO:
        //  1- get sample job for each partition (to learn about the used queue connection)
        //  2- get all jobs which have been on in-progress mode for a long time
        //  3- generate fake signals on connection+queue
        //  4- create a console command to call this method
        //  5- configure service provider to schedule the console command to be running periodically
    }

    private function partitionsPrivate(
        $queue,
        $queuePartitionListPatternResolver = 'queuePartitionListPattern',
        $extractorResolver = 'extractPartitionNameFromPartitionKey'
    ) {
        $redis = $this->getConnection();

        $keys = $redis->keys($this->$queuePartitionListPatternResolver($queue));

        $partitions = array_map(function ($item) use ($extractorResolver) {
            return $this->$extractorResolver($item);
        }, $keys);

        return array_values($partitions);
    }

    private function popPrivate($queue, $partition, $partitionKeyResolver = 'partitionKey')
    {
        $redis = $this->getConnection();

        $partitionKey = $this->$partitionKeyResolver($queue, $partition);

        $processedKey       = $this->partitionProcessedCountJobKey($queue, $partition);
        $partitionPerSecKey = $this->partitionPerSecKey($queue, $partition);

        dump($partitionKey);

        $redis->incr($processedKey);
        $redis->expire($processedKey, 3);

        $now = time();
        list ($lastAccess, $lastPersec) = explode(',', $redis->get($partitionPerSecKey) ?? ($now - 1) . ',0');

        if ($now - $lastAccess >= 1) {
            $persec = max($redis->get($processedKey) ?? 0, 0);

            $data = $now . ',' . max($persec, $persec - $lastPersec);
            $redis->set($partitionPerSecKey, $data, 'EX', 3);

            $redis->decrBy($processedKey, $persec);
        }

        return $redis->lpop($partitionKey);
    }

    private function getConnection()
    {
        $database = config('fair-queue.database');
        return Redis::connection($database);
    }
}
