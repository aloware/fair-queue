<?php

namespace Aloware\FairQueue\Interfaces;

interface RepositoryInterface
{
    /**
     * @return array
     */
    public function partitions($queue);

    public function push($queue, $partition, $job);

    public function pop($queue, $partition);

    public function acknowledge($queue, $partition, $jobId);

    public function queues();

    public function queuesWithPartitions();

    public function partitionsWithCount($queue);

}