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

    private function failedJobsPartitionKey($queue, $partition)
    {
        return $this->partitionKey($queue, $partition, '-failed');
    }

    private function partitionProcessedCountJobKey($queue, $partition)
    {
        return $this->partitionKey($queue, $partition, '-internal') . ':processed';
    }

    private function partitionPerSecKey($queue, $partition)
    {
        return $this->partitionKey($queue, $partition, '-internal') . ':persec';
    }

    private function inProgressJobKey($queue, $partition, $jobUuid)
    {
        return sprintf(
            '%s-inprogress:%s:%s:%s',
            $this->fairQueueKeyPrefix(),
            $queue,
            $partition,
            $jobUuid
        );
    }

    private function queueListPattern()
    {
        return sprintf(
            '*%s:*',
            $this->fairQueueKeyPrefix()
        );
    }

    private function queuePartitionListPattern($queue)
    {
        return sprintf(
            '*%s:%s:*',
            $this->fairQueueKeyPrefix(),
            $queue
        );
    }

    private function queueFailedJobsPartitionListPattern($queue)
    {
        return sprintf(
            '*%s-failed:%s:*',
            $this->fairQueueKeyPrefix(),
            $queue
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

    private function extractPartitionNameFromFailedJobsPartitionKey($partitionKey)
    {
        $rep = $this->removePrefix($this->fairQueueKeyPrefix() . '-failed:', $partitionKey);
        return explode(':', $rep)[1];
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
}
