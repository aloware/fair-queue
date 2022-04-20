<?php

namespace Aloware\FairQueue\Http\Controllers;

use Aloware\FairQueue\Facades\FairQueue;
use Aloware\FairQueue\Http\Requests\FakeSignalRequest;
use Aloware\FairQueue\Http\Requests\RecoverLostJobsRequest;

class QueueController extends Controller
{
    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function generateFakeSignal(FakeSignalRequest $request, $queue)
    {
        FairQueue::generateFakeSignals($queue, $request->amount);
    }

    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function recoverLostJobs(RecoverLostJobsRequest $request)
    {
        $recovered_count = FairQueue::recoverLost($request->amount);

        return response()->json([
            'recovered' => $recovered_count
        ]);
    }

    public function retryFailedJobs()
    {
        $count = FairQueue::retryFailedJobs();
        return response()->json([
            'count' => $count
        ]);
    }

    public function purgeFailedJobs()
    {
        FairQueue::purgeFailedJobs();
    }

}
