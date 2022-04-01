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
        $totalJobs = FairQueue::totalJobsCount($queues);

        return response()->json([
            'totalJobs'  => $totalJobs,
            'totalQueues' => count($queues),
        ]);
    }

}
