<?php

namespace Aloware\FairQueue\Repositories;

use Aloware\FairQueue\Exceptions\SampleNotFoundException;
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

    public function queues()
    {
        return $this->queuesPrivate('queueListPattern', 'extractQueueNameFromPartitionKey');
    }

    public function queuesWithPartitions()
    {
        return $this->queuesWithPartitionsPrivate('queues', 'partitions');
    }

    public function jobs($queue, $partition)
    {
        return $this->jobsPrivate($queue, $partition, 'partitionKey');
    }

    public function job($queue, $partition, $index)
    {
        return $this->jobPrivate($queue, $partition, $index, 'partitionKey');
    }

    public function partitionsWithCount($queue)
    {
        return $this->partitionsWithCountPrivate(
            $queue,
            'queuePartitionListPattern',
            'extractPartitionNameFromPartitionKey',
            'partitionKey',
            'partitionPerSecKey',
            true
        );
    }

    public function totalJobsCount($queues)
    {
        return $this->totalJobsCountPrivate($queues, 'partitions', 'partitionKey');
    }

    public function processedJobsInPastMinutes($queues, $minutes)
    {
        $redis = $this->getConnection();

        $total = 0;
        $queue_key = $this->processedJobsInPastMinutesKey($minutes);
        $keys = $redis->keys($queue_key);
        foreach($keys as $key)
        {
            $total += $redis->zcard($key);
        }
        return $total;
    }

    public function queueProcessedJobsInPastMinutes($queue, $minutes)
    {
        $redis = $this->getConnection();

        $total = 0;
        $queue_key = $this->queueProcessedJobsInPastMinutesKey($queue, $minutes);
        $keys = $redis->keys($queue_key);
        foreach($keys as $key)
        {
            $total += $redis->zcard($key);
        }
        return $total;
    }

    public function partitionProcessedJobsInPastMinutes($queue, $partition, $minutes)
    {
        $redis = $this->getConnection();

        $partition_key = $this->partitionProcessedJobsInPastMinutesKey($queue, $partition, $minutes);
        return $redis->zcard($partition_key);
    }

    public function failedPartitions($queue)
    {
        return $this->partitionsPrivate(
            $queue,
            'failedPartitionListPattern',
            'extractPartitionNameFromFailedPartitionKey'
        );
    }

    public function failedQueues()
    {
        return $this->queuesPrivate('failedQueueListPattern', 'extractQueueNameFromFailedPartitionKey');
    }

    public function failedQueuesWithPartitions()
    {
        return $this->queuesWithPartitionsPrivate('failedQueues', 'failedPartitions');
    }

    public function failedPartitionsWithCount($queue)
    {
        return $this->partitionsWithCountPrivate(
            $queue,
            'failedPartitionListPattern',
            'extractPartitionNameFromFailedPartitionKey',
            'failedPartitionKey',
            'failedPartitionPerSecKey',
            false
        );
    }

    public function failedJobs($queue, $partition)
    {
        return $this->jobsPrivate($queue, $partition, 'failedPartitionKey');
    }

    public function failedJob($queue, $partition, $index)
    {
        return $this->jobPrivate($queue, $partition, $index, 'failedPartitionKey');
    }

    public function totalFailedJobsCount($queues)
    {
        return $this->totalJobsCountPrivate($queues, 'failedPartitions', 'failedPartitionKey');
    }

    public function push($queue, $partition, $job)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->partitionKey($queue, $partition);

        $redis->rpush($partitionKey, $job);
    }

    public function lPush($queue, $partition, $job)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->partitionKey($queue, $partition);

        $redis->lpush($partitionKey, $job);
    }

    public function pushFailed($queue, $partition, $job)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->failedPartitionKey($queue, $partition);

        $redis->rpush($partitionKey, $job);
    }

    public function pop($queue, $partition)
    {
        return $this->popPrivate($queue, $partition, 'partitionKey');
    }

    public function popFailed($queue, $partition)
    {
        return $this->popPrivate($queue, $partition, 'failedPartitionKey');
    }

    public function expectAcknowledge($connection, $queue, $partition, $jobUuid, $job)
    {
        $redis = $this->getConnection();

        $key             = $this->inProgressJobKey($connection, $queue, $partition, $jobUuid);
        $sampleSignalKey = $this->queueSampleSignalKey($queue);

        $redis->mset([
            $key             => $job,
            $sampleSignalKey => serialize([$connection, $queue])
        ]);
    }

    public function acknowledge($connection, $queue, $partition, $jobUuid)
    {
        $redis = $this->getConnection();

        $key = $this->inProgressJobKey($connection, $queue, $partition, $jobUuid);

        $redis->del($key);
    }

    public function retryFailedJobs()
    {
        $count = 0;

        $queues = $this->failedQueues();

        foreach ($queues as $queue) {
            $partitions = $this->failedPartitions($queue);

            $queueSize = 0;
            foreach ($partitions as $partition) {
                while ($job = $this->popFailed($queue, $partition)) {
                    $this->push($queue, $partition, $job);
                    $queueSize++;
                    $count++;
                }
            }

            $this->generateFakeSignals($queue, $queueSize);
        }

        return $count;
    }

    public function retryPartitionFailedJobs($queue, $partition)
    {
        $count = 0;

        $queueSize = 0;

        while ($job = $this->popFailed($queue, $partition)) {
            $this->push($queue, $partition, $job);
            $queueSize++;
            $count++;
        }

        $this->generateFakeSignals($queue, $queueSize);

        return $count;
    }

    public function purgeFailedJobs()
    {
        $redis = $this->getConnection();

        $queues = $this->failedQueues();

        foreach ($queues as $queue) {
            $partitions = $this->failedPartitions($queue);

            foreach ($partitions as $partition) {
                $redis->del($this->failedPartitionKey($queue, $partition));
            }
        }
    }

    public function recoverLost($age = 300)
    {
        $redis = $this->getConnection();

        $pattern = $this->inProgressJobsPattern();
        $keys = $redis->keys($pattern);

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

    public function recoverPartitionLost($queue, $partition, $age = 300)
    {
        $redis = $this->getConnection();

        $pattern = $this->partitionInProgressJobKey($queue, $partition);
        $keys = $redis->keys($pattern);

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

    /**
     * @throws SampleNotFoundException
     */
    public function generateFakeSignals($queue, $count)
    {
        $redis = $this->getConnection();

        $sampleSignalKey = $this->queueSampleSignalKey($queue);

        list ($connection, $_queue) = unserialize($redis->get($sampleSignalKey) ?? serialize(['', '']));

//        if (empty($_queue)) {
//            throw new SampleNotFoundException($queue);
//        }

        for ($i = 1; $i <= $count; $i++) {
            $dispatch = dispatch(new FairSignalJob(null))->onQueue($queue);

            if (!empty($connection)) {
                $dispatch->onConnection($connection);
            }
        }
    }

    private function queuesPrivate(
        $queueListPatternResolver = 'queueListPattern',
        $extractQueueNameFromPartitionKeyResolver = 'extractQueueNameFromPartitionKey'
    ) {
        $redis = $this->getConnection();

        $keys = $redis->keys($this->$queueListPatternResolver());

        $queues = array_map(function ($key) use ($extractQueueNameFromPartitionKeyResolver) {
            return $this->$extractQueueNameFromPartitionKeyResolver($key);
        }, $keys);

        return array_values(array_unique($queues));
    }

    private function queuesWithPartitionsPrivate(
        $queuesResolver = 'queues',
        $partitionsResolver = 'partitions'
    ) {
        $queues = [];

        foreach ($this->$queuesResolver() as $queue) {
            $queues[] = [
                'queue' => $queue,
                'partitions_count' => count($this->$partitionsResolver($queue)),
                'jobs_count' => $this->totalJobsCount([$queue]),
                'processed_jobs_count_1_min' => $this->queueProcessedJobsInPastMinutes($queue, 1),
                'processed_jobs_count_20_min' => $this->queueProcessedJobsInPastMinutes($queue, 20),
            ];
        }

        usort($queues, function ($a, $b) {
            return ($b['partitions_count'] < $a['partitions_count']) ? 1 : -1;
        });

        return $queues;
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

    private function partitionsWithCountPrivate(
        $queue,
        $queuePartitionListPatternResolver = 'queuePartitionListPattern',
        $extractPartitionNameFromPartitionKeyResolver = 'extractPartitionNameFromPartitionKey',
        $partitionKeyResolver = 'partitionKey',
        $partitionPerSecKeyResolver = 'partitionPerSecKey',
        $includePartitionPerSecKeyColumn = true
    ) {
        $redis = $this->getConnection();

        $pattern = $this->$queuePartitionListPatternResolver($queue);

        $keys = $redis->keys($pattern);
        $partitions = [];

        foreach ($keys as $key) {
            $partition = $this->$extractPartitionNameFromPartitionKeyResolver($key);

            $partitionKey = $this->$partitionKeyResolver($queue, $partition);

            $item = [
                'name'  => $partition,
                'count' => $redis->llen($partitionKey) ?: 0
            ];

            if ($includePartitionPerSecKeyColumn) {
                $item['per_minute'] = $this->partitionProcessedJobsInPastMinutes($queue, $partition, 1);
            }

            $partitions[] = $item;
        }

        return $partitions;
    }

    private function jobsPrivate($queue, $partition, $partitionKeyResolver = 'partitionKey')
    {
        $redis = $this->getConnection();
        $perPage = request('limit', 25);
        $startingAt = request('starting_at', 0);

        $partitionKey = $this->$partitionKeyResolver($queue, $partition);

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

    private function jobPrivate($queue, $partition, $index, $partitionKeyResolver = 'partitionKey')
    {
        $redis = $this->getConnection();

        $partitionKey = $this->$partitionKeyResolver($queue, $partition);

        $jobs = $redis->lrange($partitionKey, $index, $index);

        return $jobs ? $jobs[0] : null;
    }

    private function totalJobsCountPrivate(
        $queues,
        $partitionsResolver = 'partitions',
        $partitionKeyResolver = 'partitionKey'
    ) {
        $redis = $this->getConnection();

        $jobsCount = 0;

        foreach ($queues as $queue) {
            foreach ($this->$partitionsResolver($queue) as $partition) {
                $jobsCount += $redis->llen($this->$partitionKeyResolver($queue, $partition));
            }
        }

        return $jobsCount;
    }

    private function popPrivate($queue, $partition, $partitionKeyResolver = 'partitionKey')
    {
        $redis = $this->getConnection();

        $partitionKey = $this->$partitionKeyResolver($queue, $partition);

        $processedKey = $this->partitionProcessedCountJobKey($queue, $partition);
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

    public function getConnection()
    {
        $database = config('fair-queue.database');
        return Redis::connection($database);
    }
}