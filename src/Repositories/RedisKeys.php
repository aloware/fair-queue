<?php

namespace Aloware\FairQueue\Repositories;

trait RedisKeys
{
    private function partitionKey($queue, $partition, $prefix = '')
    {
        return sprintf(
            '%s%s:%s:%s',
            $this->fairQueueKeyPrefix(),
            $prefix,
            $queue,
            $partition
        );
    }

    private function queueKey($queue, $prefix = '')
    {
        return sprintf(
            '%s%s:%s:*',
            $this->fairQueueKeyPrefix(),
            $prefix,
            $queue
        );
    }

    /**
     * Get Fair Signal Redis Key Name
     *
     * @param string $queue
     * @param string $partition
     *
     * @return string
     */
    private function fairSignalKey($queue, $partition)
    {
        $signal_key_prefix_for_horizon = config('fair-queue.signal_key_prefix_for_horizon');
        $horizon_prefix = config('horizon.prefix');

        return sprintf(
            '%s%s%s:%s:*',
            $horizon_prefix,
            $signal_key_prefix_for_horizon,
            $queue,
            $partition,
        );
    }

    /**
     * Get Fair Signal Redis Key Name By UUID
     *
     * @param string $queue
     * @param string $partition
     * @param string $uuid
     *
     * @return string
     */
    private function fairSignalKeyByUuid($queue, $partition, $uuid)
    {
        $signal_key_prefix_for_horizon = config('fair-queue.signal_key_prefix_for_horizon');
        $horizon_prefix = config('horizon.prefix');

        return sprintf(
            '%s%s%s:%s:%s',
            $horizon_prefix,
            $signal_key_prefix_for_horizon,
            $queue,
            $partition,
            $uuid,
        );
    }

    /**
     * Get Horizon Signals Redis Key Name
     *
     * @param string $queue
     *
     * @return string
     */
    private function horizonSignalsKey($queue)
    {
        return sprintf(
            'queues:%s',
            $queue,
        );
    }

    private function failedPartitionKey($queue, $partition)
    {
        return $this->partitionKey($queue, $partition, '-failed');
    }

    private function partitionProcessedCountJobKey($queue, $partition)
    {
        return $this->partitionKey($queue, $partition, '-internal') . ':processed';
    }

    private function queueProcessedJobsInPastMinutesKey($queue, $minutes = 1)
    {
        $queue_key = $this->queueKey($queue, '-internal');
        return "{$queue_key}:processed:{$minutes}min";
    }

    private function processedJobsInPastMinutesKey($minutes = 1)
    {
        $queue_key = $this->queueKey('*', '-internal');
        return "{$queue_key}:processed:{$minutes}min";
    }

    private function partitionProcessedJobsInPastMinutesKey($queue, $partition, $minutes = 1)
    {
        $partition_key = $this->partitionKey($queue, $partition, '-internal');
        return "{$partition_key}:processed:{$minutes}min";
    }

    private function recentProcesedJobsPattern($minute = 1)
    {
        return sprintf(
            '%s-internal:*:processed:' . $minute . 'min*',
            $this->fairQueueKeyPrefix()
        );
    }

    private function partitionPerSecKey($queue, $partition)
    {
        return $this->partitionKey($queue, $partition, '-internal') . ':persec';
    }

    private function queueSampleSignalKey($queue)
    {
        return sprintf(
            '%s-sample:%s',
            $this->fairQueueKeyPrefix(),
            $queue
        );
    }

    private function inProgressJobKey($connection, $queue, $partition, $jobUuid)
    {
        return sprintf(
            '%s-inprogress:%s:%s:%s:%s',
            $this->fairQueueKeyPrefix(),
            $connection,
            $queue,
            $partition,
            $jobUuid
        );
    }

    private function queueListPattern()
    {
        return sprintf(
            '%s:*',
            $this->fairQueueKeyPrefix()
        );
    }

    private function queuePartitionListPattern($queue)
    {
        return sprintf(
            '%s:%s:*',
            $this->fairQueueKeyPrefix(),
            $queue
        );
    }

    private function failedQueuePartitionListPattern($queue)
    {
        return sprintf(
            '%s-failed:%s:*',
            $this->fairQueueKeyPrefix(),
            $queue
        );
    }

    private function failedQueueListPattern()
    {
        return sprintf(
            '%s-failed:*',
            $this->fairQueueKeyPrefix()
        );
    }

    private function failedPartitionListPattern($queue)
    {
        return sprintf(
            '%s-failed:%s:*',
            $this->fairQueueKeyPrefix(),
            $queue
        );
    }

    private function inProgressJobsPattern()
    {
        return sprintf(
            '%s-inprogress:*',
            $this->fairQueueKeyPrefix()
        );
    }

    private function partitionInProgressJobKey($queue, $partition)
    {
        return sprintf(
            '%s-inprogress:*:%s:%s',
            $queue,
            $partition
        );
    }

    private function extractQueueNameFromPartitionKey($partitionKey)
    {
        $rep = $this->removePrefix($this->fairQueueKeyPrefix() . ':', $partitionKey);
        return explode(':', $rep)[0];
    }

    private function extractPartitionNameFromPartitionKey($partitionKey)
    {
        $rep = $this->removePrefix($this->fairQueueKeyPrefix() . ':', $partitionKey);
        return explode(':', $rep)[1];
    }

    private function extractQueueNameFromFailedPartitionKey($partitionKey)
    {
        $rep = $this->removePrefix($this->fairQueueKeyPrefix() . '-failed:', $partitionKey);
        return explode(':', $rep)[0];
    }

    private function extractPartitionNameFromFailedPartitionKey($partitionKey)
    {
        $rep = $this->removePrefix($this->fairQueueKeyPrefix() . '-failed:', $partitionKey);
        return explode(':', $rep)[1];
    }

    private function extractInProgressJobKey($partitionKey)
    {
        $rep = $this->removePrefix($this->fairQueueKeyPrefix() . '-inprogress:', $partitionKey);

        $splitted = explode(':', $rep);

        $connection = $splitted[0];
        $queue      = $splitted[1];
        $partition  = $splitted[2];
        $jobUuid    = $splitted[3];

        return [$connection, $queue, $partition, $jobUuid];
    }

    private function fairQueueKeyPrefix()
    {
        return config('fair-queue.key_prefix');
    }

    private function removePrefix($prefix, $value)
    {
        $pos = strpos($value, $prefix);
        return substr($value, $pos + strlen($prefix));
    }

    /**
     * Get keys by given pattern
     *
     * @param \Illuminate\Redis\Connections\Connection $redis
     * @param string $pattern
     * @param integer $count
     *
     * @return array
     */
    public function getKeysFromPattern($redis, $pattern, $count = 15000)
    {
        $keys = [];
        $iterator = null;

        while ($keysList = $redis->scan($iterator, ['match' => $pattern, 'count' => $count])) {
            $iterator = $keysList[0];
            foreach ($keysList[1] as $key) {
                $keys[] = $key;
            }
        }
        return $keys;
    }
}
