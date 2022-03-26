<?php

namespace Aloware\FairQueue\Http\Controllers;

use Aloware\FairQueue\Facades\FairQueue;

class DashboardStatsController extends Controller
{
    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function index()
    {
        $queues = FairQueue::queues();

        return response()->json([
            'totalJobs'  => '-',
            'totalQueues' => count($queues),
            'failedJobs' => 0,
            'status' => 'running'
        ]);
    }

}
