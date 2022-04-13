<?php

namespace Aloware\FairQueue\Repositories;

use Aloware\FairQueue\FairSignalJob;
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

    public function expectAcknowledge($connection, $queue, $partition, $jobUuid, $job)
    {
        $redis = $this->getConnection();

        $key             = $this->inProgressJobKey($connection, $queue, $partition, $jobUuid);
        $sampleSignalKey = $this->partitionSampleSignalKey($queue, $partition);

        $redis->set($key, $job);
        $redis->set($sampleSignalKey, serialize([$connection, $queue, $partition]));
    }

    public function acknowledge($connection, $queue, $partition, $jobUuid)
    {
        $redis = $this->getConnection();

        $key = $this->inProgressJobKey($connection, $queue, $partition, $jobUuid);

        $redis->del($key);
    }

    public function recoverLost($age = 300)
    {
        $redis = $this->getConnection();

        $pattern = $this->inProgressJobsPattern();
        $keys    = $redis->keys($pattern);

        $count = 0;

        foreach ($keys as $key) {
            list ($connection, $queue, $partition, $jobUuid) = $this->extractInProgressJobKey($key);

            $inProgressJobKey = $this->inProgressJobKey($connection, $queue, $partition, $jobUuid);

            $lastAccess = $redis->object('idletime', $inProgressJobKey);
            if ($lastAccess < $age) {
                continue;
            }

            // restore the job into partition
            $this->push($queue, $partition, $redis->get($inProgressJobKey));
            //

            // and generate fake signal
            $dispatch = dispatch(new FairSignalJob(null))->onQueue($queue);

            if (!empty($connection)) {
                $dispatch->onConnection($connection);
            }
            //

            $count++;
        }

        return $count;
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
