<?php

namespace Aloware\FairQueue\Repository;

interface RepositoryInterface
{
    /**
     * @return array
     */
    public function partitions($queue);

    public function push($queue, $partition, $job);

    public function pop($queue, $partition);

    public function acknowledge($queue, $partition, $jobId);
}
