<?php

namespace Aloware\FairQueue\Http\Controllers;

use Aloware\FairQueue\Facades\FairQueue;

class MonitoringController extends Controller
{
    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function index()
    {
        $queues = FairQueue::queuesWithPartitions();
        return response()->json($queues);
    }

    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function failedQueues()
    {
        $queues = FairQueue::queuesWithPartitions();
        // $queues = FairQueue::failedQueuesWithPartitions();
        return response()->json($queues);
    }

    /**
     * Get failed queue partitions
     *
     * @return array
     */
    public function failedQueuePartitions($queue)
    {
        $partitions = FairQueue::partitionsWithCount($queue);
        // $partitions = FairQueue::failedPartitionsWithCount($queue);
        return response()->json($partitions);
    }

}
