<?php

namespace Aloware\FairQueue;

use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class FairSignalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $partition;

    public $originalJob;

    public function __construct($job)
    {
        $this->originalJob = $job;
    }

    public function handle()
    {
        /** @var RepositoryInterface $repository */
        $repository = app(RepositoryInterface::class);

        $selectPartition = function () use ($repository) {

            $partitions = $repository->partitions($this->queue);

            if (empty($partitions)) {
                return 'null';
            }

            $partitionIndex = random_int(0, count($partitions) - 1);

            return $partitions[$partitionIndex];
        };

        $partition = $selectPartition();

        $tries = 0;
        while (empty($jobSerialized = $repository->pop($this->queue, $partition))) {
            // maybe this partition has run out of jobs during the
            //  random selection process, so try getting a fresh list
            //  of partitions and pick another one.

            sleep(1);

            $tries++;
            if ($tries >= 10) {
                // no jobs available to process (concluded after 10
                // times retry with a second delay each).
                return;
            }

            $partition = $selectPartition();
        }

        $job = unserialize($jobSerialized);

        try {
            $job->handle();
        } catch (\Throwable $e) {
            // push it back to the list to be retried later

            $repository->push($this->queue, $partition, $jobSerialized);

            throw $e;
        }
    }

    public function addToPartition()
    {
        /** @var RepositoryInterface $repository */
        $repository = app(RepositoryInterface::class);

        $repository->push($this->queue, $this->partition, serialize($this->originalJob));

        // avoid unnecessary size allocation
        $this->originalJob = null;
    }

    public function onPartition($partition)
    {
        $this->partition = $partition;

        return $this;
    }
}