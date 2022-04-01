<?php

namespace Aloware\FairQueue\Http\Controllers;

use Aloware\FairQueue\Facades\FairQueue;
use Aloware\FairQueue\Http\Requests\PartitionJobsRequest;

class QueueMetricsController extends Controller
{
    /**
     * Get the paritions of a queue.
     *
     * @return array
     */

    public function queuePartitions($queue)
    {
        $partitions = FairQueue::partitionsWithCount($queue);
        return response()->json($partitions);
    }

    /**
     * Get the paritions of a queue.
     *
     * @return array
     */

    public function partitionJobs(PartitionJobsRequest $request, $queue, $partition)
    {
        $jobs = FairQueue::jobs($queue, $partition);
        return response()->json($jobs);
    }

    /**
     * Get job payload.
     *
     * @return object
     */

    public function jobPreview($queue, $partition, $index)
    {
        $job = FairQueue::job($queue, $partition, $index);
        return response()->json($job);
    }

}
