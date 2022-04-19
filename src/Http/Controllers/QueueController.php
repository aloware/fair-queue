<?php

namespace Aloware\FairQueue\Http\Controllers;

use Aloware\FairQueue\Facades\FairQueue;

class QueueController extends Controller
{
    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function generateFakeSignal($queue)
    {
        $amount = request('amount', null);
        FairQueue::generateFakeSignals($queue, $amount);
    }

    /**
     * Get the key performance stats for the dashboard.
     *
     * @return array
     */
    public function recoverLostJobs()
    {
        $amount = request('amount', null);
        $recovered_count = FairQueue::recoverLost($amount);

        return response()->json([
            'recovered' => $recovered_count
        ]);
    }

    public function retryFailedJobs()
    {
        FairQueue::retryFailedJobs();
    }

    public function purgeFailedJobs()
    {
        FairQueue::purgeFailedJobs();
    }

}
