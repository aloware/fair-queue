<?php

namespace Aloware\FairQueue\Repository;

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
