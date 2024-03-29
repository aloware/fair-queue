<?php

namespace Aloware\FairQueue\Interfaces;

use Aloware\FairQueue\Exceptions\SampleNotFoundException;

interface RepositoryInterface
{
    /**
     * @return array
     */
    public function partitions($queue);

    public function failedPartitions($queue);

    public function push($queue, $partition, $job);

    public function pushFailed($queue, $partition, $job);

    public function pop($queue, $partition);

    public function popFailed($queue, $partition);

    public function expectAcknowledge($connection, $queue, $partition, $jobUuid, $job);

    public function acknowledge($connection, $queue, $partition, $jobUuid);

    public function queues();

    public function queuesWithPartitions();

    public function partitionsWithCount($queue);

    public function jobs($queue, $partition);

    public function job($queue, $partition, $index);

    public function totalJobsCount($queues);

    public function processedJobsInPastMinutes($queues, $minutes);

    public function failedQueues();

    public function failedQueuesWithPartitions();

    public function failedPartitionsWithCount($queue);

    public function failedJobs($queue, $partition);

    public function failedJob($queue, $partition, $index);

    public function totalFailedJobsCount($queues);

    public function retryFailedJobs(array $queues, array $partitions);

    public function retryPartitionFailedJobs($queue, $partition);

    public function recoverPartitionLost($queue, $partition);

    public function purgeFailedJobs(array $queues, array $partitions);

    public function recoverLost($age = 300);

    public function recoverStuckJobs();

    public function getConnection();

    public function countFairSignals($queue, $partition);

    public  function countAllJobs($queue);

    /**
     * @throws SampleNotFoundException
     */
    public function generateFakeSignals($queue, $count);

}
