<?php

namespace Aloware\FairQueue\Repositories;

use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Support\Facades\Redis;

class RedisRepository implements RepositoryInterface
{
    public function partitions($queue)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();

        $pattern = sprintf(
            '*%s:%s:*',
            $prefix,
            $queue
        );

        $removablePrefix = $prefix . ':' . $queue . ':';

        return array_map(function ($item) use ($removablePrefix) {
            return $this->removePrefix($removablePrefix, $item);
        }, $redis->keys($pattern));
    }

    public function queues()
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();

        $pattern = sprintf(
            '*%s:*',
            $prefix
        );

        $queues = array_map(function ($item) use ($prefix) {
            $rep = $this->removePrefix($prefix . ':', $item);
            return explode(':', $rep)[0];
        }, $redis->keys($pattern));

        return array_values(array_unique($queues));
    }

    public function queuesWithPartitions()
    {
        $queues = [];

        foreach ($this->queues() as $key => $queue) {
            $queues[$key]['queue'] = $queue;
            $queues[$key]['count'] = count($this->partitions($queue));
        }

        return $queues;
    }

    public function partitionsWithCount($queue)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();

        $pattern = sprintf(
            '*%s:%s:*',
            $prefix,
            $queue
        );

        $keys = $redis->keys($pattern);
        $partitions = [];

        foreach ($keys as $key => $value) {
            $partition = $this->removePrefix($prefix . ':', $value);

            $persecKey = $prefix . '-internal:' . $partition . ':persec';

            list ($lastAccess, $lastPersec) = explode(',', $redis->get($persecKey) ?? '0,0');

            $partitions[$key]['name'] = explode(':', $partition)[1];
            $partitions[$key]['count'] = $redis->llen($prefix . ':' . $partition) ?: 0;
            $partitions[$key]['per_second'] = $lastPersec;
        }

        return $partitions;
    }

    public function totalJobsCount($queues)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();

        $jobsCount = 0;

        foreach ($queues as $queue) {
            foreach ($this->partitions($queue) as $partition) {
                $jobsCount += $redis->llen("{$prefix}:{$queue}:{$partition}");
            }
        }

        return $jobsCount;
    }

    public function jobs($queue, $partition)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();
        $perPage = request('limit', 25);
        $startingAt = request('starting_at', 0);

        $pattern = sprintf(
            '%s:%s:%s',
            $prefix,
            $queue,
            $partition
        );

        $jobs = $redis->lrange($pattern, $startingAt, $perPage + $startingAt);
        $jobsTotalPages = ceil(count($redis->lrange($pattern, 0, -1)) / $perPage);

        $jobsArray = [];

        foreach ($jobs as $index => $job) {
            $jobsArray[$index]['id'] = ($index + $startingAt);
            $jobsArray[$index]['name'] = get_class(unserialize($job));
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
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();

        $pattern = sprintf(
            '%s:%s:%s',
            $prefix,
            $queue,
            $partition
        );

        $jobs = $redis->lrange($pattern, $index, $index);

        return $jobs ? $jobs[0] : null;
    }

    public function push($queue, $partition, $job)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();

        $key = sprintf(
            '%s:%s:%s',
            $prefix,
            $queue,
            $partition
        );

        $redis->rpush($key, $job);
    }

    public function pop($queue, $partition)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();

        $key = sprintf(
            '%s:%s:%s',
            $prefix,
            $queue,
            $partition
        );

        $processedKey = sprintf(
            '%s-internal:%s:%s:processed',
            $prefix,
            $queue,
            $partition
        );

        $persecKey = sprintf(
            '%s-internal:%s:%s:persec',
            $prefix,
            $queue,
            $partition
        );

        $redis->incr($processedKey);
        $redis->expire($processedKey, 3);

        $now = time();
        list ($lastAccess, $lastPersec) = explode(',', $redis->get($persecKey) ?? ($now - 1) . ',0');

        if ($now - $lastAccess >= 1) {
            $persec = max($redis->get($processedKey) ?? 0, 0);

            $data = $now . ',' . max($persec, $persec - $lastPersec);
            $redis->set($persecKey, $data, 'EX', 3);

            $redis->decrBy($processedKey, $persec);
        }

        return $redis->lpop($key);
    }

    public function acknowledge($queue, $partition, $jobId)
    {
        // TODO: we should secure retrievals so in case of service crashes
        //  we can retries jobs.
    }

    private function removeBeforePrefix($prefix, $value)
    {
        $removablePrefix = $prefix . ':';
        $pos = strpos($value, $removablePrefix);
        return substr($value, $pos);
    }

    private function removePrefix($prefix, $value)
    {
        $pos = strpos($value, $prefix);
        return substr($value, $pos + strlen($prefix));
    }

    private function getConnection()
    {
        $database = config('fair-queue.database');
        return Redis::connection($database);
    }

    private function getPrefix()
    {
        return config('fair-queue.key_prefix');
    }

}
