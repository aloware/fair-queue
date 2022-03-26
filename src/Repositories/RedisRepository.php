<?php

namespace Aloware\FairQueue\Repositories;

use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Support\Facades\Redis;

class RedisRepository implements RepositoryInterface
{
    public function partitions($queue)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection($prefix);

        $pattern = sprintf(
            '*%s:%s:*',
            $prefix,
            $queue
        );

        return array_map(function ($item) use ($prefix, $queue) {
            $removablePrefix = $prefix . ':' . $queue . ':';
            $pos = strpos($item, $removablePrefix);
            return substr($item, $pos + strlen($removablePrefix));
        }, $redis->keys($pattern));
    }

    public function queues()
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection($prefix);

        $pattern = sprintf(
            '*%s:*',
            $prefix
        );

        $queues = array_map(function ($item) use ($prefix) {
            $removablePrefix = $prefix . ':';
            $pos = strpos($item, $removablePrefix);
            $rep = substr($item, $pos + strlen($removablePrefix));
            return explode(':', $rep)[0];
        }, $redis->keys($pattern));
        return array_values(array_unique($queues));
    }

    public function queuesWithPartitions()
    {
        $queues = [];

        foreach($this->queues() as $key=>$queue) {
            $queues[$key]['queue'] = $queue;
            $queues[$key]['count'] = count($this->partitions($queue));
        }

        return $queues;
    }

    public function partitionsWithCount($queue)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection($prefix);

        $pattern = sprintf(
            '*%s:%s:*',
            $prefix,
            $queue
        );
        $keys = $redis->keys($pattern);
        $partitions = [];

        foreach($keys as $key=>$value) {
            $partitions[$key]['name'] = $value;
            $partitions[$key]['count'] = $redis->llen($value);
        }

        return $partitions;
    }

    public function push($queue, $partition, $job)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection($prefix);

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
        $redis = $this->getConnection($prefix);

        $key = sprintf(
            '%s:%s:%s',
            $prefix,
            $queue,
            $partition
        );

        return $redis->lpop($key);
    }

    public function acknowledge($queue, $partition, $jobId)
    {
        // TODO: we should secure retrievals so in case of service crashes
        //  we can retries jobs.
    }

    private function getConnection($prefix)
    {
        return Redis::connection($prefix);
    }

    private function getPrefix()
    {
        return config('database.redis.fair-queue.prefix');
    }

}