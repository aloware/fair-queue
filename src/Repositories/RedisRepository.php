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

        return array_map(function ($item) use ($prefix, $queue) {
            $removablePrefix = $prefix . ':' . $queue . ':';
            $pos = strpos($item, $removablePrefix);
            return substr($item, $pos + strlen($removablePrefix));
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
        $redis = $this->getConnection();

        $pattern = sprintf(
            '*%s:%s:*',
            $prefix,
            $queue
        );
        $keys = $redis->keys($pattern);
        $partitions = [];

        foreach($keys as $key=>$value) {
            $partitions[$key]['name'] = explode(':', $value)[2];
            $partitions[$key]['count'] = $redis->llen($value);
        }

        return $partitions;
    }

    public function totalJobsCount($queues)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();

        $jobs_count = 0;

        foreach($queues as $queue) {
            foreach($this->partitions($queue) as $partition) {
                $jobs_count += $redis->llen("{$prefix}:{$queue}:{$partition}");
            }
        }
        return $jobs_count;
    }

    public function jobs($queue, $partition)
    {
        $prefix = $this->getPrefix();
        $redis = $this->getConnection();
        $per_page = request('limit', 25);
        $starting_at = request('starting_at', 0);

        $pattern = sprintf(
            '%s:%s:%s',
            $prefix,
            $queue,
            $partition,
        );

        $jobs = $redis->lrange($pattern, $starting_at, $per_page + $starting_at);
        $jobs_total_pages = ceil(count($redis->lrange($pattern, 0, -1)) / $per_page);

        $jobs_array = [];

        foreach($jobs as $index => $job)
        {
            $jobs_array[$index]['id'] = ($index + $starting_at);
            $jobs_array[$index]['name'] = get_class(unserialize($job));
        }

        $has_more = count($jobs) > $per_page;

        if($has_more) {
            array_pop($jobs_array);
        }

        return [
            'jobs'    => $jobs_array,
            'has_more' => $has_more,
            'total' => $jobs_total_pages
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
            $partition,
        );

        $jobs = $redis->lrange($pattern, $index, $index);

        $job = $jobs ? $jobs[0] : null;

        return $job;
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

        return $redis->lpop($key);
    }

    public function acknowledge($queue, $partition, $jobId)
    {
        // TODO: we should secure retrievals so in case of service crashes
        //  we can retries jobs.
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