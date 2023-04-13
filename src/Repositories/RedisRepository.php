<?php

namespace Aloware\FairQueue\Repositories;

use Aloware\FairQueue\Exceptions\SampleNotFoundException;
use Aloware\FairQueue\FairSignalJob;
use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Support\Facades\Redis;

class RedisRepository implements RepositoryInterface
{
    use RedisKeys;

    /**
     * Get all partitions
     *
     * @param string $queue
     *
     * @return array
     */
    public function partitions($queue)
    {
        return $this->partitionsPrivate($queue);
    }

    /**
     * Get all queues
     *
     * @return array
     */
    public function queues()
    {
        return $this->queuesPrivate('queueListPattern', 'extractQueueNameFromPartitionKey');
    }

    /**
     * Get all queues with their partitions
     *
     * @return array
     */
    public function queuesWithPartitions()
    {
        return $this->queuesWithPartitionsPrivate('queues', 'partitions');
    }

    /**
     * Get all jobs
     *
     * @return array
     */
    public function jobs($queue, $partition)
    {
        return $this->jobsPrivate($queue, $partition, 'partitionKey');
    }

    /**
     * Get job Class name and the payload
     *
     * @return array
     */
    public function job($queue, $partition, $index)
    {
        $job = $this->jobPrivate($queue, $partition, $index, 'partitionKey');

        return [
            'name' => get_class(unserialize($job)),
            'payload' => $job
        ];
    }

    /**
     * Get partitions With number of jobs
     *
     * @param string $queue
     *
     * @return array
     */
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

    /**
     * Get total jobs of the given queues
     *
     * @param array $queues
     *
     * @return int
     */
    public function totalJobsCount($queues)
    {
        return $this->totalJobsCountPrivate($queues, 'partitions', 'partitionKey');
    }

    /**
     * Get Total Jobs
     *
     * @param array $queues
     *
     * @return int
     */
    public function processedJobsInPastMinutes($queues, $minutes)
    {
        $redis = $this->getConnection();

        $total = 0;
        $queue_key = $this->processedJobsInPastMinutesKey($minutes);
        $keys = $this->getKeysFromPattern($redis, $queue_key);
        foreach($keys as $key)
        {
            $total += $redis->zcard($key);
        }
        return $total;
    }

    /**
     * Get number of processed jobs of a queue in past minutes
     *
     * @param string $queue
     * @param int $minutes
     *
     * @return int
     */
    public function queueProcessedJobsInPastMinutes($queue, $minutes)
    {
        $redis = $this->getConnection();

        $total = 0;
        $queue_key = $this->queueProcessedJobsInPastMinutesKey($queue, $minutes);
        $keys = $this->getKeysFromPattern($redis, $queue_key);
        foreach($keys as $key)
        {
            $total += $redis->zcard($key);
        }
        return $total;
    }

    /**
     * Get number of processed jobs of a partition in past minutes
     *
     * @param string $queue
     * @param string $partition
     * @param int $minutes
     *
     * @return int
     */
    public function partitionProcessedJobsInPastMinutes($queue, $partition, $minutes)
    {
        $redis = $this->getConnection();

        $partition_key = $this->partitionProcessedJobsInPastMinutesKey($queue, $partition, $minutes);
        return $redis->zcard($partition_key);
    }

    /**
     * Get list of failed partitions of a queue
     *
     * @param string $queue
     *
     * @return array
     */
    public function failedPartitions($queue)
    {
        return $this->partitionsPrivate(
            $queue,
            'failedPartitionListPattern',
            'extractPartitionNameFromFailedPartitionKey'
        );
    }

    /**
     * Get list of failed queues
     *
     * @return array
     */
    public function failedQueues()
    {
        return $this->queuesPrivate('failedQueueListPattern', 'extractQueueNameFromFailedPartitionKey');
    }

    /**
     * Get list of failed queues
     *
     * @return array
     */
    public function failedQueuesWithPartitions()
    {
        return $this->queuesWithPartitionsPrivate('failedQueues', 'failedPartitions');
    }

    /**
     * Get list of failed partitions with number of jobs
     *
     * @param string $queue
     *
     * @return array
     */
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

    /**
     * Get list of failed job of a partition
     *
     * @param string $queue
     * @param string $partition
     *
     * @return array
     */
    public function failedJobs($queue, $partition)
    {
        return $this->jobsPrivate($queue, $partition, 'failedPartitionKey');
    }

    /**
     * Get failed job Class name and Payload based on its index
     *
     * @param string $queue
     * @param string $partition
     * @param int $index
     *
     * @return array
     */
    public function failedJob($queue, $partition, $index)
    {
        $job = $this->jobPrivate($queue, $partition, $index, 'failedPartitionKey');

        return [
            'name' => get_class(unserialize($job)),
            'payload' => $job
        ];
    }

