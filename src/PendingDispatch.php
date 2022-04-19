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
        $this->job->originalJob->maxTries = max(intval($number), 1);

        return $this;
    }

    public function __destruct()
    {
        $this->job->addToPartition();

        app(Dispatcher::class)->dispatch($this->job);
    }
}
