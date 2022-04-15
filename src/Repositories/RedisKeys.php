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

    private function inProgressJobsPattern()
    {
        return sprintf(
            '*%s-inprogress:*',
            $this->fairQueueKeyPrefix()
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
}
