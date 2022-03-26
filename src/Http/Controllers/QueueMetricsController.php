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

}
