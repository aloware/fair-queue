<?php

namespace Aloware\FairQueue;

use Aloware\FairQueue\Exceptions\EmptyPartitionNameException;
use Illuminate\Contracts\Bus\Dispatcher;

class PendingDispatch extends \Illuminate\Foundation\Bus\PendingDispatch
{
    /**
     * @throws EmptyPartitionNameException
     */
    public function fairConsume($partition)
    {
        if (empty($partition)) {
            throw new EmptyPartitionNameException();
        }

        $this->job->partition = $partition;

        return $this;
    }

    public function tries($number = 1)
    {
        $this->job->originalJob->maxTries = max((int) $number, 1);

        return $this;
    }

    public function __destruct()
    {
        if (!config('fair-queue.enabled')) {
            app(Dispatcher::class)->dispatch($this->job->originalJob);
            return ;
        }

        $this->job->addToPartition();

        app(Dispatcher::class)->dispatch($this->job);
    }
}
