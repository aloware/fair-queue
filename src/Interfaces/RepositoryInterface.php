<?php

namespace Aloware\FairQueue\Interfaces;

interface RepositoryInterface
{
    /**
     * @return array
     */
    public function partitions($queue);

    public function push($queue, $partition, $job);

    public function pushFailed($queue, $partition, $job);

    public function pop($queue, $partition);

    public function popFailed($queue, $partition);

    public function expectAcknowledge($connection, $queue, $partition, $job, $wait = 60);

    public function acknowledge($connection, $queue, $partition, $jobUuid);

    public function recoverLost();

    public function queues();

    public function queuesWithPartitions();

    public function partitionsWithCount($queue);

    public function jobs($queue, $partition);

    public function job($queue, $partition, $index);

    public function totalJobsCount($queues);

}