    /**
     * Get number of failed jobs of given queues
     *
     * @param array $queues
     *
     * @return int
     */
    public function totalFailedJobsCount($queues)
    {
        return $this->totalJobsCountPrivate($queues, 'failedPartitions', 'failedPartitionKey');
    }

    /**
     * Push a job into given queue and partition at the tail of list
     *
     * @param string $queue
     * @param string $partition
     *
     * @return void
     */
    public function push($queue, $partition, $job)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->partitionKey($queue, $partition);
        $listKeyName = $this->queuePartitionsListKeyName($queue);

        $redis->rpush($partitionKey, $job);
        $redis->sadd($listKeyName, $partition);
    }

     /**
     * Push a job into given queue and partition at the head of list
     *
     * @param string $queue
     * @param string $partition
     *
     * @return void
     */
    public function lPush($queue, $partition, $job)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->partitionKey($queue, $partition);

        $redis->lpush($partitionKey, $job);
    }

     /**
     * Push a failed job into given queue and partition at the tail of list
     *
     * @param string $queue
     * @param string $partition
     *
     * @return void
     */
    public function pushFailed($queue, $partition, $job)
    {
        $redis = $this->getConnection();

        $partitionKey = $this->failedPartitionKey($queue, $partition);

        $redis->rpush($partitionKey, $job);
    }

    /**
     * Returns and removes the first element of the list
     *
     * @param string $queue
     * @param string $partition
     *
     * @return array|null
     */
    public function pop($queue, $partition)
    {
        return $this->popPrivate($queue, $partition, 'partitionKey');
    }

    /**
     * Returns and removes the first element of the failed jobs list
     *
     * @param string $queue
     * @param string $partition
     *
     * @return array|null
     */
    public function popFailed($queue, $partition)
    {
        return $this->popPrivate($queue, $partition, 'failedPartitionKey');
    }

    /**
     * Sets a expect of acknowledge
     *
     * @param string $connection
     * @param string $queue
     * @param string $partition
     * @param string $jobUuid
     * @param string $job
     *
     * @return void
     */
    public function expectAcknowledge($connection, $queue, $partition, $jobUuid, $job)
    {
        $redis = $this->getConnection();

        $key = $this->inProgressJobKey($connection, $queue, $partition, $jobUuid);
        $sampleSignalKey = $this->queueSampleSignalKey($queue);

        $redis->mset([
            $key => $job,
            $sampleSignalKey => serialize([$connection, $queue])
        ]);
    }

    /**
     * After acknowledge receive lets remove it from the list
     *
     * @param string $connection
     * @param string $queue
     * @param string $partition
     * @param string $jobUuid
     *
     * @return void
     */
    public function acknowledge($connection, $queue, $partition, $jobUuid)
    {
        $redis = $this->getConnection();
        $signals_redis = $this->getSignalsConnection();

        $signal_key = $this->fairSignalKeyByUuid($queue, $partition, $jobUuid);

        $key = $this->inProgressJobKey($connection, $queue, $partition, $jobUuid);

        $signals_redis->del($signal_key);
        $redis->del($key);
    }

    /**
     * Retry failed jobs of given queues and partitions
     *
     * @param array $queues
     * @param array $queue_partitions
     *
     * @return int
     */
    public function retryFailedJobs(array $queues = [], array $queue_partitions = [])
    {
        $count = 0;

        if(!$queues) {
            $queues = $this->failedQueues();
        }

        foreach ($queues as $queue) {
            if(!$queue_partitions) {
                $partitions = $this->failedPartitions($queue);
            } else {
                $partitions = $queue_partitions;
            }

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

    /**
     * Retry failed jobs of a given partition
     *
     * @param string $queue
     * @param string $partition
     *
     * @return int
     */
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

    /**
     * Purge failed jobs of given queues and partitions
     *
     * @param array $queues
     * @param array $queue_partitions
     *
     * @return void
     */
    public function purgeFailedJobs(array $queues = [], array $queue_partitions = [])
    {
        $redis = $this->getConnection();

        if(!$queues) {
            $queues = $this->failedQueues();
        }

        foreach ($queues as $queue) {
            if(!$queue_partitions) {
                $partitions = $this->failedPartitions($queue);
            } else {
                $partitions = $queue_partitions;
            }

            foreach ($partitions as $partition) {
                $redis->del($this->failedPartitionKey($queue, $partition));
            }
        }
    }

    /**
     * Recovers lost jobs since given seconds ago
     *
     * @param int $age
     *
     * @return int
     */
    public function recoverLost($age = 300)
    {
        $redis = $this->getConnection();

        $pattern = $this->inProgressJobsPattern();

        $keys = $this->getKeysFromPattern($redis, $pattern);

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

            // and generate fake signal
            $dispatch = dispatch(new FairSignalJob(null))->onQueue($queue);

            if (!empty($connection)) {
                $dispatch->onConnection($connection);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Recovers lost jobs for the given partition since given seconds ago
     *
     * @param string $queue
     * @param string $partition
     * @param int $age
     *
     * @return int
     */
    public function recoverPartitionLost($queue, $partition, $age = 300)
    {
        $redis = $this->getConnection();

        $pattern = $this->partitionInProgressJobKey($queue, $partition);
        $keys = $this->getKeysFromPattern($redis, $pattern);

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

            // and generate fake signal
            $dispatch = dispatch(new FairSignalJob(null))->onQueue($queue);

            if (!empty($connection)) {
                $dispatch->onConnection($connection);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Count Fair Signals of the give queue and partition
     *
     * @param string $queue
     * @param string $partition
     *
     * @return int
     */
    public function countFairSignals($queue, $partition)
    {
        $signals_redis = $this->getSignalsConnection();
        $pattern = $this->fairSignalKey($queue, $partition);

        return $signals_redis->eval(<<<"LUA"
            return #redis.pcall('keys', '{$pattern}')
        LUA, 0);
    }

    /**
     * Count Horizon Signals of the give queue
     *
     * @param string $queue
     *
     * @return int
     */
    public function countHorizonFairSignals($queue)
    {
        $signals_redis = $this->getSignalsConnection();
        $pattern = $this->horizonSignalsKey($queue);

        $keys = $this->getKeysFromPattern($signals_redis, $pattern);

        $count = 0;
        foreach($keys as $key) {
            $count += $signals_redis->llen($key);
        }

        return $count;
    }

    /**
     * Count all jobs of the given queue
     *
     * @param string $queue
     *
     * @return int
     */
    public function countAllJobs($queue)
    {
        $redis = $this->getConnection();

        $pattern = $this->queueKey($queue);
        $keys = $this->getKeysFromPattern($redis, $pattern);

        $count = 0;
        foreach($keys as $key) {
            $count += $redis->llen($key);
        }
        return $count;
    }

    /**
     * Count all jobs of the given queue
     *
     * @param string $queue
     *
     * @return int
     */
    public function recoverStuckJobs()
    {
        $queues = $this->queues();

        $count = 0;

        foreach ($queues as $queue) {
            $jobs_count = $this->countAllJobs($queue);
            $signals_count = $this->countFairSignals($queue, '*');

            if($jobs_count > $signals_count) {
                $queue_size = $jobs_count - $signals_count;
                $count += $queue_size;
                $this->generateFakeSignals($queue, $queue_size);
            }
        }

        return $count;
    }

    /**
     * Remove extra horizon signals of all queues
     *
     * @param string $queue
     *
     * @return int
     */
    public function removeExtraHorizonSignals()
    {
        $signals_redis = $this->getSignalsConnection();
        $queues = $this->queues();

        $count = 0;

        foreach ($queues as $queue) {
            $jobs_count = $this->countAllJobs($queue);
            $horizon_signals_count = $this->countHorizonFairSignals($queue);
            $pattern = $this->horizonSignalsKey($queue);

            if($horizon_signals_count > $jobs_count) {
                $extra_signals_count = $horizon_signals_count - $jobs_count;
                $count += $extra_signals_count;
                $signals_redis->ltrim($pattern, $extra_signals_count, -1);
            }
        }

        return $count;
    }

     /**
     * Generates fake signal
     *
     * @param string $queue
     * @param int $count
     *
     * @throws SampleNotFoundException

     * @return int
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

    /**
     * Gets name of all queues
     *
     * @param string $queueListPatternResolver
     * @param string $extractQueueNameFromPartitionKeyResolver
     *
     *
     * @return array
     */
    private function queuesPrivate(
        $queueListPatternResolver = 'queueListPattern',
        $extractQueueNameFromPartitionKeyResolver = 'extractQueueNameFromPartitionKey'
    ) {
        $redis = $this->getConnection();

        $keys = $this->getKeysFromPattern($redis, $this->$queueListPatternResolver());

        $queues = array_map(function ($key) use ($extractQueueNameFromPartitionKeyResolver) {
            return $this->$extractQueueNameFromPartitionKeyResolver($key);
        }, $keys);

        return array_values(array_unique($queues));
    }

    /**
     * Get all queues with their partitions
     *
     * @param string $queuesResolver
     * @param string $partitionsResolver
     *
     * @return array
     */
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
            return ($b['queue'] < $a['queue']) ? 1 : -1;
        });

        return $queues;
    }

    /**
     * Get all partitions
     *
     * @param string $queue
     * @param string $queuePartitionListPatternResolver
     * @param string $extractorResolver
     *
     * @return array
     */
    private function partitionsPrivate(
        $queue,
        $queuePartitionListPatternResolver = 'queuePartitionListPattern',
        $extractorResolver = 'extractPartitionNameFromPartitionKey'
    ) {
        $redis = $this->getConnection();

        $keys = $this->getKeysFromPattern($redis, $this->$queuePartitionListPatternResolver($queue));

        $partitions = array_map(function ($item) use ($extractorResolver) {
            return $this->$extractorResolver($item);
        }, $keys);

        return array_values($partitions);
    }

    /**
     * Get random partition name of a queue
     *
     * @param string $queue
     *
     * @return string
     */
    public function getRandomPartitionName($queue)
    {
        $redis = $this->getConnection();
        $listKeyName = $this->queuePartitionsListKeyName($queue);

        return $redis->srandmember($listKeyName);
    }

    /**
     * Get partitions With number of jobs
     *
     * @param string $queue
     * @param string $queuePartitionListPatternResolver
     * @param string $extractPartitionNameFromPartitionKeyResolver
     *
     * @return array
     */
    private function partitionsWithCountPrivate(
        $queue,
        $queuePartitionListPatternResolver = 'queuePartitionListPattern',
        $extractPartitionNameFromPartitionKeyResolver = 'extractPartitionNameFromPartitionKey',
        $partitionKeyResolver = 'partitionKey'
    ) {
        $redis = $this->getConnection();

        $pattern = $this->$queuePartitionListPatternResolver($queue);

        $keys = $this->getKeysFromPattern($redis, $pattern);
        $partitions = [];

        foreach ($keys as $key) {
            $partition = $this->$extractPartitionNameFromPartitionKeyResolver($key);

            $partitionKey = $this->$partitionKeyResolver($queue, $partition);

            $count = $redis->llen($partitionKey) ?: 0;
            $per_minute = $this->partitionProcessedJobsInPastMinutes($queue, $partition, 1);
            $eta = $per_minute ? ($count / $per_minute) : 0;
            $item = [
                'per_minute' => $per_minute,
                'name'  => $partition,
                'count' => $count,
                'eta' => round($eta, 2),
            ];

            $partitions[] = $item;
        }

        usort($partitions, function ($a, $b) {
            return ($b['name'] < $a['name']) ? 1 : -1;
        });

        return $partitions;
    }

    /**
     * Get all jobs
     *
     * @param string $queue
     * @param string $partition
     * @param string $partitionKeyResolver
     *
     * @return array
     */
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

    /**
     * Get job
     *
     * @param string $queue
     * @param string $partition
     * @param string $index
     * @param string $partitionKeyResolver
     *
     * @return array
    */
    private function jobPrivate($queue, $partition, $index, $partitionKeyResolver = 'partitionKey')
    {
        $redis = $this->getConnection();

        $partitionKey = $this->$partitionKeyResolver($queue, $partition);

        $jobs = $redis->lrange($partitionKey, $index, $index);

        return $jobs ? $jobs[0] : null;
    }

    /**
     * Get total jobs of the given queues
     *
     * @param array $queues
     * @param string $partitionsResolver
     * @param string $partitionKeyResolver
     *
     * @return int
    */
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

    /**
     * Returns and removes the first element of the list
     *
     * @param string $queue
     * @param string $partition
     * @param string $partitionKeyResolver
     *
     * @return string|null
    */
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

        $result = $redis->multi()
            ->lpop($partitionKey)
            ->exists($partitionKey)
            ->exec();

        // if processed job is the last job of the partition,
        // going to remove partition name from queue partitions list
        if($result[1] === 0 && $result[0] !== false) {
            $this->removePatitionNameFromList($queue, $partition);
        }

        return $result[0];
    }

    /**
     * Removes partition name from queue partitions list
     * @param string $queue
     * @param string $partition
     *
     * @return void
    */
    public function removePatitionNameFromList($queue, $partition)
    {
        $listKeyName = $this->queuePartitionsListKeyName($queue);
        $redis = $this->getConnection();
        $redis->srem($listKeyName, $partition);
    }

    /**
     * Returns Redis Connection
     *
     * @return \Illuminate\Redis\Connections\Connection
    */
    public function getConnection()
    {
        $database = config('fair-queue.database');
        return Redis::connection($database);
    }

    /**
     * Returns Redis Fair Signal Connection
     *
     * @return \Illuminate\Redis\Connections\Connection
    */
    public function getSignalsConnection()
    {
        $database = config('fair-queue.signals_database');
        return Redis::connection($database);
    }
}
