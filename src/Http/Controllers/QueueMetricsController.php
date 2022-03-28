<?php

namespace Aloware\FairQueue\Http\Controllers;

use Aloware\FairQueue\Facades\FairQueue;

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

    public function partitionJobs($partition)
    {
        $jobs = FairQueue::jobs($partition);
        return response()->json($jobs);
    }

    /**
     * Get job payload.
     *
     * @return object
     */

    public function jobPreview($partition, $index)
    {
        $job = FairQueue::job($partition, $index);
        return response()->json($job);
    }

}
